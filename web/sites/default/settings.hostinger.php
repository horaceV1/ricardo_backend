<?php

/**
 * Hostinger-specific settings for darkcyan-stork-408379.hostingersite.com
 */

// Database configuration
$databases['default']['default'] = [
  'database' => 'u821792182_clinica',
  'username' => 'u821792182_danielpessoa',
  'password' => 'Drupal.123',
  'host' => 'localhost',
  'port' => '3306',
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];

// Set the base URL
$base_url = 'https://darkcyan-stork-408379.hostingersite.com';

// Trusted host patterns
$settings['trusted_host_patterns'] = [
  '^darkcyan\-stork\-408379\.hostingersite\.com$',
  '^clinicadoempresario\.pt$',
  '^www\.clinicadoempresario\.pt$',
];

// Hash salt - IMPORTANT: Generate a unique one
$settings['hash_salt'] = 'wKdRm8yT9xPzVbQnFhL3jGpYcM5rUvNqWsXt6A4BnCm7HkJfDgEaZwRlPo2IyTx1';

// File paths
$settings['file_public_path'] = 'sites/default/files';
$settings['file_private_path'] = '../private';
$settings['config_sync_directory'] = '../config/sync';

// Disable CSS/JS aggregation for debugging initially
$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;

// Production error settings
$config['system.logging']['error_level'] = 'hide';

// Set PHP settings
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');

// File system settings
$settings['skip_permissions_hardening'] = TRUE;
$settings['file_chmod_directory'] = 0755;
$settings['file_chmod_file'] = 0644;
