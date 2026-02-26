<?php

namespace Drupal\contact_page_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the Contact Page API endpoint.
 */
class ContactPageApiController extends ControllerBase {

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a ContactPageApiController object.
   */
  public function __construct(FileUrlGeneratorInterface $file_url_generator) {
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_url_generator')
    );
  }

  /**
   * Returns the contact page data as JSON.
   */
  public function getContactPageData(Request $request) {
    // Handle CORS preflight.
    if ($request->getMethod() === 'OPTIONS') {
      return new JsonResponse(NULL, 204, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
      ]);
    }

    // Query for the first published contact_page node.
    $query = $this->entityTypeManager()->getStorage('node')->getQuery()
      ->condition('type', 'contact_page')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE);

    $nids = $query->execute();

    if (empty($nids)) {
      return new JsonResponse(
        $this->getDefaults(),
        200,
        $this->getCorsHeaders()
      );
    }

    $node = $this->entityTypeManager()->getStorage('node')->load(reset($nids));

    if (!$node) {
      return new JsonResponse(
        $this->getDefaults(),
        200,
        $this->getCorsHeaders()
      );
    }

    $defaults = $this->getDefaults();

    // Parse subjects from comma-separated string into array.
    $subjects_raw = $this->getFieldValue($node, 'field_contact_subjects', '');
    $subjects = [];
    if (!empty($subjects_raw)) {
      $subjects = array_map('trim', explode(',', $subjects_raw));
      $subjects = array_filter($subjects);
    }
    if (empty($subjects)) {
      $subjects = $defaults['form']['subjects'];
    }

    // Parse FAQ items.
    $faqs = [];
    for ($i = 1; $i <= 3; $i++) {
      $question = $this->getFieldValue($node, "field_contact_faq{$i}_question", '');
      $answer = $this->getFieldValue($node, "field_contact_faq{$i}_answer", '');
      if (!empty($question) || !empty($answer)) {
        $faqs[] = [
          'question' => $question ?: $defaults['faq']['items'][$i - 1]['question'] ?? '',
          'answer' => $answer ?: $defaults['faq']['items'][$i - 1]['answer'] ?? '',
        ];
      }
    }
    if (empty($faqs)) {
      $faqs = $defaults['faq']['items'];
    }

    $data = [
      'hero' => [
        'title' => $this->getFieldValue($node, 'field_contact_hero_title', $defaults['hero']['title']),
        'subtitle' => $this->getFieldValue($node, 'field_contact_hero_subtitle', $defaults['hero']['subtitle']),
      ],
      'info_cards' => [
        'address' => [
          'title' => $this->getFieldValue($node, 'field_contact_address_title', $defaults['info_cards']['address']['title']),
          'text' => $this->getFieldValue($node, 'field_contact_address_text', $defaults['info_cards']['address']['text']),
        ],
        'phone' => [
          'title' => $this->getFieldValue($node, 'field_contact_phone_title', $defaults['info_cards']['phone']['title']),
          'text' => $this->getFieldValue($node, 'field_contact_phone_text', $defaults['info_cards']['phone']['text']),
          'link' => $this->getFieldValue($node, 'field_contact_phone_link', $defaults['info_cards']['phone']['link']),
        ],
        'email' => [
          'title' => $this->getFieldValue($node, 'field_contact_email_title', $defaults['info_cards']['email']['title']),
          'text' => $this->getFieldValue($node, 'field_contact_email_text', $defaults['info_cards']['email']['text']),
          'link' => $this->getFieldValue($node, 'field_contact_email_link', $defaults['info_cards']['email']['link']),
        ],
        'hours' => [
          'title' => $this->getFieldValue($node, 'field_contact_hours_title', $defaults['info_cards']['hours']['title']),
          'text' => $this->getFieldValue($node, 'field_contact_hours_text', $defaults['info_cards']['hours']['text']),
        ],
      ],
      'form' => [
        'title' => $this->getFieldValue($node, 'field_contact_form_title', $defaults['form']['title']),
        'subtitle' => $this->getFieldValue($node, 'field_contact_form_subtitle', $defaults['form']['subtitle']),
        'success_title' => $this->getFieldValue($node, 'field_contact_form_success', $defaults['form']['success_title']),
        'success_message' => $this->getFieldValue($node, 'field_contact_form_success_msg', $defaults['form']['success_message']),
        'button_text' => $this->getFieldValue($node, 'field_contact_form_btn_text', $defaults['form']['button_text']),
        'button_sending' => $this->getFieldValue($node, 'field_contact_form_btn_sending', $defaults['form']['button_sending']),
        'newsletter_text' => $this->getFieldValue($node, 'field_contact_newsletter_text', $defaults['form']['newsletter_text']),
        'subjects' => $subjects,
      ],
      'map' => [
        'embed_url' => $this->getFieldValue($node, 'field_contact_map_embed', $defaults['map']['embed_url']),
        'title' => $this->getFieldValue($node, 'field_contact_map_title', $defaults['map']['title']),
        'text' => $this->getFieldValue($node, 'field_contact_map_text', $defaults['map']['text']),
      ],
      'faq' => [
        'title' => $this->getFieldValue($node, 'field_contact_faq_title', $defaults['faq']['title']),
        'items' => $faqs,
      ],
      'cta' => [
        'title' => $this->getFieldValue($node, 'field_contact_cta_title', $defaults['cta']['title']),
        'subtitle' => $this->getFieldValue($node, 'field_contact_cta_subtitle', $defaults['cta']['subtitle']),
        'button_text' => $this->getFieldValue($node, 'field_contact_cta_btn_text', $defaults['cta']['button_text']),
        'button_link' => $this->getFieldValue($node, 'field_contact_cta_btn_link', $defaults['cta']['button_link']),
      ],
    ];

    return new JsonResponse($data, 200, $this->getCorsHeaders());
  }

  /**
   * Helper to safely get a field value with a fallback default.
   */
  protected function getFieldValue($node, $field_name, $default = '') {
    if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
      return $node->get($field_name)->value;
    }
    return $default;
  }

  /**
   * Returns CORS headers.
   */
  protected function getCorsHeaders() {
    return [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'GET, OPTIONS',
      'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
      'Cache-Control' => 'public, max-age=300',
    ];
  }

  /**
   * Returns the default contact page data (matching the hardcoded frontend content).
   */
  protected function getDefaults() {
    return [
      'hero' => [
        'title' => 'Entre em Contato',
        'subtitle' => 'Estamos aqui para ajudar o seu negócio a crescer. Entre em contato conosco para saber mais sobre os nossos serviços de consultoria e formação profissional.',
      ],
      'info_cards' => [
        'address' => [
          'title' => 'Morada',
          'text' => 'Avenida 25 de Abril, nº 47, 1º Andar, Sala 12, 2620-185 Ramada',
        ],
        'phone' => [
          'title' => 'Telefone',
          'text' => '+351 211 164 404',
          'link' => 'tel:+351211164404',
        ],
        'email' => [
          'title' => 'Email',
          'text' => 'geral@clinicadoempresario.com',
          'link' => 'mailto:geral@clinicadoempresario.com',
        ],
        'hours' => [
          'title' => 'Horário',
          'text' => 'Seg - Sex: 9:00 - 18:00',
        ],
      ],
      'form' => [
        'title' => 'Envie-nos uma Mensagem',
        'subtitle' => 'Preencha o formulário abaixo e entraremos em contato o mais breve possível.',
        'success_title' => 'Mensagem Enviada!',
        'success_message' => 'Obrigado pelo seu contato. A nossa equipa irá responder-lhe dentro de 24 horas úteis.',
        'button_text' => 'Enviar Mensagem',
        'button_sending' => 'A enviar...',
        'newsletter_text' => 'Desejo receber novidades e informações sobre os serviços da Clínica do Empresário',
        'subjects' => [
          'Consultoria Estratégica',
          'Contabilidade e Finanças',
          'Marketing Digital',
          'Recursos Humanos',
          'Formação Profissional',
          'Incentivos e Fundos',
          'Seguros',
          'Certificação Energética',
          'Outro Assunto',
        ],
      ],
      'map' => [
        'embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3110.5847753811074!2d-9.2131!3d38.7917!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzjCsDQ3JzMwLjEiTiA5wrAxMic0Ny4yIlc!5e0!3m2!1spt-PT!2spt!4v1234567890',
        'title' => 'A Nossa Localização',
        'text' => 'Avenida 25 de Abril, nº 47, 1º Andar, Sala 12, 2620-185 Ramada',
      ],
      'faq' => [
        'title' => 'Perguntas Frequentes',
        'items' => [
          [
            'question' => 'Qual é o tempo de resposta?',
            'answer' => 'Respondemos a todas as mensagens dentro de 24 horas úteis. Para assuntos urgentes, recomendamos que nos contacte por telefone.',
          ],
          [
            'question' => 'Como posso agendar uma reunião?',
            'answer' => 'Pode agendar uma reunião diretamente através do formulário acima, por telefone ou por email. Oferecemos reuniões presenciais e online.',
          ],
          [
            'question' => 'A primeira consulta é gratuita?',
            'answer' => 'Sim, oferecemos uma primeira consulta gratuita para avaliar as necessidades do seu negócio e apresentar as nossas soluções.',
          ],
        ],
      ],
      'cta' => [
        'title' => 'Prefere ligar?',
        'subtitle' => 'A nossa equipa está disponível de segunda a sexta, das 9h às 18h.',
        'button_text' => '+351 211 164 404',
        'button_link' => 'tel:+351211164404',
      ],
    ];
  }

}
