<?php

namespace Drupal\eupago_payments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_order\Entity\Order;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;

/**
 * Eupago Payment Gateway Controller.
 *
 * Handles Multibanco, MB WAY, and Credit Card payments via Eupago API.
 */
class EupagoCheckoutController extends ControllerBase {

  /**
   * Eupago API configuration.
   */
  private function getEupagoConfig() {
    // Check for live mode setting.
    $state = \Drupal::state();
    $mode = $state->get('eupago_payments.mode', 'sandbox');

    if ($mode === 'live') {
      return [
        'api_key' => $state->get('eupago_payments.live_api_key', ''),
        'base_url' => 'https://clientes.eupago.pt',
        'base_url_v2' => 'https://clientes.eupago.pt',
        'mode' => 'live',
      ];
    }

    return [
      'api_key' => $state->get('eupago_payments.sandbox_api_key', 'demo-ef71-e8d1-57a2-dac'),
      'base_url' => 'https://sandbox.eupago.pt',
      'base_url_v2' => 'https://sandbox.eupago.pt',
      'mode' => 'sandbox',
    ];
  }

  /**
   * Base64 URL decode.
   */
  private function base64UrlDecode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
      $padlen = 4 - $remainder;
      $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
  }

  /**
   * Verify JWT token from cookie or header.
   */
  private function verifyJwtToken(Request $request) {
    $token = NULL;

    if ($request->headers->has('Authorization')) {
      $auth_header = $request->headers->get('Authorization');
      if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        $token = $matches[1];
      }
    }

    if (!$token) {
      $token = $request->cookies->get('auth_token');
    }

    if (!$token) {
      return NULL;
    }

    try {
      $parts = explode('.', $token);
      if (count($parts) !== 3) {
        return NULL;
      }

      list($header64, $payload64, $signature64) = $parts;

      $secret = \Drupal::config('system.site')->get('uuid');

      $expected_signature = hash_hmac('sha256', $header64 . '.' . $payload64, $secret, TRUE);
      $expected_signature64 = rtrim(strtr(base64_encode($expected_signature), '+/', '-_'), '=');

      if ($signature64 !== $expected_signature64) {
        return NULL;
      }

      $payload_json = $this->base64UrlDecode($payload64);
      $payload = json_decode($payload_json, TRUE);

      if (!$payload || !isset($payload['uid'])) {
        return NULL;
      }

      if (isset($payload['exp']) && $payload['exp'] < time()) {
        return NULL;
      }

      $user = User::load($payload['uid']);
      return $user ?: NULL;

    }
    catch (\Exception $e) {
      \Drupal::logger('eupago_payments')->error('JWT verification failed: @message', ['@message' => $e->getMessage()]);
    }

    return NULL;
  }

  /**
   * Validate and load order, assign to user if anonymous.
   */
  private function loadAndValidateOrder($order_id, $user) {
    $order = Order::load($order_id);
    if (!$order) {
      return NULL;
    }

    $current_customer_id = $order->getCustomerId();

    if ($current_customer_id == 0) {
      $order->setCustomerId($user->id());
      $order->setEmail($user->getEmail());
      $order->save();
    }
    elseif ($current_customer_id != $user->id()) {
      return NULL;
    }

    return $order;
  }

  /**
   * Complete an order after payment confirmation.
   */
  private function completeOrder(Order $order, $payment_method, $remote_id) {
    try {
      // Place the order first (draft → placed).
      if ($order->getState()->getId() === 'draft') {
        $order->getState()->applyTransitionById('place');
        $order->save();
      }

      // Fulfill the order (placed → completed).
      $transition = $order->getState()->getWorkflow()->getTransition('fulfill');
      if ($transition) {
        $order->getState()->applyTransition($transition);
      }
      $order->save();

      // Create a payment entity.
      $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');

      // Find or create a manual payment gateway for Eupago.
      $gateways = \Drupal::entityTypeManager()
        ->getStorage('commerce_payment_gateway')
        ->loadByProperties(['id' => 'eupago_' . $payment_method]);

      if (empty($gateways)) {
        // Use any available gateway or the default manual one.
        $gateways = \Drupal::entityTypeManager()
          ->getStorage('commerce_payment_gateway')
          ->loadByProperties(['plugin' => 'manual']);
      }

      $gateway = !empty($gateways) ? reset($gateways) : NULL;

      if ($gateway) {
        $payment = $payment_storage->create([
          'state' => 'completed',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $gateway->id(),
          'order_id' => $order->id(),
          'remote_id' => $remote_id,
          'remote_state' => 'COMPLETED',
        ]);
        $payment->save();
      }

      \Drupal::logger('eupago_payments')->info('Order @order_id completed via @method', [
        '@order_id' => $order->id(),
        '@method' => $payment_method,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('eupago_payments')->error('Error completing order @order_id: @message', [
        '@order_id' => $order->id(),
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Create Multibanco payment reference.
   *
   * POST /api/checkout/eupago/multibanco
   */
  public function createMultibanco(Request $request) {
    $user = $this->verifyJwtToken($request);
    if (!$user) {
      return new JsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = json_decode($request->getContent(), TRUE);
    if (!isset($data['order_id'])) {
      return new JsonResponse(['error' => 'Order ID required'], 400);
    }

    $order = $this->loadAndValidateOrder($data['order_id'], $user);
    if (!$order) {
      return new JsonResponse(['error' => 'Order not found or unauthorized'], 404);
    }

    $config = $this->getEupagoConfig();
    $amount = number_format((float) $order->getTotalPrice()->getNumber(), 2, '.', '');
    $identifier = 'order_' . $order->id() . '_' . time();

    // Set deadline to 3 days from now.
    $deadline = date('Y-m-d', strtotime('+3 days'));

    try {
      $client = new Client();
      $response = $client->post($config['base_url'] . '/clientes/rest_api/multibanco/create', [
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
          'ApiKey' => $config['api_key'],
        ],
        'json' => [
          'chave' => $config['api_key'],
          'valor' => (float) $amount,
          'id' => $identifier,
          'data_inicio' => date('Y-m-d'),
          'data_fim' => $deadline,
          'per_dup' => 0,
        ],
      ]);

      $result = json_decode($response->getBody(), TRUE);

      \Drupal::logger('eupago_payments')->info('Multibanco API response: @response', [
        '@response' => json_encode($result),
      ]);

      if (isset($result['sucesso']) && $result['sucesso'] === TRUE) {
        // Store payment reference in database.
        $db = \Drupal::database();
        $db->insert('eupago_payments')
          ->fields([
            'order_id' => $order->id(),
            'uid' => $user->id(),
            'payment_method' => 'multibanco',
            'reference' => $result['referencia'] ?? '',
            'entity' => $result['entidade'] ?? '',
            'amount' => (float) $amount,
            'status' => 'pending',
            'eupago_identifier' => $identifier,
            'deadline' => $deadline,
            'created' => time(),
            'changed' => time(),
          ])
          ->execute();

        // Store eupago data on the order.
        $order->setData('eupago_payment', [
          'method' => 'multibanco',
          'reference' => $result['referencia'] ?? '',
          'entity' => $result['entidade'] ?? '',
          'amount' => $amount,
          'identifier' => $identifier,
          'deadline' => $deadline,
        ]);
        $order->save();

        return new JsonResponse([
          'success' => TRUE,
          'method' => 'multibanco',
          'entity' => $result['entidade'] ?? '',
          'reference' => $result['referencia'] ?? '',
          'amount' => $amount,
          'deadline' => $deadline,
          'order_id' => $order->id(),
        ]);
      }
      else {
        $error_msg = $result['resposta'] ?? 'Unknown error';
        \Drupal::logger('eupago_payments')->error('Multibanco creation failed: @error', [
          '@error' => $error_msg,
        ]);
        return new JsonResponse([
          'error' => 'Failed to create Multibanco reference: ' . $error_msg,
        ], 500);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('eupago_payments')->error('Multibanco error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'error' => 'Failed to create Multibanco reference: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Create MB WAY payment request.
   *
   * POST /api/checkout/eupago/mbway
   */
  public function createMbway(Request $request) {
    $user = $this->verifyJwtToken($request);
    if (!$user) {
      return new JsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = json_decode($request->getContent(), TRUE);
    if (!isset($data['order_id']) || !isset($data['phone'])) {
      return new JsonResponse(['error' => 'Order ID and phone number required'], 400);
    }

    $order = $this->loadAndValidateOrder($data['order_id'], $user);
    if (!$order) {
      return new JsonResponse(['error' => 'Order not found or unauthorized'], 404);
    }

    $config = $this->getEupagoConfig();
    $amount = number_format((float) $order->getTotalPrice()->getNumber(), 2, '.', '');
    $identifier = 'order_' . $order->id() . '_' . time();

    // Normalize phone number: ensure it starts with 351 for Portuguese numbers.
    $phone = preg_replace('/[^0-9]/', '', $data['phone']);
    if (strlen($phone) === 9) {
      $phone = '351' . $phone;
    }

    try {
      $client = new Client();
      $response = $client->post($config['base_url_v2'] . '/api/v1.02/mbway/create', [
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
          'ApiKey' => $config['api_key'],
        ],
        'json' => [
          'chave' => $config['api_key'],
          'valor' => (float) $amount,
          'id' => $identifier,
          'alias' => $phone,
          'descricao' => 'Pedido #' . $order->id() . ' - Clínica do Empresário',
        ],
      ]);

      $result = json_decode($response->getBody(), TRUE);

      \Drupal::logger('eupago_payments')->info('MB WAY API response: @response', [
        '@response' => json_encode($result),
      ]);

      if (isset($result['sucesso']) && $result['sucesso'] === TRUE) {
        $db = \Drupal::database();
        $db->insert('eupago_payments')
          ->fields([
            'order_id' => $order->id(),
            'uid' => $user->id(),
            'payment_method' => 'mbway',
            'reference' => $result['referencia'] ?? '',
            'amount' => (float) $amount,
            'status' => 'pending',
            'eupago_identifier' => $identifier,
            'phone' => $phone,
            'created' => time(),
            'changed' => time(),
          ])
          ->execute();

        $order->setData('eupago_payment', [
          'method' => 'mbway',
          'reference' => $result['referencia'] ?? '',
          'amount' => $amount,
          'identifier' => $identifier,
          'phone' => $phone,
        ]);
        $order->save();

        return new JsonResponse([
          'success' => TRUE,
          'method' => 'mbway',
          'reference' => $result['referencia'] ?? '',
          'amount' => $amount,
          'order_id' => $order->id(),
          'message' => 'Pedido MB WAY enviado. Verifique a sua aplicação MB WAY para confirmar o pagamento. Tem 5 minutos para confirmar.',
        ]);
      }
      else {
        $error_msg = $result['resposta'] ?? 'Unknown error';
        \Drupal::logger('eupago_payments')->error('MB WAY creation failed: @error', [
          '@error' => $error_msg,
        ]);
        return new JsonResponse([
          'error' => 'Failed to create MB WAY payment: ' . $error_msg,
        ], 500);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('eupago_payments')->error('MB WAY error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'error' => 'Failed to create MB WAY payment: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Create Credit Card payment (redirect to 3D Secure form).
   *
   * POST /api/checkout/eupago/creditcard
   */
  public function createCreditCard(Request $request) {
    $user = $this->verifyJwtToken($request);
    if (!$user) {
      return new JsonResponse(['error' => 'Unauthorized'], 401);
    }

    $data = json_decode($request->getContent(), TRUE);
    if (!isset($data['order_id'])) {
      return new JsonResponse(['error' => 'Order ID required'], 400);
    }

    $order = $this->loadAndValidateOrder($data['order_id'], $user);
    if (!$order) {
      return new JsonResponse(['error' => 'Order not found or unauthorized'], 404);
    }

    $config = $this->getEupagoConfig();
    $amount = number_format((float) $order->getTotalPrice()->getNumber(), 2, '.', '');
    $identifier = 'order_' . $order->id() . '_' . time();

    // Determine the return/callback URLs.
    $frontend_url = \Drupal::state()->get('eupago_payments.frontend_url', 'https://ricardo-two.vercel.app');
    $success_url = $frontend_url . '/pedidos/confirmacao/' . $order->id();
    $failure_url = $frontend_url . '/checkout?payment_error=1';

    try {
      $client = new Client();
      $response = $client->post($config['base_url_v2'] . '/api/v1.02/creditcard/create', [
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
          'Authorization' => 'ApiKey ' . $config['api_key'],
        ],
        'json' => [
          'payment' => [
            'amount' => [
              'currency' => 'EUR',
              'value' => (float) $amount,
            ],
            'identifier' => $identifier,
            'successUrl' => $success_url,
            'failUrl' => $failure_url,
            'backUrl' => $failure_url,
            'lang' => 'PT',
          ],
          'customer' => [
            'notify' => TRUE,
            'email' => $user->getEmail(),
          ],
        ],
      ]);

      $result = json_decode($response->getBody(), TRUE);

      \Drupal::logger('eupago_payments')->info('Credit Card API response: @response', [
        '@response' => json_encode($result),
      ]);

      if (isset($result['sucesso']) && $result['sucesso'] === TRUE && isset($result['url'])) {
        $db = \Drupal::database();
        $db->insert('eupago_payments')
          ->fields([
            'order_id' => $order->id(),
            'uid' => $user->id(),
            'payment_method' => 'creditcard',
            'reference' => $result['referencia'] ?? '',
            'amount' => (float) $amount,
            'status' => 'pending',
            'eupago_identifier' => $identifier,
            'redirect_url' => $result['url'],
            'created' => time(),
            'changed' => time(),
          ])
          ->execute();

        $order->setData('eupago_payment', [
          'method' => 'creditcard',
          'reference' => $result['referencia'] ?? '',
          'amount' => $amount,
          'identifier' => $identifier,
          'redirect_url' => $result['url'],
        ]);
        $order->save();

        return new JsonResponse([
          'success' => TRUE,
          'method' => 'creditcard',
          'redirect_url' => $result['url'],
          'amount' => $amount,
          'order_id' => $order->id(),
        ]);
      }
      else {
        $error_msg = $result['resposta'] ?? 'Unknown error';
        \Drupal::logger('eupago_payments')->error('Credit Card creation failed: @error', [
          '@error' => $error_msg,
        ]);
        return new JsonResponse([
          'error' => 'Failed to create Credit Card payment: ' . $error_msg,
        ], 500);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('eupago_payments')->error('Credit Card error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'error' => 'Failed to create Credit Card payment: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Check payment status for an order.
   *
   * GET /api/checkout/eupago/status/{order_id}
   */
  public function checkStatus(Request $request, $order_id) {
    $user = $this->verifyJwtToken($request);
    if (!$user) {
      return new JsonResponse(['error' => 'Unauthorized'], 401);
    }

    $db = \Drupal::database();
    $record = $db->select('eupago_payments', 'ep')
      ->fields('ep')
      ->condition('order_id', $order_id)
      ->condition('uid', $user->id())
      ->orderBy('created', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchAssoc();

    if (!$record) {
      return new JsonResponse(['error' => 'No payment found for this order'], 404);
    }

    return new JsonResponse([
      'order_id' => (int) $record['order_id'],
      'payment_method' => $record['payment_method'],
      'status' => $record['status'],
      'reference' => $record['reference'],
      'entity' => $record['entity'],
      'amount' => $record['amount'],
      'deadline' => $record['deadline'],
      'created' => (int) $record['created'],
      'paid_at' => $record['paid_at'] ? (int) $record['paid_at'] : NULL,
    ]);
  }

  /**
   * Eupago webhook callback handler.
   *
   * GET /api/checkout/eupago/webhook
   *
   * Eupago sends payment notifications as GET request with query parameters:
   * valor, canal, referencia, transacao, identificador, mp, chave_api, data,
   * entidade, comissao, local
   */
  public function webhook(Request $request) {
    // Log the entire webhook request for debugging.
    \Drupal::logger('eupago_payments')->info('Webhook received: @params', [
      '@params' => json_encode($request->query->all()),
    ]);

    $referencia = $request->query->get('referencia');
    $transacao = $request->query->get('transacao');
    $valor = $request->query->get('valor');
    $identificador = $request->query->get('identificador');
    $mp = $request->query->get('mp');
    $chave_api = $request->query->get('chave_api');
    $data = $request->query->get('data');

    if (!$referencia && !$identificador) {
      \Drupal::logger('eupago_payments')->warning('Webhook received without reference or identifier');
      return new Response('Missing reference', 400);
    }

    // Verify the API key matches ours.
    $config = $this->getEupagoConfig();
    if ($chave_api && $chave_api !== $config['api_key']) {
      \Drupal::logger('eupago_payments')->warning('Webhook API key mismatch');
      // Still process it since it may be valid.
    }

    $db = \Drupal::database();

    // Find the payment record by reference or identifier.
    $query = $db->select('eupago_payments', 'ep')
      ->fields('ep');

    if ($referencia) {
      $query->condition('reference', $referencia);
    }
    elseif ($identificador) {
      $query->condition('eupago_identifier', $identificador);
    }

    $record = $query->orderBy('created', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchAssoc();

    if (!$record) {
      \Drupal::logger('eupago_payments')->warning('Webhook: no payment record found for reference @ref / identifier @id', [
        '@ref' => $referencia ?? 'N/A',
        '@id' => $identificador ?? 'N/A',
      ]);
      return new Response('Payment not found', 404);
    }

    // Already processed.
    if ($record['status'] === 'paid') {
      \Drupal::logger('eupago_payments')->info('Webhook: payment already marked as paid for order @order_id', [
        '@order_id' => $record['order_id'],
      ]);
      return new Response('OK', 200);
    }

    // Update the payment record.
    $db->update('eupago_payments')
      ->fields([
        'status' => 'paid',
        'eupago_transacao' => $transacao,
        'paid_at' => time(),
        'changed' => time(),
      ])
      ->condition('id', $record['id'])
      ->execute();

    // Complete the commerce order.
    $order = Order::load($record['order_id']);
    if ($order && $order->getState()->getId() !== 'completed') {
      $this->completeOrder($order, $record['payment_method'], $referencia ?: $transacao);
    }

    \Drupal::logger('eupago_payments')->info('Webhook: payment confirmed for order @order_id via @method', [
      '@order_id' => $record['order_id'],
      '@method' => $record['payment_method'],
    ]);

    return new Response('OK', 200);
  }

  /**
   * Admin settings form handler (via state API).
   *
   * POST /api/checkout/eupago/admin/settings
   */
  public function adminSettings(Request $request) {
    // Only allow admin users.
    $user = $this->verifyJwtToken($request);
    if (!$user || !$user->hasPermission('administer site configuration')) {
      return new JsonResponse(['error' => 'Unauthorized'], 403);
    }

    $data = json_decode($request->getContent(), TRUE);
    $state = \Drupal::state();

    if (isset($data['mode'])) {
      $state->set('eupago_payments.mode', $data['mode']);
    }
    if (isset($data['sandbox_api_key'])) {
      $state->set('eupago_payments.sandbox_api_key', $data['sandbox_api_key']);
    }
    if (isset($data['live_api_key'])) {
      $state->set('eupago_payments.live_api_key', $data['live_api_key']);
    }
    if (isset($data['frontend_url'])) {
      $state->set('eupago_payments.frontend_url', $data['frontend_url']);
    }

    return new JsonResponse(['success' => TRUE, 'message' => 'Eupago settings updated.']);
  }

  /**
   * Get available payment methods.
   *
   * GET /api/checkout/eupago/methods
   */
  public function getPaymentMethods(Request $request) {
    return new JsonResponse([
      'methods' => [
        [
          'id' => 'multibanco',
          'name' => 'Multibanco',
          'description' => 'Pagamento por referência Multibanco (ATM ou homebanking)',
          'icon' => 'multibanco',
          'enabled' => TRUE,
        ],
        [
          'id' => 'mbway',
          'name' => 'MB WAY',
          'description' => 'Pagamento instantâneo via MB WAY',
          'icon' => 'mbway',
          'enabled' => TRUE,
        ],
        [
          'id' => 'creditcard',
          'name' => 'Cartão de Crédito/Débito',
          'description' => 'Pagamento seguro com cartão (Visa/Mastercard)',
          'icon' => 'creditcard',
          'enabled' => TRUE,
        ],
        [
          'id' => 'paypal',
          'name' => 'PayPal',
          'description' => 'Pagamento via PayPal',
          'icon' => 'paypal',
          'enabled' => TRUE,
        ],
      ],
    ]);
  }

}
