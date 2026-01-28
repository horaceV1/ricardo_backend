<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Commerce Cart API routes.
 */
class CommerceCartApiController extends ControllerBase {

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * Constructs a new CommerceCartApiController object.
   *
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   */
  public function __construct(CartProviderInterface $cart_provider, CartManagerInterface $cart_manager) {
    $this->cartProvider = $cart_provider;
    $this->cartManager = $cart_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_cart.cart_manager')
    );
  }

  /**
   * Add CORS headers to response.
   */
  protected function addCorsHeaders(JsonResponse $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    $response->headers->set('Access-Control-Allow-Credentials', 'true');
    return $response;
  }

  /**
   * Get the current user's cart.
   */
  public function getCart(Request $request = NULL) {
    // Handle OPTIONS preflight request
    if ($request && $request->getMethod() === 'OPTIONS') {
      $response = new JsonResponse(NULL, 204);
      return $this->addCorsHeaders($response);
    }

    $store = \Drupal::entityTypeManager()->getStorage('commerce_store')->loadDefault();
    if (!$store) {
      $response = new JsonResponse(['error' => 'No store available'], 404);
      return $this->addCorsHeaders($response);
    }

    $cart = $this->cartProvider->getCart('default', $store);
    
    if (!$cart) {
      $response = new JsonResponse([
        'order_id' => null,
        'order_number' => null,
        'total_price' => [
          'number' => '0',
          'currency_code' => $store->getDefaultCurrencyCode(),
        ],
        'order_items' => [],
      ]);
      return $this->addCorsHeaders($response);
    }

    $order_items = [];
    foreach ($cart->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      $order_items[] = [
        'order_item_id' => $order_item->id(),
        'purchased_entity_id' => $purchased_entity ? $purchased_entity->id() : null,
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

    $response = new JsonResponse([
      'order_id' => $cart->id(),
      'order_number' => $cart->getOrderNumber(),
      'total_price' => [
        'number' => $cart->getTotalPrice()->getNumber(),
        'currency_code' => $cart->getTotalPrice()->getCurrencyCode(),
      ],
      'order_items' => $order_items,
    ]);
    return $this->addCorsHeaders($response);
  }

  /**
   * Add item to cart.
   */
  public function addToCart(Request $request) {
    // Handle OPTIONS preflight request
    if ($request->getMethod() === 'OPTIONS') {
      $response = new JsonResponse(NULL, 204);
      return $this->addCorsHeaders($response);
    }

    $data = json_decode($request->getContent(), TRUE);
    
    if (!isset($data[0]['purchased_entity_id']) || !isset($data[0]['quantity'])) {
      $response = new JsonResponse(['error' => 'Invalid data', 'received' => $data], 400);
      return $this->addCorsHeaders($response);
    }

    $variation_id = $data[0]['purchased_entity_id'];
    $quantity = (int) $data[0]['quantity'];

    // Try loading by UUID using entity repository
    $variation = NULL;
    if (strpos($variation_id, '-') !== FALSE) {
      // It's a UUID - use entity repository
      try {
        $entity_repository = \Drupal::service('entity.repository');
        $variation = $entity_repository->loadEntityByUuid('commerce_product_variation', $variation_id);
      } catch (\Exception $e) {
        \Drupal::logger('commerce_cart')->error('Error loading variation by UUID: @message', [
          '@message' => $e->getMessage(),
        ]);
      }
    } else {
      // It's a numeric ID
      $variation = ProductVariation::load($variation_id);
    }

    if (!$variation) {
      $response = new JsonResponse(['error' => 'Product variation not found', 'variation_id' => $variation_id], 404);
      return $this->addCorsHeaders($response);
    }

    $store = \Drupal::entityTypeManager()->getStorage('commerce_store')->loadDefault();
    if (!$store) {
      $response = new JsonResponse(['error' => 'No store available'], 404);
      return $this->addCorsHeaders($response);
    }

    $cart = $this->cartProvider->getCart('default', $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart('default', $store);
    }

    // Get the correct order item type based on variation type
    $order_item_type = 'default';
    $variation_type = $variation->bundle();
    if ($variation_type === 'media_license_download' || $variation_type === 'media_physical') {
      $order_item_type = 'default';
    }

    $order_item = OrderItem::create([
      'type' => $order_item_type,
      'purchased_entity' => $variation,
      'quantity' => $quantity,
      'unit_price' => $variation->getPrice(),
    ]);
    $order_item->save();

    $this->cartManager->addOrderItem($cart, $order_item, TRUE);

    $response = new JsonResponse(['success' => TRUE, 'cart_id' => $cart->id()]);
    return $this->addCorsHeaders($response);
  }

  /**
   * Remove item from cart.
   */
  public function removeFromCart(Request $request) {
    // Handle OPTIONS preflight request
    if ($request->getMethod() === 'OPTIONS') {
      $response = new JsonResponse(NULL, 204);
      return $this->addCorsHeaders($response);
    }

    $data = json_decode($request->getContent(), TRUE);
    
    if (!isset($data[0]['order_item_id'])) {
      $response = new JsonResponse(['error' => 'Invalid data'], 400);
      return $this->addCorsHeaders($response);
    }

    $order_item_id = $data[0]['order_item_id'];
    $order_item = OrderItem::load($order_item_id);
    
    if (!$order_item) {
      $response = new JsonResponse(['error' => 'Order item not found'], 404);
      return $this->addCorsHeaders($response);
    }

    $store = \Drupal::entityTypeManager()->getStorage('commerce_store')->loadDefault();
    $cart = $this->cartProvider->getCart('default', $store);
    
    if ($cart) {
      $this->cartManager->removeOrderItem($cart, $order_item);
    }

    $response = new JsonResponse(['success' => TRUE]);
    return $this->addCorsHeaders($response);
  }

  /**
   * Update cart item quantity.
   */
  public function updateCartItem(Request $request) {
    // Handle OPTIONS preflight request
    if ($request->getMethod() === 'OPTIONS') {
      $response = new JsonResponse(NULL, 204);
      return $this->addCorsHeaders($response);
    }

    $data = json_decode($request->getContent(), TRUE);
    
    if (!isset($data[0]['order_item_id']) || !isset($data[0]['quantity'])) {
      $response = new JsonResponse(['error' => 'Invalid data'], 400);
      return $this->addCorsHeaders($response);
    }

    $order_item_id = $data[0]['order_item_id'];
    $quantity = (int) $data[0]['quantity'];
    
    $order_item = OrderItem::load($order_item_id);
    if (!$order_item) {
      $response = new JsonResponse(['error' => 'Order item not found'], 404);
      return $this->addCorsHeaders($response);
    }

    $store = \Drupal::entityTypeManager()->getStorage('commerce_store')->loadDefault();
    $cart = $this->cartProvider->getCart('default', $store);
    
    if ($cart) {
      $this->cartManager->updateOrderItem($cart, $order_item, ['quantity' => $quantity]);
    }

    $response = new JsonResponse(['success' => TRUE]);
    return $this->addCorsHeaders($response);
  }

  /**
   * Get order details.
   */
  public function getOrder($order_id) {
    $order = Order::load($order_id);
    
    if (!$order) {
      $response = new JsonResponse(['error' => 'Order not found'], 404);
      return $this->addCorsHeaders($response);
    }

    // Check if current user owns this order
    $current_user = \Drupal::currentUser();
    if ($order->getCustomerId() != $current_user->id() && !$current_user->hasPermission('administer commerce_order')) {
      $response = new JsonResponse(['error' => 'Access denied'], 403);
      return $this->addCorsHeaders($response);
    }

    $order_items = [];
    foreach ($order->getItems() as $order_item) {
      $order_items[] = [
        'title' => $order_item->getTitle(),
        'quantity' => (int) $order_item->getQuantity(),
        'total_price' => [
          'number' => $order_item->getTotalPrice()->getNumber(),
          'currency_code' => $order_item->getTotalPrice()->getCurrencyCode(),
        ],
      ];
    }

    $customer = $order->getCustomer();

    $response = new JsonResponse([
      'order_id' => $order->id(),
      'order_number' => $order->getOrderNumber(),
      'state' => $order->getState()->getId(),
      'total_price' => [
        'number' => $order->getTotalPrice()->getNumber(),
        'currency_code' => $order->getTotalPrice()->getCurrencyCode(),
      ],
      'placed' => date('c', $order->getPlacedTime()),
      'order_items' => $order_items,
      'customer' => [
        'name' => $customer ? $customer->getDisplayName() : '',
        'mail' => $customer ? $customer->getEmail() : '',
      ],
    ]);
    return $this->addCorsHeaders($response);
  }

}
