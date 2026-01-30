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
    
    // Only process JWT auth routes
    $path = $request->getPathInfo();
    if (strpos($path, '/api/auth/') !== 0 || $path === '/api/auth/login' || $path === '/api/auth/register' || $path === '/api/auth/logout') {
      return;
    }

    // Get Authorization header
    $auth_header = $request->headers->get('Authorization');
    
    if (!$auth_header || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
      return;
    }

    $token = $matches[1];
    
    // Validate and decode token
    $user_id = $this->validateToken($token);
    
    if ($user_id) {
      // Load and set the user
      $user = User::load($user_id);
      if ($user && $user->isActive()) {
        \Drupal::service('account_switcher')->switchTo($user);
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
      return FALSE;
    }

    list($header, $payload, $signature) = $parts;
    
    // Verify signature
    $valid_signature = $this->base64UrlEncode(
      hash_hmac('sha256', $header . '.' . $payload, $secret, true)
    );
    
    if ($signature !== $valid_signature) {
      return FALSE;
    }

    // Decode payload
    $payload_data = json_decode($this->base64UrlDecode($payload), TRUE);
    
    // Check expiration
    if (!$payload_data || !isset($payload_data['exp']) || $payload_data['exp'] < time()) {
      return FALSE;
    }

    return $payload_data['uid'] ?? FALSE;
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
