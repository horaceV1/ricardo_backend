<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

/**
 * Returns responses for PayPal Checkout API routes.
 */
class PayPalCheckoutApiController extends ControllerBase {

  /**
   * Get PayPal access token.
   */
  private function getPayPalAccessToken($payment_gateway) {
    $config = $payment_gateway->getPlugin()->getConfiguration();
    $mode = $payment_gateway->getPlugin()->getMode();
    
    $client_id = $config['client_id'];
    $secret = $config['secret'];
    
    $base_url = $mode === 'live' 
      ? 'https://api-m.paypal.com' 
      : 'https://api-m.sandbox.paypal.com';
    
    $client = new Client();
    $response = $client->post($base_url . '/v1/oauth2/token', [
      'auth' => [$client_id, $secret],
      'form_params' => [
        'grant_type' => 'client_credentials',
      ],
    ]);
    
    $body = json_decode($response->getBody(), TRUE);
    return $body['access_token'];
  }

  /**
   * Create PayPal order.
   */
  public function createOrder(Request $request) {
    // Log the incoming request
    \Drupal::logger('commerce_paypal')->info('Create order request received');
    
    $data = json_decode($request->getContent(), TRUE);
    
    if (!isset($data['order_id'])) {
      \Drupal::logger('commerce_paypal')->error('Order ID missing from request');
      return new JsonResponse(['error' => 'Order ID required'], 400);
    }

    \Drupal::logger('commerce_paypal')->info('Loading order: @order_id', ['@order_id' => $data['order_id']]);
    
    $order = Order::load($data['order_id']);
    if (!$order) {
      \Drupal::logger('commerce_paypal')->error('Order not found: @order_id', ['@order_id' => $data['order_id']]);
      return new JsonResponse(['error' => 'Order not found'], 404);
    }

    // Get PayPal Checkout payment gateway
    $payment_gateways = \Drupal::entityTypeManager()
      ->getStorage('commerce_payment_gateway')
      ->loadByProperties(['plugin' => 'paypal_checkout']);
    
    if (empty($payment_gateways)) {
      return new JsonResponse(['error' => 'PayPal Checkout gateway not configured'], 500);
    }

    $payment_gateway = reset($payment_gateways);
    $config = $payment_gateway->getPlugin()->getConfiguration();
    $mode = $payment_gateway->getPlugin()->getMode();

    try {
      // Get access token
      $access_token = $this->getPayPalAccessToken($payment_gateway);
      
      $base_url = $mode === 'live' 
        ? 'https://api-m.paypal.com' 
        : 'https://api-m.sandbox.paypal.com';
      
      // Simplified order creation without items array
      // PayPal requires exact amount matching when items are included
      $paypal_order_data = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
          'reference_id' => (string) $order->id(),
          'description' => 'Pedido #' . $order->getOrderNumber(),
          'amount' => [
            'currency_code' => $order->getTotalPrice()->getCurrencyCode(),
            'value' => number_format((float) $order->getTotalPrice()->getNumber(), 2, '.', ''),
          ],
        ]],
        'application_context' => [
          'brand_name' => 'Clínica do Empresário',
          'locale' => 'pt-PT',
          'landing_page' => 'NO_PREFERENCE',
          'shipping_preference' => 'NO_SHIPPING',
          'user_action' => 'PAY_NOW',
        ],
      ];
      
      $client = new Client();
      $response = $client->post($base_url . '/v2/checkout/orders', [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $access_token,
        ],
        'json' => $paypal_order_data,
      ]);
      
      $result = json_decode($response->getBody(), TRUE);
      
      if (isset($result['id'])) {
        // Store PayPal order ID in order data
        $order->setData('paypal_order_id', $result['id']);
        $order->save();
        
        // Return just the ID string for PayPal SDK
        $json_response = new JsonResponse($result['id']);
        $json_response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $json_response;
      } else {
        throw new \Exception('Invalid response from PayPal API');
      }
    } catch (\Exception $e) {
      \Drupal::logger('commerce_paypal')->error('Error creating PayPal order: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Failed to create PayPal order: ' . $e->getMessage()], 500);
    }
  }

  /**
   * Capture PayPal payment.
   */
  public function captureOrder(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    
    if (!isset($data['paypal_order_id']) || !isset($data['order_id'])) {
      return new JsonResponse(['error' => 'PayPal Order ID and Order ID required'], 400);
    }

    $order = Order::load($data['order_id']);
    if (!$order) {
      return new JsonResponse(['error' => 'Order not found'], 404);
    }

    // Get PayPal Checkout payment gateway
    $payment_gateways = \Drupal::entityTypeManager()
      ->getStorage('commerce_payment_gateway')
      ->loadByProperties(['plugin' => 'paypal_checkout']);
    
    if (empty($payment_gateways)) {
      return new JsonResponse(['error' => 'PayPal Checkout gateway not configured'], 500);
    }

    $payment_gateway = reset($payment_gateways);
    $config = $payment_gateway->getPlugin()->getConfiguration();
    $mode = $payment_gateway->getPlugin()->getMode();

    try {
      // Get access token
      $access_token = $this->getPayPalAccessToken($payment_gateway);
      
      $base_url = $mode === 'live' 
        ? 'https://api-m.paypal.com' 
        : 'https://api-m.sandbox.paypal.com';
      
      // Capture the payment
      $client = new Client();
      $response = $client->post($base_url . '/v2/checkout/orders/' . $data['paypal_order_id'] . '/capture', [
        'headers' => [
          'Content-Type' => 'application/json',
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]);
      
      $result = json_decode($response->getBody(), TRUE);
      
      if (isset($result['status']) && $result['status'] === 'COMPLETED') {
        // Update order state
        $order->set('state', 'completed');
        $order->setData('paypal_capture', $result);
        $order->save();
        
        // Create payment entity
        $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
        $payment = $payment_storage->create([
          'state' => 'completed',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $payment_gateway->id(),
          'order_id' => $order->id(),
          'remote_id' => $data['paypal_order_id'],
          'remote_state' => 'COMPLETED',
        ]);
        $payment->save();
        
        return new JsonResponse([
          'success' => TRUE,
          'order_id' => $order->id(),
          'order_number' => $order->getOrderNumber(),
        ]);
      } else {
        return new JsonResponse(['error' => 'Payment not completed', 'status' => $result['status'] ?? 'unknown'], 400);
      }
    } catch (\Exception $e) {
      \Drupal::logger('commerce_paypal')->error('Error capturing PayPal payment: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Failed to capture payment: ' . $e->getMessage()], 500);
    }
  }

}
