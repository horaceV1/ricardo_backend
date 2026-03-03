<?php

namespace Drupal\homepage_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns homepage data as JSON.
 */
class HomepageApiController extends ControllerBase {

  /**
   * Returns the homepage content as a structured JSON response.
   *
   * Loads the first published "homepage" node and returns all
   * section fields in an organized structure.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON with all homepage sections.
   */
  public function getHomepageData(): JsonResponse {
    $response_headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'GET',
      'Access-Control-Allow-Headers' => 'Content-Type',
      'Cache-Control' => 'public, max-age=300',
    ];

    try {
      // Find the first published homepage node.
      $nids = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'homepage')
        ->condition('status', 1)
        ->sort('created', 'ASC')
        ->range(0, 1)
        ->execute();

      if (empty($nids)) {
        return new JsonResponse([
          'error' => 'No homepage content found.',
          'data' => $this->getDefaults(),
        ], 200, $response_headers);
      }

      $node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load(reset($nids));

      if (!$node) {
        return new JsonResponse([
          'error' => 'Homepage node could not be loaded.',
          'data' => $this->getDefaults(),
        ], 200, $response_headers);
      }

      // Resolve the hero image URL.
      $heroImageUrl = '';
      if ($node->hasField('field_hero_image') && !$node->get('field_hero_image')->isEmpty()) {
        $media = $node->get('field_hero_image')->entity;
        if ($media && $media->hasField('field_media_image')) {
          $file = $media->get('field_media_image')->entity;
          if ($file) {
            $heroImageUrl = \Drupal::service('file_url_generator')
              ->generateAbsoluteString($file->getFileUri());
          }
        }
      }

      $data = [
        'hero' => [
          'badge' => $this->getHtml($node, 'field_hero_badge'),
          'title' => $this->getHtml($node, 'field_hero_title'),
          'highlight' => $this->getPlainText($node, 'field_hero_highlight'),
          'subtitle' => $this->getHtml($node, 'field_hero_subtitle'),
          'image' => $heroImageUrl,
          'cta' => [
            'text' => $this->getPlainText($node, 'field_hero_cta_text'),
            'link' => $this->getPlainText($node, 'field_hero_cta_link'),
          ],
          'cta2' => [
            'text' => $this->getPlainText($node, 'field_hero_cta2_text'),
            'link' => $this->getPlainText($node, 'field_hero_cta2_link'),
          ],
        ],
        'stats' => [
          [
            'value' => $this->getPlainText($node, 'field_stat1_value'),
            'label' => $this->getPlainText($node, 'field_stat1_label'),
          ],
          [
            'value' => $this->getPlainText($node, 'field_stat2_value'),
            'label' => $this->getPlainText($node, 'field_stat2_label'),
          ],
          [
            'value' => $this->getPlainText($node, 'field_stat3_value'),
            'label' => $this->getPlainText($node, 'field_stat3_label'),
          ],
        ],
        'features' => [
          'title' => $this->getHtml($node, 'field_features_title'),
          'subtitle' => $this->getHtml($node, 'field_features_subtitle'),
          'items' => [
            [
              'title' => $this->getHtml($node, 'field_feature1_title'),
              'description' => $this->getHtml($node, 'field_feature1_desc'),
            ],
            [
              'title' => $this->getHtml($node, 'field_feature2_title'),
              'description' => $this->getHtml($node, 'field_feature2_desc'),
            ],
            [
              'title' => $this->getHtml($node, 'field_feature3_title'),
              'description' => $this->getHtml($node, 'field_feature3_desc'),
            ],
            [
              'title' => $this->getHtml($node, 'field_feature4_title'),
              'description' => $this->getHtml($node, 'field_feature4_desc'),
            ],
          ],
        ],
        'formations' => [
          'title' => $this->getHtml($node, 'field_formations_title'),
          'subtitle' => $this->getHtml($node, 'field_formations_subtitle'),
        ],
        'testimonials' => [
          'title' => $this->getHtml($node, 'field_testimonials_title'),
          'subtitle' => $this->getHtml($node, 'field_testimonials_subtitle'),
          'items' => [
            [
              'text' => $this->getHtml($node, 'field_testimonial1_text'),
              'name' => $this->getPlainText($node, 'field_testimonial1_name'),
              'role' => $this->getPlainText($node, 'field_testimonial1_role'),
            ],
            [
              'text' => $this->getHtml($node, 'field_testimonial2_text'),
              'name' => $this->getPlainText($node, 'field_testimonial2_name'),
              'role' => $this->getPlainText($node, 'field_testimonial2_role'),
            ],
            [
              'text' => $this->getHtml($node, 'field_testimonial3_text'),
              'name' => $this->getPlainText($node, 'field_testimonial3_name'),
              'role' => $this->getPlainText($node, 'field_testimonial3_role'),
            ],
          ],
        ],
        'cta' => [
          'title' => $this->getHtml($node, 'field_cta_title'),
          'subtitle' => $this->getHtml($node, 'field_cta_subtitle'),
          'button' => [
            'text' => $this->getPlainText($node, 'field_cta_button_text'),
            'link' => $this->getPlainText($node, 'field_cta_button_link'),
          ],
          'button2' => [
            'text' => $this->getPlainText($node, 'field_cta_button2_text'),
            'link' => $this->getPlainText($node, 'field_cta_button2_link'),
          ],
        ],
      ];

