<?php

/**
 * Check if field_curso exists and link products to courses
 * Run via: drush php-script check_field_and_link.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\commerce_product\Entity\Product;
use Drupal\node\Entity\Node;

// Check if field exists
echo "=== Checking field_curso ===" . PHP_EOL;
$storage = FieldStorageConfig::loadByName('commerce_product', 'field_curso');
if ($storage) {
  echo "✓ Field storage exists" . PHP_EOL;
} else {
  echo "✗ Field storage does NOT exist - run create_field_curso.php first!" . PHP_EOL;
  exit(1);
}

$field = FieldConfig::loadByName('commerce_product', 'media', 'field_curso');
if ($field) {
  echo "✓ Field instance exists on media bundle" . PHP_EOL;
} else {
  echo "✗ Field instance does NOT exist on media bundle" . PHP_EOL;
  exit(1);
}

// Find all products
echo PHP_EOL . "=== Checking Products ===" . PHP_EOL;
$product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
$products = $product_storage->loadByProperties(['type' => 'media']);

if (empty($products)) {
  echo "No media products found" . PHP_EOL;
  exit(0);
}

foreach ($products as $product) {
  echo PHP_EOL . "Product: {$product->getTitle()} (ID: {$product->id()})" . PHP_EOL;
  
  if ($product->hasField('field_curso')) {
    if (!$product->get('field_curso')->isEmpty()) {
      $curso = $product->get('field_curso')->entity;
      if ($curso) {
        echo "  ✓ Linked to curso: {$curso->getTitle()} (NID: {$curso->id()})" . PHP_EOL;
      } else {
        echo "  ✗ field_curso has value but node doesn't exist" . PHP_EOL;
      }
    } else {
      echo "  ⚠ field_curso exists but is EMPTY - needs to be linked!" . PHP_EOL;
      
      // Try to auto-link if product name matches a curso
      $product_title = strtolower($product->getTitle());
      if (strpos($product_title, 'curso') !== false) {
        // Search for matching article nodes
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $query = $node_storage->getQuery()
          ->condition('type', 'article')
          ->condition('status', 1)
          ->accessCheck(FALSE);
        $nids = $query->execute();
        
        if (!empty($nids)) {
          $nodes = $node_storage->loadMultiple($nids);
          foreach ($nodes as $node) {
            // Check if this is a parent curso (has children)
            if ($node->hasField('parent') && $node->get('parent')->isEmpty()) {
              echo "  → Found parent curso: {$node->getTitle()} (NID: {$node->id()})" . PHP_EOL;
              echo "  → Auto-linking..." . PHP_EOL;
              $product->set('field_curso', $node->id());
              $product->save();
              echo "  ✓ Linked successfully!" . PHP_EOL;
              break;
            }
          }
        }
      }
    }
  } else {
    echo "  ✗ Product does NOT have field_curso!" . PHP_EOL;
  }
}

// Check recent orders
echo PHP_EOL . "=== Checking Recent Orders ===" . PHP_EOL;
$order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
$query = $order_storage->getQuery()
  ->condition('type', 'default')
  ->condition('state', 'completed')
  ->sort('completed', 'DESC')
  ->range(0, 5)
  ->accessCheck(FALSE);
$order_ids = $query->execute();

if (empty($order_ids)) {
  echo "No completed orders found" . PHP_EOL;
} else {
  $orders = $order_storage->loadMultiple($order_ids);
  foreach ($orders as $order) {
    echo PHP_EOL . "Order #{$order->getOrderNumber()} (ID: {$order->id()})" . PHP_EOL;
    echo "  Customer: User #{$order->getCustomerId()}" . PHP_EOL;
    echo "  State: {$order->getState()->getId()}" . PHP_EOL;
    echo "  Completed: " . ($order->getCompletedTime() ? date('Y-m-d H:i:s', $order->getCompletedTime()) : 'Not completed') . PHP_EOL;
    echo "  Items:" . PHP_EOL;
    foreach ($order->getItems() as $item) {
      $purchased_entity = $item->getPurchasedEntity();
      if ($purchased_entity) {
        echo "    - {$item->getTitle()} (Variation ID: {$purchased_entity->id()})" . PHP_EOL;
        $product = $purchased_entity->getProduct();
        if ($product && $product->hasField('field_curso') && !$product->get('field_curso')->isEmpty()) {
          $curso = $product->get('field_curso')->entity;
          if ($curso) {
            echo "      ✓ Has curso: {$curso->getTitle()}" . PHP_EOL;
          }
        } else {
          echo "      ✗ NO CURSO LINKED!" . PHP_EOL;
        }
      }
    }
  }
}

echo PHP_EOL . "=== Done ===" . PHP_EOL;
