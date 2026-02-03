<?php

namespace Drupal\jwt_auth_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simple_oauth\Service\Filesystem\FileSystemChecker;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Exception\OAuthServerException;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

/**
 * JWT Authentication Controller.
 */
class JwtAuthController extends ControllerBase {

  /**
   * Login endpoint - returns JWT token.
   */
  public function login(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    
    if (empty($data['username']) || empty($data['password'])) {
      return new JsonResponse(['error' => 'Username and password are required'], 400);
    }

    // Load user by name or email
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => $data['username']]);
    
    if (empty($users)) {
      $users = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadByProperties(['mail' => $data['username']]);
    }

    if (empty($users)) {
      return new JsonResponse(['error' => 'Invalid credentials'], 401);
    }

    $user = reset($users);

    // Check password
    $password_hasher = \Drupal::service('password');
    if (!$password_hasher->check($data['password'], $user->getPassword())) {
      return new JsonResponse(['error' => 'Invalid credentials'], 401);
    }

    // Check if user is blocked
    if (!$user->isActive()) {
      return new JsonResponse(['error' => 'User account is blocked'], 403);
    }

    // Generate JWT token using Simple OAuth
    try {
      $token = $this->generateToken($user);
      
      return new JsonResponse([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => 86400,
        'user' => [
          'uid' => $user->id(),
          'uuid' => $user->uuid(),
          'name' => $user->getAccountName(),
          'mail' => $user->getEmail(),
          'roles' => $user->getRoles(),
          'field_first_name' => $user->hasField('field_first_name') ? $user->get('field_first_name')->value : NULL,
          'field_last_name' => $user->hasField('field_last_name') ? $user->get('field_last_name')->value : NULL,
        ],
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('jwt_auth_api')->error('Token generation failed: @message', ['@message' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Failed to generate token'], 500);
    }
  }

  /**
   * Register endpoint - creates new user and returns JWT token.
   */
  public function register(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    
    if (empty($data['name']) || empty($data['mail']) || empty($data['pass'])) {
      return new JsonResponse(['error' => 'Name, email, and password are required'], 400);
    }

    // Check if username already exists
    $existing_user = user_load_by_name($data['name']);
    if ($existing_user) {
      return new JsonResponse(['error' => 'Username already exists'], 409);
    }

    // Check if email already exists
    $existing_email = user_load_by_mail($data['mail']);
    if ($existing_email) {
      return new JsonResponse(['error' => 'Email already exists'], 409);
    }

    try {
      // Create new user
      $user = User::create([
        'name' => $data['name'],
        'mail' => $data['mail'],
        'pass' => $data['pass'],
        'status' => 1,
        'field_first_name' => $data['field_first_name'] ?? NULL,
        'field_last_name' => $data['field_last_name'] ?? NULL,
      ]);
      
      $user->save();

      // Create user profile
      $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
      $profile = $profile_storage->create([
        'type' => 'user_submissions',
        'uid' => $user->id(),
        'field_first_name' => $data['field_first_name'] ?? NULL,
        'field_last_name' => $data['field_last_name'] ?? NULL,
        'field_phone' => $data['field_phone'] ?? NULL,
        'field_address' => $data['field_address'] ?? NULL,
        'field_city' => $data['field_city'] ?? NULL,
        'field_postal_code' => $data['field_postal_code'] ?? NULL,
        'field_country' => $data['field_country'] ?? NULL,
      ]);
      $profile->save();

      // Create customer profile (address book) with structured address
      if (!empty($data['field_address']) || !empty($data['field_postal_code'])) {
        try {
          $address_data = [
            'country_code' => $data['field_country'] ?? 'PT',
            'address_line1' => $data['field_address'] ?? '',
            'locality' => $data['field_city'] ?? '',
            'postal_code' => $data['field_postal_code'] ?? '',
          ];
          
          // Add name fields if available
          if (!empty($data['field_first_name'])) {
            $address_data['given_name'] = $data['field_first_name'];
          }
          if (!empty($data['field_last_name'])) {
            $address_data['family_name'] = $data['field_last_name'];
          }
          
          \Drupal::logger('jwt_auth_api')->info('Creating customer profile with address data: @data', [
            '@data' => json_encode($address_data),
          ]);
          
          $customer_profile = $profile_storage->create([
            'type' => 'customer',
            'uid' => $user->id(),
            'address' => $address_data,
          ]);
          $customer_profile->save();
          
          \Drupal::logger('jwt_auth_api')->info('Customer profile created successfully with ID: @id', [
            '@id' => $customer_profile->id(),
          ]);
        } catch (\Exception $e) {
          \Drupal::logger('jwt_auth_api')->error('Failed to create customer profile: @message', [
            '@message' => $e->getMessage(),
          ]);
        }
      } else {
        \Drupal::logger('jwt_auth_api')->notice('No address data provided during registration');
      }

      // Generate JWT token
      $token = $this->generateToken($user);
      
      return new JsonResponse([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => 86400,
        'user' => [
          'uid' => $user->id(),
          'uuid' => $user->uuid(),
          'name' => $user->getAccountName(),
          'mail' => $user->getEmail(),
          'roles' => $user->getRoles(),
          'field_first_name' => $user->get('field_first_name')->value,
          'field_last_name' => $user->get('field_last_name')->value,
        ],
      ], 201);
    } catch (\Exception $e) {
      \Drupal::logger('jwt_auth_api')->error('User creation failed: @message', ['@message' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Failed to create user'], 500);
    }
  }

  /**
   * Get current user endpoint.
   */
  public function getUser(Request $request) {
    $user = User::load(\Drupal::currentUser()->id());
    
    if (!$user || $user->isAnonymous()) {
      return new JsonResponse(['error' => 'Not authenticated'], 401);
    }

    // Load user profile to get address fields
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
    $profiles = $profile_storage->loadByProperties([
      'uid' => $user->id(),
      'type' => 'user_submissions',
    ]);
    $profile = !empty($profiles) ? reset($profiles) : NULL;

    $response = [
      'uid' => $user->id(),
      'uuid' => $user->uuid(),
      'name' => $user->getAccountName(),
      'mail' => $user->getEmail(),
      'roles' => $user->getRoles(),
      'created' => date('c', $user->getCreatedTime()),
      'access' => date('c', $user->getLastAccessedTime()),
      'login' => date('c', $user->getLastLoginTime()),
      'status' => $user->isActive(),
      'field_first_name' => $user->hasField('field_first_name') ? $user->get('field_first_name')->value : NULL,
      'field_last_name' => $user->hasField('field_last_name') ? $user->get('field_last_name')->value : NULL,
    ];

    // Add address fields from profile if available
    if ($profile) {
      $response['field_phone'] = $profile->hasField('field_phone') ? $profile->get('field_phone')->value : NULL;
      $response['field_address'] = $profile->hasField('field_address') ? $profile->get('field_address')->value : NULL;
      $response['field_city'] = $profile->hasField('field_city') ? $profile->get('field_city')->value : NULL;
      $response['field_postal_code'] = $profile->hasField('field_postal_code') ? $profile->get('field_postal_code')->value : NULL;
      $response['field_country'] = $profile->hasField('field_country') ? $profile->get('field_country')->value : NULL;
    } else {
      $response['field_phone'] = NULL;
      $response['field_address'] = NULL;
      $response['field_city'] = NULL;
      $response['field_postal_code'] = NULL;
      $response['field_country'] = NULL;
    }

    // Add customer profile (address book) information
    $customer_profiles = $profile_storage->loadByProperties([
      'uid' => $user->id(),
      'type' => 'customer',
    ]);
    
    if (!empty($customer_profiles)) {
      $customer_profile = reset($customer_profiles);
      if ($customer_profile->hasField('address') && !$customer_profile->get('address')->isEmpty()) {
        $address_field = $customer_profile->get('address')->first();
        $response['address_book'] = [
          'country_code' => $address_field->country_code,
          'address_line1' => $address_field->address_line1,
          'address_line2' => $address_field->address_line2,
          'locality' => $address_field->locality,
          'postal_code' => $address_field->postal_code,
          'given_name' => $address_field->given_name,
          'family_name' => $address_field->family_name,
        ];
      }
    }

    return new JsonResponse($response);
  }

  /**
   * Logout endpoint.
   */
  public function logout(Request $request) {
    // For JWT, logout is handled on the client side by removing the token
    // But we can log the event
    \Drupal::logger('jwt_auth_api')->info('User logged out');
    
    return new JsonResponse(['message' => 'Logged out successfully']);
  }

  /**
   * Update user profile endpoint.
   */
  public function updateProfile(Request $request) {
    $current_user = \Drupal::currentUser();
    
    if ($current_user->isAnonymous()) {
      return new JsonResponse(['error' => 'Not authenticated'], 401);
    }

    $data = json_decode($request->getContent(), TRUE);
    
    // Log the incoming data to help with debugging
    \Drupal::logger('jwt_auth_api')->info('Profile update request received for user @uid with data: @data', [
      '@uid' => $current_user->id(),
      '@data' => json_encode($data),
    ]);
    
    try {
      // Load user's profile
      $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
      $profiles = $profile_storage->loadByProperties([
        'uid' => $current_user->id(),
        'type' => 'user_submissions',
      ]);
      
      if (empty($profiles)) {
        // Create profile if it doesn't exist
        $profile = $profile_storage->create([
          'type' => 'user_submissions',
          'uid' => $current_user->id(),
        ]);
      } else {
        $profile = reset($profiles);
      }
      
      // Update all profile fields
      if (isset($data['field_first_name']) && $profile->hasField('field_first_name')) {
        $profile->set('field_first_name', $data['field_first_name']);
      }
      if (isset($data['field_last_name']) && $profile->hasField('field_last_name')) {
        $profile->set('field_last_name', $data['field_last_name']);
      }
      if (isset($data['field_phone']) && $profile->hasField('field_phone')) {
        $profile->set('field_phone', $data['field_phone']);
      }
      if (isset($data['field_address']) && $profile->hasField('field_address')) {
        $profile->set('field_address', $data['field_address']);
      }
      if (isset($data['field_city']) && $profile->hasField('field_city')) {
        $profile->set('field_city', $data['field_city']);
      }
      if (isset($data['field_postal_code']) && $profile->hasField('field_postal_code')) {
        $profile->set('field_postal_code', $data['field_postal_code']);
      }
      if (isset($data['field_country']) && $profile->hasField('field_country')) {
        $profile->set('field_country', $data['field_country']);
      }
      
      $profile->save();

      // Also update or create customer profile (address book) if address fields are provided
      if (isset($data['field_address']) || isset($data['field_postal_code']) || isset($data['field_city'])) {
        try {
          // Load or create customer profile
          $customer_profiles = $profile_storage->loadByProperties([
            'uid' => $current_user->id(),
            'type' => 'customer',
          ]);
          
          \Drupal::logger('jwt_auth_api')->info('Found @count customer profiles for user @uid', [
            '@count' => count($customer_profiles),
            '@uid' => $current_user->id(),
          ]);
          
          if (empty($customer_profiles)) {
            // Create new customer profile
            $customer_profile = $profile_storage->create([
              'type' => 'customer',
              'uid' => $current_user->id(),
            ]);
            \Drupal::logger('jwt_auth_api')->info('Creating new customer profile for user @uid', [
              '@uid' => $current_user->id(),
            ]);
          } else {
            // Use existing customer profile (get the first one)
            $customer_profile = reset($customer_profiles);
            \Drupal::logger('jwt_auth_api')->info('Updating existing customer profile @id', [
              '@id' => $customer_profile->id(),
            ]);
          }
          
          // Build address data
          $address_data = [];
          
          // Get existing address data if available
          if ($customer_profile->hasField('address') && !$customer_profile->get('address')->isEmpty()) {
            $existing_address = $customer_profile->get('address')->first()->toArray();
            $address_data = $existing_address;
          }
          
          // Update with new data
          if (isset($data['field_address'])) {
            $address_data['address_line1'] = $data['field_address'];
          }
          if (isset($data['field_city'])) {
            $address_data['locality'] = $data['field_city'];
          }
          if (isset($data['field_postal_code'])) {
            $address_data['postal_code'] = $data['field_postal_code'];
          }
          if (isset($data['field_country'])) {
            $address_data['country_code'] = $data['field_country'];
          } elseif (empty($address_data['country_code'])) {
            $address_data['country_code'] = 'PT'; // Default to Portugal
          }
          
          // Add name fields if available
          if (isset($data['field_first_name'])) {
            $address_data['given_name'] = $data['field_first_name'];
          }
          if (isset($data['field_last_name'])) {
            $address_data['family_name'] = $data['field_last_name'];
          }
          
          \Drupal::logger('jwt_auth_api')->info('Saving customer profile with address: @data', [
            '@data' => json_encode($address_data),
          ]);
          
          // Save address to customer profile
          $customer_profile->set('address', $address_data);
          $customer_profile->save();
          
          \Drupal::logger('jwt_auth_api')->info('Customer profile saved successfully');
        } catch (\Exception $e) {
          \Drupal::logger('jwt_auth_api')->error('Failed to update customer profile: @message', [
            '@message' => $e->getMessage(),
          ]);
        }
      } else {
        \Drupal::logger('jwt_auth_api')->notice('No address data in update request');
      }
      
      return new JsonResponse([
        'message' => 'Profile updated successfully',
        'profile' => [
          'field_first_name' => $profile->hasField('field_first_name') ? $profile->get('field_first_name')->value : NULL,
          'field_last_name' => $profile->hasField('field_last_name') ? $profile->get('field_last_name')->value : NULL,
          'field_phone' => $profile->hasField('field_phone') ? $profile->get('field_phone')->value : NULL,
          'field_address' => $profile->hasField('field_address') ? $profile->get('field_address')->value : NULL,
          'field_city' => $profile->hasField('field_city') ? $profile->get('field_city')->value : NULL,
          'field_postal_code' => $profile->hasField('field_postal_code') ? $profile->get('field_postal_code')->value : NULL,
          'field_country' => $profile->hasField('field_country') ? $profile->get('field_country')->value : NULL,
        ],
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('jwt_auth_api')->error('Profile update failed: @message', ['@message' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Failed to update profile: ' . $e->getMessage()], 500);
    }
  }

  /**
   * Generate JWT token for user using HMAC.
   */
  private function generateToken(User $user) {
    // Use a secret key from settings
    $secret = \Drupal::config('system.site')->get('uuid');
    
    // Create JWT header
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    // Create JWT payload
    $payload = json_encode([
      'iat' => time(),
      'exp' => time() + (86400 * 7), // 7 days
      'sub' => $user->id(),
      'uid' => $user->id(),
      'name' => $user->getAccountName(),
      'mail' => $user->getEmail(),
    ]);

    // Encode Header and Payload
    $base64UrlHeader = $this->base64UrlEncode($header);
    $base64UrlPayload = $this->base64UrlEncode($payload);
    
    // Create Signature
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = $this->base64UrlEncode($signature);
    
    // Create JWT
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
  }

  /**
   * Base64 URL encode.
   */
  private function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
  }

}
