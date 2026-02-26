<?php

namespace Drupal\admin_dashboard\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects /admin to the admin dashboard.
 */
class AdminRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs a new AdminRedirectSubscriber.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Redirects /admin to /admin/dashboard.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event): void {
    $request = $event->getRequest();
    $path = $request->getPathInfo();

    // Only redirect the exact /admin path.
    if ($path === '/admin' && $this->currentUser->hasPermission('access administration pages')) {
      $url = Url::fromRoute('admin_dashboard.dashboard')->toString();
      $event->setResponse(new RedirectResponse($url, 302));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Use a high priority so we redirect before the page is rendered.
    return [
      KernelEvents::REQUEST => ['onRequest', 100],
    ];
  }

}
