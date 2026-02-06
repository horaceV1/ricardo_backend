<?php

namespace Drupal\jwt_auth_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_order\Entity\Order;

/**
 * Returns user's order history.
 */
class UserOrdersController extends ControllerBase {

  /**
   * Get current user's order history.
   */
  public function getOrders(Request $request) {
    $current_user = \Drupal::currentUser();
    
    if ($current_user->isAnonymous()) {
      return new JsonResponse(['error' => 'Unauthorized'], 401);
    }

    try {
      // Load all orders for the current user
      $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
      $query = $order_storage->getQuery()
        ->condition('uid', $current_user->id())
        ->condition('type', 'default')
        ->sort('placed', 'DESC')
        ->accessCheck(TRUE);
      
      $order_ids = $query->execute();
      
      if (empty($order_ids)) {
        return new JsonResponse(['data' => []]);
      }

      $orders = $order_storage->loadMultiple($order_ids);
      $orders_data = [];

      foreach ($orders as $order) {
        // Get order items
        $order_items = [];
        foreach ($order->getItems() as $order_item) {
          $order_items[] = [
            'title' => $order_item->getTitle(),
            'quantity' => (int) $order_item->getQuantity(),
            'unit_price' => [
              'number' => $order_item->getUnitPrice()->getNumber(),
              'currency_code' => $order_item->getUnitPrice()->getCurrencyCode(),
            ],
            'total_price' => [
              'number' => $order_item->getTotalPrice()->getNumber(),
              'currency_code' => $order_item->getTotalPrice()->getCurrencyCode(),
            ],
          ];
        }

        // Build order data
        $order_data = [
          'order_id' => $order->id(),
          'order_number' => $order->getOrderNumber(),
          'state' => $order->getState()->getId(),
          'total_price' => [
            'number' => $order->getTotalPrice()->getNumber(),
            'currency_code' => $order->getTotalPrice()->getCurrencyCode(),
          ],
          'placed' => $order->getPlacedTime() ? date('c', $order->getPlacedTime()) : date('c', $order->getCreatedTime()),
          'order_items' => $order_items,
          'customer' => [
            'name' => $order->getCustomer() ? $order->getCustomer()->getDisplayName() : 'Guest',
            'mail' => $order->getEmail(),
          ],
        ];

        // Add completed time if order is completed
        if ($order->getState()->getId() === 'completed' && $order->getCompletedTime()) {
          $order_data['completed'] = date('c', $order->getCompletedTime());
        }

        $orders_data[] = $order_data;
      }

      return new JsonResponse([
        'data' => $orders_data,
        'total' => count($orders_data),
      ]);

    } catch (\Exception $e) {
      \Drupal::logger('jwt_auth_api')->error('Error fetching user orders: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Failed to fetch orders'], 500);
    }
  }

}
