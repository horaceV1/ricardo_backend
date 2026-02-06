<?php

namespace Drupal\jwt_auth_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_order\Entity\Order;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns a single order by ID.
 */
class SingleOrderController extends ControllerBase {

  /**
   * Get single order.
   */
  public function getOrder(Request $request, $order_id) {
    $current_user = \Drupal::currentUser();
    
    if ($current_user->isAnonymous()) {
      return new JsonResponse(['error' => 'Not authenticated'], 401);
    }

    $order = Order::load($order_id);
    
    if (!$order) {
      return new JsonResponse(['error' => 'Order not found'], 404);
    }

    // Verify order belongs to current user
    if ($order->getCustomerId() != $current_user->id()) {
      return new JsonResponse(['error' => 'Unauthorized'], 403);
    }

    $order_data = [
      'order_id' => $order->id(),
      'order_number' => $order->getOrderNumber(),
      'state' => $order->getState()->getId(),
      'total_price' => [
        'number' => $order->getTotalPrice()->getNumber(),
        'currency_code' => $order->getTotalPrice()->getCurrencyCode(),
      ],
      'placed' => $order->getPlacedTime() ? date('c', $order->getPlacedTime()) : null,
      'completed' => $order->getCompletedTime() ? date('c', $order->getCompletedTime()) : null,
      'order_items' => [],
      'customer' => [
        'name' => $order->getCustomer() ? $order->getCustomer()->getDisplayName() : 'Guest',
        'mail' => $order->getEmail(),
      ],
    ];

    foreach ($order->getItems() as $item) {
      $order_data['order_items'][] = [
        'title' => $item->getTitle(),
        'quantity' => (int) $item->getQuantity(),
        'unit_price' => [
          'number' => $item->getUnitPrice()->getNumber(),
          'currency_code' => $item->getUnitPrice()->getCurrencyCode(),
        ],
        'total_price' => [
          'number' => $item->getTotalPrice()->getNumber(),
          'currency_code' => $item->getTotalPrice()->getCurrencyCode(),
        ],
        'purchased_entity_id' => $item->getPurchasedEntityId(),
      ];
    }

    return new JsonResponse($order_data);
  }

}