      return new JsonResponse(['data' => $data], 200, $response_headers);

    }
    catch (\Exception $e) {
      \Drupal::logger('homepage_manager')->error('Homepage API error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'error' => 'An error occurred loading homepage data.',
        'data' => $this->getDefaults(),
      ], 200, $response_headers);
    }
  }

  /**
   * Gets the processed HTML for a text_long field (preserves formatting).
   *
   * Returns the rendered HTML through Drupal's text format filters,
   * which preserves font styles, colors, sizes etc. from CKEditor5.
   */
  protected function getHtml($node, string $field_name): string {
    if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
      $item = $node->get($field_name)->first();
      // For text_long / text_with_summary fields, use processed value.
      if (isset($item->processed)) {
        return (string) $item->processed;
      }
      // Fallback: return raw value.
      return (string) $item->value;
    }
    return '';
  }

  /**
   * Gets a plain text value (strips all HTML tags).
   *
   * Used for button text, links, names, roles, stat values etc.
   * where HTML formatting should not be rendered.
   */
  protected function getPlainText($node, string $field_name): string {
    if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
      $value = (string) $node->get($field_name)->value;
      // Strip HTML tags and decode entities for plain text fields.
      return html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return '';
  }

  /**
   * Returns default values for all homepage sections.
   *
   * Used as fallback when no homepage node exists yet.
   */
  protected function getDefaults(): array {
    return [
      'hero' => [
        'badge' => 'Formações Premium para Empresários',
        'title' => 'Transforme Seu Negócio',
        'highlight' => 'Negócio',
        'subtitle' => 'Consultoria especializada e soluções práticas para empresários que buscam crescimento sustentável e resultados consistentes.',
        'image' => '',
        'cta' => ['text' => 'Conheça Nossas Formações', 'link' => '/courses'],
        'cta2' => ['text' => 'Saiba Mais', 'link' => '/about'],
      ],
      'stats' => [
        ['value' => '15+', 'label' => 'Anos de Experiência'],
        ['value' => '300+', 'label' => 'Empresas Atendidas'],
        ['value' => '95%', 'label' => 'Taxa de Sucesso'],
      ],
      'features' => [
        'title' => 'Nossos Diferenciais',
        'subtitle' => 'Metodologia comprovada, expertise real e foco total nos resultados do seu negócio',
        'items' => [
          ['title' => 'Diagnóstico Preciso', 'description' => 'Análise profunda para identificar oportunidades e desafios'],
          ['title' => 'Expertise Comprovada', 'description' => 'Consultores com vivência prática em gestão empresarial'],
          ['title' => 'Atendimento Personalizado', 'description' => 'Soluções customizadas para a realidade do seu negócio'],
          ['title' => 'Resultados Mensuráveis', 'description' => 'Acompanhamento contínuo com métricas claras de evolução'],
        ],
      ],
      'formations' => [
        'title' => 'Nossas Formações',
        'subtitle' => 'Programas de consultoria e desenvolvimento empresarial sob medida',
      ],
      'testimonials' => [
        'title' => 'O Que Dizem Nossos Clientes',
        'subtitle' => 'Histórias reais de transformação empresarial',
        'items' => [
          ['text' => '', 'name' => '', 'role' => ''],
          ['text' => '', 'name' => '', 'role' => ''],
          ['text' => '', 'name' => '', 'role' => ''],
        ],
      ],
      'cta' => [
        'title' => 'Agende Sua Consulta Inicial',
        'subtitle' => 'Faça uma análise gratuita do seu negócio e descubra como podemos ajudá-lo a alcançar novos patamares',
        'button' => ['text' => 'Agendar Consulta Gratuita', 'link' => '/contact'],
        'button2' => ['text' => 'Conheça Nossa Metodologia', 'link' => '/about'],
      ],
    ];
  }

}
