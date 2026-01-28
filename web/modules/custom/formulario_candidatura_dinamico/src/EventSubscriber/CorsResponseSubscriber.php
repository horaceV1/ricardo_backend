<?php

namespace Drupal\formulario_candidatura_dinamico\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Add CORS headers to API responses.
 */
class CorsResponseSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse', -10];
    return $events;
  }

  /**
   * Add CORS headers to API responses.
   */
  public function onResponse(ResponseEvent $event) {
    $request = $event->getRequest();
    $response = $event->getResponse();

    // Only add CORS headers to API routes
    $path = $request->getPathInfo();
    if (strpos($path, '/api/') === 0) {
      $response->headers->set('Access-Control-Allow-Origin', '*');
      $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
      $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
      $response->headers->set('Access-Control-Allow-Credentials', 'true');
      $response->headers->set('Access-Control-Max-Age', '3600');

      // For OPTIONS requests, set status to 204
      if ($request->getMethod() === 'OPTIONS') {
        $response->setStatusCode(204);
        $response->setContent('');
      }
    }
  }

}
