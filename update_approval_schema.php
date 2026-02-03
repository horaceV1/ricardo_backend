<?php

/**
 * Run this script to update the approval system database schema.
 * Upload to your server and run: php update_approval_schema.php
 * Or access via browser: https://your-site.com/update_approval_schema.php
 */

use Drupal\Core\Database\Database;

// Bootstrap Drupal
$autoloader = require_once 'autoload.php';
require_once 'web/core/includes/bootstrap.inc';
$kernel = \Drupal\Core\DrupalKernel::createFromRequest(
  \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
  $autoloader,
  'prod'
);
$kernel->boot();
$kernel->prepareLegacyRequest(\Symfony\Component\HttpFoundation\Request::createFromGlobals());

echo "<h1>Updating Approval System Schema</h1>";
echo "<pre>";

try {
  $connection = Database::getConnection();
  
  // Check current schema
  echo "📋 Checking current schema...\n";
  $current_columns = $connection->query("SHOW COLUMNS FROM {dynamic_form_submission}")->fetchAll();
  $has_approval_note = false;
  $has_approval_note_value = false;
  
  foreach ($current_columns as $col) {
    if ($col->Field === 'approval_note') {
      $has_approval_note = true;
      echo "   Found old column: approval_note\n";
    }
    if ($col->Field === 'approval_note__value') {
      $has_approval_note_value = true;
      echo "   Found correct column: approval_note__value\n";
    }
  }
  
  // Step 1: Drop old column if exists
  if ($has_approval_note) {
    echo "\n🗑️  Dropping old approval_note column...\n";
    $connection->query("ALTER TABLE {dynamic_form_submission} DROP COLUMN approval_note");
    echo "   ✅ Dropped\n";
  }
  
  // Step 2: Add new columns
  if (!$has_approval_note_value) {
    echo "\n➕ Adding approval_note__value column...\n";
    $connection->query("ALTER TABLE {dynamic_form_submission} ADD COLUMN approval_note__value LONGTEXT");
    echo "   ✅ Added\n";
    
    echo "\n➕ Adding approval_note__format column...\n";
    $connection->query("ALTER TABLE {dynamic_form_submission} ADD COLUMN approval_note__format VARCHAR(255)");
    echo "   ✅ Added\n";
  } else {
    echo "\n✅ Columns already exist, no changes needed\n";
  }
  
  // Verify final schema
  echo "\n📋 Final schema:\n";
  $final_columns = $connection->query("SHOW COLUMNS FROM {dynamic_form_submission} WHERE Field LIKE 'approval%'")->fetchAll();
  foreach ($final_columns as $col) {
    echo "   ✓ {$col->Field} ({$col->Type})\n";
  }
  
  // Clear cache
  echo "\n🧹 Clearing Drupal cache...\n";
  drupal_flush_all_caches();
  echo "   ✅ Cache cleared\n";
  
  echo "\n✅ Schema update complete!\n";
  echo "\n🔗 Test the approval form at:\n";
  echo "   /admin/structure/dynamic_form_submission/[submission_id]\n";
  
} catch (Exception $e) {
  echo "\n❌ Error: " . $e->getMessage() . "\n";
  echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<p><strong>Remember to delete this file after running it!</strong></p>";
