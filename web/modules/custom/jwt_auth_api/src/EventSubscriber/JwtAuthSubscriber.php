<?php

namespace Drupal\jwt_auth_api\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\user\Entity\User;

/**
 * JWT Authentication Event Subscriber.
 */
class JwtAuthSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 30];
    return $events;
  }

  /**
   * Handle JWT authentication on request.
   */
  public function onKernelRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $path = $request->getPathInfo();
    
    // Skip authentication for static assets and non-API routes
    $skip_patterns = [
      '/sites/default/files/',
      '/core/',
      '/modules/',
      '/themes/',
      '/libraries/',
    ];
    
    foreach ($skip_patterns as $pattern) {
      if (strpos($path, $pattern) === 0) {
        return;
      }
    }
    
    // Only log and authenticate for API routes and user pages
    $authenticate_patterns = ['/api/', '/user/'];
    $should_authenticate = false;
    foreach ($authenticate_patterns as $pattern) {
      if (strpos($path, $pattern) === 0) {
        $should_authenticate = true;
        break;
      }
    }
    
    if (!$should_authenticate) {
      return;
    }
    
    // Get Authorization header
    $auth_header = $request->headers->get('Authorization');
    
    \Drupal::logger('jwt_auth_api')->info('Request path: @path, Has auth header: @has', [
      '@path' => $path,
      '@has' => $auth_header ? 'YES' : 'NO',
    ]);
    
    if (!$auth_header || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
      \Drupal::logger('jwt_auth_api')->warning('No valid Authorization header found for path @path', [
        '@path' => $path,
      ]);
      return;
    }

    $token = $matches[1];
    \Drupal::logger('jwt_auth_api')->info('Token received: @token', [
      '@token' => substr($token, 0, 50) . '...',
    ]);
    
    // Validate and decode token
    $user_id = $this->validateToken($token);
    
    \Drupal::logger('jwt_auth_api')->info('Token validation result: @result', [
      '@result' => $user_id ? "User ID $user_id" : 'INVALID',
    ]);
    
    if ($user_id) {
      // Load and set the user
      $user = User::load($user_id);
      if ($user && $user->isActive()) {
        \Drupal::service('account_switcher')->switchTo($user);
        \Drupal::logger('jwt_auth_api')->info('User @uid authenticated via JWT for path @path', [
          '@uid' => $user_id,
          '@path' => $request->getPathInfo(),
        ]);
      } else {
        \Drupal::logger('jwt_auth_api')->error('User @uid not found or inactive', [
          '@uid' => $user_id,
        ]);
      }
    }
  }

  /**
   * Validate JWT token.
   */
  private function validateToken($token) {
    $secret = \Drupal::config('system.site')->get('uuid');
    
    // Split the token
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
      \Drupal::logger('jwt_auth_api')->error('Token validation failed: Invalid format (expected 3 parts, got @count)', [
        '@count' => count($parts),
      ]);
      return FALSE;
    }

    list($header, $payload, $signature) = $parts;
    
    // Verify signature
    $valid_signature = $this->base64UrlEncode(
      hash_hmac('sha256', $header . '.' . $payload, $secret, true)
    );
    
    if ($signature !== $valid_signature) {
      \Drupal::logger('jwt_auth_api')->error('Token validation failed: Signature mismatch. Expected: @expected, Got: @got', [
        '@expected' => substr($valid_signature, 0, 20) . '...',
        '@got' => substr($signature, 0, 20) . '...',
      ]);
      return FALSE;
    }

    // Decode payload
    $payload_data = json_decode($this->base64UrlDecode($payload), TRUE);
    
    if (!$payload_data) {
      \Drupal::logger('jwt_auth_api')->error('Token validation failed: Could not decode payload');
      return FALSE;
    }
    
    // Check expiration
    if (!isset($payload_data['exp'])) {
      \Drupal::logger('jwt_auth_api')->error('Token validation failed: No expiration time in payload');
      return FALSE;
    }
    
    if ($payload_data['exp'] < time()) {
      \Drupal::logger('jwt_auth_api')->error('Token validation failed: Token expired. Exp: @exp, Now: @now', [
        '@exp' => $payload_data['exp'],
        '@now' => time(),
      ]);
      return FALSE;
    }

    $uid = $payload_data['uid'] ?? FALSE;
    if (!$uid) {
      \Drupal::logger('jwt_auth_api')->error('Token validation failed: No UID in payload');
      return FALSE;
    }
    
    \Drupal::logger('jwt_auth_api')->info('Token validated successfully for UID: @uid', [
      '@uid' => $uid,
    ]);
    
    return $uid;
  }

  /**
   * Base64 URL encode.
   */
  private function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
  }

  /**
   * Base64 URL decode.
   */
  private function base64UrlDecode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
      $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
  }

}
