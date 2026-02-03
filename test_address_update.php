#!/usr/bin/env php
<?php

/**
 * Test script to simulate frontend address update
 */

// Test data to send
$test_data = [
    'field_first_name' => 'Test',
    'field_last_name' => 'User',
    'field_phone' => '+351912345678',
    'field_address' => 'Rua de Teste, 123',
    'field_city' => 'Lisboa',
    'field_postal_code' => '1000-001',
    'field_country' => 'PT',
];

// Get the JWT token from command line or use a default
$token = $argv[1] ?? '';

if (empty($token)) {
    echo "Usage: php test_address_update.php <JWT_TOKEN>\n";
    echo "\nOr first login to get a token:\n";
    echo "curl -X POST https://darkcyan-stork-408379.hostingersite.com/api/auth/login \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -d '{\"name\":\"your_username\",\"pass\":\"your_password\"}'\n";
    exit(1);
}

// API endpoint
$url = 'https://darkcyan-stork-408379.hostingersite.com/api/auth/profile';

// Initialize cURL
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token,
]);

// Execute request
echo "Sending address update request...\n";
echo "Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    exit(1);
}

curl_close($ch);

// Display response
echo "HTTP Status: $http_code\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n";

if ($http_code == 200) {
    echo "\n✓ Address update successful!\n";
    echo "\nNow check the Drupal admin to verify the customer profile was created:\n";
    echo "https://darkcyan-stork-408379.hostingersite.com/admin/people/profiles\n";
} else {
    echo "\n✗ Address update failed!\n";
}
