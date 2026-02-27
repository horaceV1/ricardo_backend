<?php

namespace Drupal\jwt_auth_api\Plugin\Mail;

use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * HTML Mail plugin that preserves HTML in the email body.
 */
#[Mail(
  id: 'jwt_auth_api_html_mail',
  label: new TranslatableMarkup('JWT Auth API HTML Mail'),
  description: new TranslatableMarkup('Sends HTML emails preserving markup.'),
)]
class HtmlMail extends PhpMail {

  /**
   * {@inheritdoc}
   *
   * Override format() to preserve HTML instead of converting to plain text.
   */
  public function format(array $message) {
    // Join the body array into one string, but do NOT convert to plain text.
    $message['body'] = implode("\n\n", $message['body']);
    return $message;
  }

}
