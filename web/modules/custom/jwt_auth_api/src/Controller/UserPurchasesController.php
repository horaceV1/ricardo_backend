<?php

namespace Drupal\jwt_auth_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_order\Entity\Order;

/**
 * Returns user's purchased products.
 */
class UserPurchasesController extends ControllerBase {

  /**
   * Get current user's purchased products.
   */
  public function getPurchases(Request $request) {
    $current_user = \Drupal::currentUser();
    
    if ($current_user->isAnonymous()) {
      return new JsonResponse(['error' => 'Unauthorized'], 401);
    }

    try {
      // Load all completed orders for the current user
      $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
      $query = $order_storage->getQuery()
        ->condition('uid', $current_user->id())
        ->condition('state', 'completed')
        ->sort('completed', 'DESC')
        ->accessCheck(TRUE);
      
      $order_ids = $query->execute();
      
      if (empty($order_ids)) {
        return new JsonResponse(['data' => []]);
      }

      $orders = $order_storage->loadMultiple($order_ids);
      $purchased_products = [];

      foreach ($orders as $order) {
        foreach ($order->getItems() as $order_item) {
          $purchased_product = $order_item->getPurchasedEntity();
          
          if (!$purchased_product) {
            continue;
          }

          // Get the product from the variation
          $product = $purchased_product->getProduct();
          
          if (!$product) {
            continue;
          }

          // Check if variation has digital media field (commerce_file field for license downloads)
          $media_files = [];
          
          // Check for commerce_file field on variation (used by Commerce File/License modules)
          if ($purchased_product->hasField('commerce_file') && !$purchased_product->get('commerce_file')->isEmpty()) {
            foreach ($purchased_product->get('commerce_file') as $file_item) {
              $file = $file_item->entity;
              if ($file) {
                $media_files[] = [
                  'fid' => $file->id(),
                  'filename' => $file->getFilename(),
                  'filesize' => $file->getSize(),
                  'mime_type' => $file->getMimeType(),
                  'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
                  'title' => $file->getFilename(),
                ];
              }
            }
          }
          
          // Also check product for field_digital_media if it exists
          if ($product->hasField('field_digital_media') && !$product->get('field_digital_media')->isEmpty()) {
            foreach ($product->get('field_digital_media') as $media_item) {
              $media = $media_item->entity;
              if ($media && $media->hasField('field_media_file') && !$media->get('field_media_file')->isEmpty()) {
                $file = $media->get('field_media_file')->entity;
                if ($file) {
                  $media_files[] = [
                    'fid' => $file->id(),
                    'filename' => $file->getFilename(),
                    'filesize' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
                    'title' => $media->label(),
                  ];
                }
              }
            }
          }

          // Build product data
          $product_id = $product->id();
          
          // Avoid duplicates
          if (isset($purchased_products[$product_id])) {
            continue;
          }

          $product_data = [
            'product_id' => $product_id,
            'title' => $product->getTitle(),
            'variation_id' => $purchased_product->id(),
            'order_id' => $order->id(),
            'order_number' => $order->getOrderNumber(),
            'purchased_date' => $order->getCompletedTime() ? date('Y-m-d\TH:i:s', $order->getCompletedTime()) : $order->getPlacedTime()->format('Y-m-d\TH:i:s'),
          ];

          // Add product fields
          if ($product->hasField('body') && !$product->get('body')->isEmpty()) {
            $product_data['body'] = [
              'value' => $product->get('body')->value,
              'summary' => $product->get('body')->summary,
            ];
          }

          // Add product image
          if ($product->hasField('field_image') && !$product->get('field_image')->isEmpty()) {
            $image = $product->get('field_image')->entity;
            if ($image) {
              $product_data['image'] = [
                'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($image->getFileUri()),
                'alt' => $product->get('field_image')->alt,
              ];
            }
          }

          // Add digital media files
          $product_data['digital_media'] = $media_files;
          $product_data['has_downloads'] = !empty($media_files);

          $purchased_products[$product_id] = $product_data;
        }
      }

      return new JsonResponse([
        'data' => array_values($purchased_products),
      ]);

    } catch (\Exception $e) {
      \Drupal::logger('jwt_auth_api')->error('Error fetching user purchases: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Failed to fetch purchases'], 500);
    }
  }

}
