<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for PayPal Checkout API routes.
 */
class PayPalCheckoutApiController extends ControllerBase {

  /**
   * Create PayPal order.
   */
  public function createOrder(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    
    if (!isset($data['order_id'])) {
      return new JsonResponse(['error' => 'Order ID required'], 400);
    }

    $order = Order::load($data['order_id']);
    if (!$order) {
      return new JsonResponse(['error' => 'Order not found'], 404);
    }

    // Get PayPal payment gateway
    $payment_gateways = \Drupal::entityTypeManager()
      ->getStorage('commerce_payment_gateway')
      ->loadByProperties(['plugin' => 'paypal_commerce']);
    
    if (empty($payment_gateways)) {
      return new JsonResponse(['error' => 'PayPal gateway not configured'], 500);
    }

    $payment_gateway = reset($payment_gateways);
    $plugin = $payment_gateway->getPlugin();

    try {
      // Create PayPal order
      $paypal_order = $plugin->createOrder($order);
      
      return new JsonResponse([
        'success' => TRUE,
        'paypal_order_id' => $paypal_order['id'],
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('commerce_paypal')->error('Error creating PayPal order: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Failed to create PayPal order'], 500);
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

    // Get PayPal payment gateway
    $payment_gateways = \Drupal::entityTypeManager()
      ->getStorage('commerce_payment_gateway')
      ->loadByProperties(['plugin' => 'paypal_commerce']);
    
    if (empty($payment_gateways)) {
      return new JsonResponse(['error' => 'PayPal gateway not configured'], 500);
    }

    $payment_gateway = reset($payment_gateways);
    $plugin = $payment_gateway->getPlugin();

    try {
      // Capture PayPal payment
      $capture_result = $plugin->capturePayment($data['paypal_order_id'], $order);
      
      if ($capture_result['status'] === 'COMPLETED') {
        // Update order state
        $order->set('state', 'completed');
        $order->save();
        
        return new JsonResponse([
          'success' => TRUE,
          'order_id' => $order->id(),
          'order_number' => $order->getOrderNumber(),
        ]);
      } else {
        return new JsonResponse(['error' => 'Payment not completed'], 400);
      }
    } catch (\Exception $e) {
      \Drupal::logger('commerce_paypal')->error('Error capturing PayPal payment: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Failed to capture payment'], 500);
    }
  }

}
