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
      
      // Convert country name to ISO code if needed
      if (isset($data['field_country'])) {
        $data['field_country'] = $this->convertCountryToIso($data['field_country']);
      }
      
      $profile_fields = [
        'type' => 'user_submissions',
        'uid' => $user->id(),
        'field_first_name' => $data['field_first_name'] ?? NULL,
        'field_last_name' => $data['field_last_name'] ?? NULL,
        'field_phone' => $data['field_phone'] ?? NULL,
        'field_address' => $data['field_address'] ?? NULL,
        'field_city' => $data['field_city'] ?? NULL,
        'field_postal_code' => $data['field_postal_code'] ?? NULL,
        'field_country' => $data['field_country'] ?? NULL,
      ];
      if (!empty($data['field_nif'])) {
        $profile_fields['field_nif'] = $data['field_nif'];
      }
      $profile = $profile_storage->create($profile_fields);
      $profile->save();

      // ALWAYS create customer profile (address book) during registration
      try {
        // Set country code first (required field)
        $address_data = [
          'country_code' => $data['field_country'] ?? 'PT',
        ];
        
        // Add address fields if provided (not empty)
        if (!empty($data['field_address'])) {
          $address_data['address_line1'] = $data['field_address'];
        }
        if (!empty($data['field_city'])) {
          $address_data['locality'] = $data['field_city'];
        }
        if (!empty($data['field_postal_code'])) {
          $address_data['postal_code'] = $data['field_postal_code'];
        }
        
        // Add name fields if available (not empty)
        if (!empty($data['field_first_name'])) {
          $address_data['given_name'] = $data['field_first_name'];
        }
        if (!empty($data['field_last_name'])) {
          $address_data['family_name'] = $data['field_last_name'];
        }
        
        \Drupal::logger('jwt_auth_api')->info('Creating customer profile (address book) with data: @data', [
          '@data' => json_encode($address_data),
        ]);
        
        // ALWAYS create customer profile with at minimum the country code
        $customer_profile = $profile_storage->create([
          'type' => 'customer',
          'uid' => $user->id(),
          'address' => $address_data,
        ]);
        $customer_profile->save();
        
        \Drupal::logger('jwt_auth_api')->info('✓ Customer profile (address book) created with ID: @id', [
          '@id' => $customer_profile->id(),
        ]);
      } catch (\Exception $e) {
        \Drupal::logger('jwt_auth_api')->error('Failed to create customer profile (address book): @message', [
          '@message' => $e->getMessage(),
        ]);
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
      $response['field_nif'] = $profile->hasField('field_nif') ? $profile->get('field_nif')->value : NULL;
    } else {
      $response['field_phone'] = NULL;
      $response['field_address'] = NULL;
      $response['field_city'] = NULL;
      $response['field_postal_code'] = NULL;
      $response['field_country'] = NULL;
      $response['field_nif'] = NULL;
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
    
    // Convert country name to ISO code if needed
    if (isset($data['field_country'])) {
      $data['field_country'] = $this->convertCountryToIso($data['field_country']);
    }
    
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
      if (isset($data['field_nif']) && $profile->hasField('field_nif')) {
        $profile->set('field_nif', $data['field_nif']);
      }
      
      $profile->save();

      // ALWAYS update or create customer profile (address book) for any profile update
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
          \Drupal::logger('jwt_auth_api')->info('Creating new customer profile (address book) for user @uid', [
            '@uid' => $current_user->id(),
          ]);
        } else {
          // Use existing customer profile (get the first one)
          $customer_profile = reset($customer_profiles);
          \Drupal::logger('jwt_auth_api')->info('Updating existing customer profile (address book) @id', [
            '@id' => $customer_profile->id(),
          ]);
        }
        
        // Build address data from incoming data
        $address_data = [];
        
        // Get existing address data if available
        if ($customer_profile->hasField('address') && !$customer_profile->get('address')->isEmpty()) {
          $existing_address = $customer_profile->get('address')->first()->toArray();
          $address_data = $existing_address;
        }
        
        // Set country code first (required field) - default to PT if not provided
        $address_data['country_code'] = $data['field_country'] ?? $address_data['country_code'] ?? 'PT';
        
        // Update with new data - ALWAYS update all fields if provided
        if (isset($data['field_address']) && $data['field_address'] !== '') {
          $address_data['address_line1'] = $data['field_address'];
        }
        if (isset($data['field_city']) && $data['field_city'] !== '') {
          $address_data['locality'] = $data['field_city'];
        }
        if (isset($data['field_postal_code']) && $data['field_postal_code'] !== '') {
          $address_data['postal_code'] = $data['field_postal_code'];
        }
        
        // Add name fields if available
        if (isset($data['field_first_name']) && $data['field_first_name'] !== '') {
          $address_data['given_name'] = $data['field_first_name'];
        }
        if (isset($data['field_last_name']) && $data['field_last_name'] !== '') {
          $address_data['family_name'] = $data['field_last_name'];
        }
        
        \Drupal::logger('jwt_auth_api')->info('Saving address to customer profile (address book): @data', [
          '@data' => json_encode($address_data),
        ]);
        
        // ALWAYS save address to customer profile (address book) - at minimum with country code
        $customer_profile->set('address', $address_data);
        $customer_profile->save();
        
        \Drupal::logger('jwt_auth_api')->info('✓ Customer profile (address book) saved successfully with ID @id', [
          '@id' => $customer_profile->id(),
        ]);
      } catch (\Exception $e) {
        \Drupal::logger('jwt_auth_api')->error('Failed to update customer profile (address book): @message', [
          '@message' => $e->getMessage(),
        ]);
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
          'field_nif' => $profile->hasField('field_nif') ? $profile->get('field_nif')->value : NULL,
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

  /**
   * Convert country name to ISO 2-letter code.
   */
  private function convertCountryToIso($country) {
    // If already 2 letters, return as-is
    if (strlen($country) === 2) {
      return strtoupper($country);
    }
    
    // Map common country names to ISO codes
    $countryMap = [
      'Portugal' => 'PT',
      'Spain' => 'ES',
      'France' => 'FR',
      'Germany' => 'DE',
      'Italy' => 'IT',
      'United Kingdom' => 'GB',
      'Brazil' => 'BR',
      'United States' => 'US',
      'Angola' => 'AO',
      'Mozambique' => 'MZ',
      'Cape Verde' => 'CV',
      'Guinea-Bissau' => 'GW',
      'São Tomé and Príncipe' => 'ST',
      'Timor-Leste' => 'TL',
    ];
    
    // Case-insensitive search
    $countryLower = strtolower($country);
    foreach ($countryMap as $name => $code) {
      if (strtolower($name) === $countryLower) {
        return $code;
      }
    }
    
    // If not found, default to PT
    \Drupal::logger('jwt_auth_api')->warning('Unknown country name "@country", defaulting to PT', [
      '@country' => $country,
    ]);
    return 'PT';
  }

  /**
   * Custom access check for JWT authentication.
   * 
   * This is used in routing.yml files as:
   * requirements:
   *   _custom_access: '\Drupal\jwt_auth_api\Controller\JwtAuthController::checkJwtAccess'
   */
  public function checkJwtAccess() {
    $current_user = \Drupal::currentUser();
    
    // Check if user is authenticated (not anonymous)
    if ($current_user->isAuthenticated()) {
      return \Drupal\Core\Access\AccessResult::allowed();
    }
    
    return \Drupal\Core\Access\AccessResult::forbidden('JWT authentication required');
  }

  /**
   * Send email verification code.
   */
  public function sendVerificationCode(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $email = $data['email'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return new JsonResponse(['error' => 'Valid email is required'], 400);
    }

    // Check if email already registered.
    $existing = user_load_by_mail($email);
    if ($existing) {
      return new JsonResponse(['error' => 'Este email já está registado'], 409);
    }

    // Generate 6-digit code.
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Delete old codes for this email.
    $db = \Drupal::database();
    $db->delete('email_verification_codes')
      ->condition('email', $email)
      ->execute();

    // Store new code.
    $db->insert('email_verification_codes')
      ->fields([
        'email' => $email,
        'code' => $code,
        'created' => time(),
        'verified' => 0,
      ])
      ->execute();

    // Build styled HTML email.
    $site_name = 'Clínica do Empresário';
    $html_body = $this->buildVerificationEmailHtml($code, $site_name);

    // Send email.
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $params = [
      'subject' => 'Código de Verificação - ' . $site_name,
      'body' => $html_body,
    ];

    $result = $mail_manager->mail('jwt_auth_api', 'email_verification', $email, 'pt', $params, NULL, TRUE);

    if ($result['result'] === TRUE) {
      \Drupal::logger('jwt_auth_api')->info('Verification code sent to @email', ['@email' => $email]);
      return new JsonResponse(['message' => 'Código de verificação enviado para o seu email']);
    }
    else {
      \Drupal::logger('jwt_auth_api')->error('Failed to send verification email to @email', ['@email' => $email]);
      return new JsonResponse(['error' => 'Falha ao enviar email de verificação'], 500);
    }
  }

  /**
   * Verify email code.
   */
  public function verifyEmailCode(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $email = $data['email'] ?? '';
    $code = $data['code'] ?? '';

    if (empty($email) || empty($code)) {
      return new JsonResponse(['error' => 'Email and code are required'], 400);
    }

    $db = \Drupal::database();
    $record = $db->select('email_verification_codes', 'e')
      ->fields('e')
      ->condition('email', $email)
      ->condition('code', $code)
      ->condition('verified', 0)
      ->orderBy('created', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();

    if (!$record) {
      return new JsonResponse(['error' => 'Código inválido ou expirado'], 400);
    }

    // Check if code is older than 15 minutes.
    if ((time() - $record->created) > 900) {
      return new JsonResponse(['error' => 'Código expirado. Solicite um novo código.'], 400);
    }

    // Mark as verified.
    $db->update('email_verification_codes')
      ->fields(['verified' => 1])
      ->condition('id', $record->id)
      ->execute();

    return new JsonResponse(['message' => 'Email verificado com sucesso', 'verified' => TRUE]);
  }

  /**
   * Build HTML email for verification code.
   */
  private function buildVerificationEmailHtml($code, $site_name) {
    return '
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f7f6; font-family: Arial, Helvetica, sans-serif;">
  <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 600px; margin: 40px auto;">
    <!-- Header -->
    <tr>
      <td style="background: linear-gradient(135deg, #009999 0%, #007a7a 100%); padding: 40px 30px; text-align: center; border-radius: 16px 16px 0 0;">
        <h1 style="color: #ffffff; font-size: 28px; margin: 0 0 8px 0; font-weight: 700; letter-spacing: -0.5px;">' . $site_name . '</h1>
        <p style="color: #b3e6e6; font-size: 14px; margin: 0;">Consultoria e Gestão Empresarial</p>
      </td>
    </tr>
    <!-- Body -->
    <tr>
      <td style="background-color: #ffffff; padding: 40px 30px;">
        <h2 style="color: #1a1a1a; font-size: 22px; margin: 0 0 16px 0; font-weight: 600;">Verificação de Email</h2>
        <p style="color: #4a4a4a; font-size: 15px; line-height: 1.6; margin: 0 0 24px 0;">Obrigado por se registar na <strong>' . $site_name . '</strong>. Utilize o código abaixo para verificar o seu endereço de email:</p>
        
        <!-- Code Box -->
        <div style="background: linear-gradient(135deg, #f0fafa 0%, #e6f5f5 100%); border: 2px solid #009999; border-radius: 12px; padding: 24px; text-align: center; margin: 0 0 24px 0;">
          <p style="color: #666; font-size: 12px; text-transform: uppercase; letter-spacing: 2px; margin: 0 0 8px 0; font-weight: 600;">O seu código</p>
          <p style="color: #009999; font-size: 36px; font-weight: 800; letter-spacing: 8px; margin: 0; font-family: monospace;">' . $code . '</p>
        </div>
        
        <p style="color: #888; font-size: 13px; line-height: 1.5; margin: 0 0 8px 0;">⏱ Este código é válido por <strong>15 minutos</strong>.</p>
        <p style="color: #888; font-size: 13px; line-height: 1.5; margin: 0;">Se não solicitou este código, pode ignorar este email com segurança.</p>
      </td>
    </tr>
    <!-- Footer -->
    <tr>
      <td style="background-color: #1a2332; padding: 24px 30px; text-align: center; border-radius: 0 0 16px 16px;">
        <p style="color: #80d4d4; font-size: 14px; font-weight: 600; margin: 0 0 8px 0;">' . $site_name . '</p>
        <p style="color: #8899aa; font-size: 12px; margin: 0;">&copy; ' . date('Y') . ' Todos os direitos reservados.</p>
      </td>
    </tr>
  </table>
</body>
</html>';
  }

}
