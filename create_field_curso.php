<?php

/**
 * Script to create field_curso on commerce_product entity.
 * Run this via: drush php-script create_field_curso.php
 * Or upload and access via browser (delete after use!)
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

// Bootstrap Drupal if running standalone
if (PHP_SAPI !== 'cli') {
  $autoloader = require_once 'autoload.php';
  \Drupal\Core\DrupalKernel::createFromRequest(
    \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
    $autoloader,
    'prod'
  )->boot();
}

try {
  // Check if field storage already exists
  $storage = FieldStorageConfig::loadByName('commerce_product', 'field_curso');
  
  if (!$storage) {
    echo "Creating field storage...\n";
    $storage = FieldStorageConfig::create([
      'field_name' => 'field_curso',
      'entity_type' => 'commerce_product',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'node'],
      'cardinality' => 1,
    ]);
    $storage->save();
    echo "✓ Field storage created\n";
  } else {
    echo "✓ Field storage already exists\n";
  }

  // Create field for media bundle
  $field = FieldConfig::loadByName('commerce_product', 'media', 'field_curso');
  
  if (!$field) {
    echo "Creating field instance for media bundle...\n";
    $field = FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'media',
      'label' => 'Curso Associado',
      'description' => 'Link this product to a course (Article node with children)',
      'required' => FALSE,
      'settings' => [
        'handler' => 'default:node',
        'handler_settings' => [
          'target_bundles' => ['article' => 'article'],
          'sort' => [
            'field' => '_none',
          ],
          'auto_create' => FALSE,
        ],
      ],
    ]);
    $field->save();
    echo "✓ Field instance created for media bundle\n";
  } else {
    echo "✓ Field instance already exists for media bundle\n";
  }

  // Add to form display
  $form_display = EntityFormDisplay::load('commerce_product.media.default');
  if ($form_display) {
    echo "Configuring form display...\n";
    $form_display->setComponent('field_curso', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 10,
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'placeholder' => '',
      ],
    ])->save();
    echo "✓ Form display configured\n";
  }

  // Also create for default bundle if it exists
  $field_default = FieldConfig::loadByName('commerce_product', 'default', 'field_curso');
  if (!$field_default) {
    echo "Creating field instance for default bundle...\n";
    $field_default = FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'default',
      'label' => 'Curso Associado',
      'description' => 'Link this product to a course (Article node with children)',
      'required' => FALSE,
      'settings' => [
        'handler' => 'default:node',
        'handler_settings' => [
          'target_bundles' => ['article' => 'article'],
          'sort' => [
            'field' => '_none',
          ],
          'auto_create' => FALSE,
        ],
      ],
    ]);
    $field_default->save();
    echo "✓ Field instance created for default bundle\n";
    
    // Add to form display for default bundle
    $form_display_default = EntityFormDisplay::load('commerce_product.default.default');
    if ($form_display_default) {
      $form_display_default->setComponent('field_curso', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 10,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])->save();
      echo "✓ Form display configured for default bundle\n";
    }
  }

  // Clear cache
  drupal_flush_all_caches();
  echo "\n✓ All done! Cache cleared.\n";
  echo "\nYou can now edit your product and see the 'Curso Associado' field.\n";

} catch (Exception $e) {
  echo "✗ Error: " . $e->getMessage() . "\n";
  exit(1);
}
