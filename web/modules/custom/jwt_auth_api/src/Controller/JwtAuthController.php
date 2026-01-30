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

    return new JsonResponse([
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
    ]);
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
   * Generate JWT token for user.
   */
  private function generateToken(User $user) {
    // Use Simple OAuth to generate token
    $token_storage = \Drupal::service('simple_oauth.repositories.access_token');
    $client_storage = \Drupal::service('simple_oauth.repositories.client');
    $scope_repository = \Drupal::service('simple_oauth.repositories.scope');
    
    $private_key = \Drupal::config('simple_oauth.settings')->get('private_key');
    $public_key = \Drupal::config('simple_oauth.settings')->get('public_key');
    
    if (empty($private_key) || empty($public_key)) {
      throw new \Exception('OAuth keys not configured');
    }

    // Create a simple JWT token manually
    $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
    $payload = json_encode([
      'iat' => time(),
      'exp' => time() + 86400,
      'sub' => $user->id(),
      'uid' => $user->id(),
      'name' => $user->getAccountName(),
      'mail' => $user->getEmail(),
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = '';
    openssl_sign(
      $base64UrlHeader . "." . $base64UrlPayload,
      $signature,
      file_get_contents($private_key),
      OPENSSL_ALGO_SHA256
    );
    
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
  }

}
