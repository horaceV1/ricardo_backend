<?php

namespace Drupal\footer_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class FooterApiController extends ControllerBase {

  public function get() {
    $config = \Drupal::config('footer_manager.settings');

    $data = [
      'brand_title' => $config->get('brand_title') ?? 'Clínica do Empresário',
      'brand_description' => $config->get('brand_description') ?? 'Consultoria especializada e soluções práticas para o crescimento do seu negócio.',
      'copyright' => $config->get('copyright') ?? '© 2026 Clínica do Empresário. Todos os direitos reservados.',
      'facebook_url' => $config->get('facebook_url') ?? '',
      'instagram_url' => $config->get('instagram_url') ?? '',
      'columns' => $config->get('columns') ?? [],
    ];

    $response = new JsonResponse($data);
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Cache-Control', 'public, max-age=300');
    return $response;
  }

}
