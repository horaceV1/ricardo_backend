<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\file\Entity\File;

/**
 * API Controller for Dynamic Forms.
 */
class DynamicFormApiController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get form configuration.
   *
   * @param string $form_id
   *   The form ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with form configuration.
   */
  public function getForm($form_id) {
    try {
      $form = $this->entityTypeManager
        ->getStorage('dynamic_form')
        ->load($form_id);

      if (!$form) {
        return new JsonResponse(['error' => 'Form not found'], 404);
      }

      $data = [
        'id' => $form->id(),
        'label' => $form->label(),
        'fields' => $form->get('fields') ?: [],
        'mailchimp_enabled' => $form->get('mailchimp_enabled') ?: FALSE,
        'mailchimp_list_id' => $form->get('mailchimp_list_id') ?: '',
      ];

      return new JsonResponse($data);
    } catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Submit form data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with submission result.
   */
  public function submitForm(Request $request) {
    try {
      $form_id = $request->request->get('form_id');

      if (!$form_id) {
        return new JsonResponse(['error' => 'Form ID is required'], 400);
      }

      $form = $this->entityTypeManager
        ->getStorage('dynamic_form')
        ->load($form_id);

      if (!$form) {
        return new JsonResponse(['error' => 'Form not found'], 404);
      }

      $fields = $form->get('fields') ?: [];
      $submission_data = [];

      // Process form fields
      foreach ($fields as $index => $field) {
        $field_key = "field_{$index}";
        
        if ($field['type'] === 'documento') {
          // Handle file upload
          $file = $request->files->get($field_key);
          if ($file) {
            // Save the file
            $directory = 'public://formularios-dinamicos';
            \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
            
            $file_entity = \Drupal::service('file.repository')->writeData(
              file_get_contents($file->getRealPath()),
              $directory . '/' . $file->getClientOriginalName(),
              \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME
            );
            
            if ($file_entity) {
              $submission_data[$field['label']] = [
                'type' => 'file',
                'value' => $file_entity->id(),
                'filename' => $file->getClientOriginalName(),
              ];
            }
          }
        } else {
          // Handle text input
          $value = $request->request->get($field_key);
          if ($value) {
            $submission_data[$field['label']] = [
              'type' => 'text',
              'value' => $value,
            ];
          }
        }
      }

      // Create submission entity
      $submission = $this->entityTypeManager
        ->getStorage('dynamic_form_submission')
        ->create([
          'form_id' => $form_id,
          'data' => $submission_data,
          'created' => \Drupal::time()->getRequestTime(),
        ]);

      $submission->save();

      // Handle Mailchimp if enabled
      if ($form->get('mailchimp_enabled') && !empty($form->get('mailchimp_list_id'))) {
        $this->subscribeToMailchimp($submission_data, $form->get('mailchimp_list_id'));
      }

      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Form submitted successfully',
        'submission_id' => $submission->id(),
      ]);

    } catch (\Exception $e) {
      \Drupal::logger('formulario_candidatura_dinamico')->error('Form submission error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Failed to submit form'], 500);
    }
  }

  /**
   * Subscribe email to Mailchimp list.
   *
   * @param array $submission_data
   *   The submission data.
   * @param string $list_id
   *   The Mailchimp list ID.
   */
  protected function subscribeToMailchimp(array $submission_data, $list_id) {
    // Extract email from submission data if available
    $email = NULL;
    foreach ($submission_data as $field => $data) {
      if ($data['type'] === 'text' && filter_var($data['value'], FILTER_VALIDATE_EMAIL)) {
        $email = $data['value'];
        break;
      }
    }

    if ($email && \Drupal::service('module_handler')->moduleExists('mailchimp')) {
      try {
        $mailchimp_lists = mailchimp_get_api_object('MailchimpLists');
        if ($mailchimp_lists) {
          $mailchimp_lists->addOrUpdateMember($list_id, $email, 'subscribed');
        }
      } catch (\Exception $e) {
        \Drupal::logger('formulario_candidatura_dinamico')->error('Mailchimp subscription error: @message', [
          '@message' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Get article layout with dynamic forms.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The article node.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with forms from Layout Builder.
   */
  public function getArticleLayout($node) {
    try {
      if (!$node || $node->bundle() !== 'article') {
        return new JsonResponse(['error' => 'Article not found'], 404);
      }

      $forms = [];
      $debug = [
        'node_id' => $node->id(),
        'has_layout_field' => $node->hasField('layout_builder__layout'),
        'field_is_empty' => false,
        'sections_count' => 0,
        'components_count' => 0,
        'plugin_ids' => [],
      ];
      
      // Check if layout builder is enabled
      if ($node->hasField('layout_builder__layout')) {
        $layout_field = $node->get('layout_builder__layout');
        $debug['field_is_empty'] = $layout_field->isEmpty();
        
        // If the field is empty, try to get the default layout from the entity view display
        if ($layout_field->isEmpty()) {
          $entity_view_display = \Drupal::entityTypeManager()
            ->getStorage('entity_view_display')
            ->load('node.article.default');
          
          if ($entity_view_display && $entity_view_display->isLayoutBuilderEnabled()) {
            $sections = $entity_view_display->getThirdPartySetting('layout_builder', 'sections', []);
            $debug['using_default_layout'] = true;
            $debug['sections_count'] = count($sections);
            
            foreach ($sections as $section) {
              $components = $section->getComponents();
              $debug['components_count'] += count($components);
              
              foreach ($components as $component) {
                $plugin = $component->getPlugin();
                $plugin_id = $plugin->getPluginId();
                $debug['plugin_ids'][] = $plugin_id;
                
                // Check if this is a dynamic form block
                if ($plugin_id === 'dynamic_form_block') {
                  $config = $plugin->getConfiguration();
                  $form_id = $config['dynamic_form_id'] ?? null;
                  
                  if ($form_id) {
                    $form = $this->entityTypeManager
                      ->getStorage('dynamic_form')
                      ->load($form_id);
                    
                    if ($form) {
                      $forms[] = [
                        'id' => $form->id(),
                        'label' => $form->label(),
                        'fields' => $form->get('fields') ?: [],
                      ];
                    }
                  }
                }
              }
            }
          }
        } else {
          // Use per-node layout
          $layout = $layout_field->getValue();
          $debug['using_default_layout'] = false;
          $debug['sections_count'] = count($layout);
          
          foreach ($layout as $section_item) {
            if (isset($section_item['section'])) {
              $section_object = $section_item['section'];
              $components = $section_object->getComponents();
              $debug['components_count'] += count($components);
              
              foreach ($components as $component) {
                $plugin = $component->getPlugin();
                $plugin_id = $plugin->getPluginId();
                $debug['plugin_ids'][] = $plugin_id;
                
                // Check if this is a dynamic form block
                if ($plugin_id === 'dynamic_form_block') {
                  $config = $plugin->getConfiguration();
                  $form_id = $config['dynamic_form_id'] ?? null;
                  
                  if ($form_id) {
                    $form = $this->entityTypeManager
                      ->getStorage('dynamic_form')
                      ->load($form_id);
                    
                    if ($form) {
                      $forms[] = [
                        'id' => $form->id(),
                        'label' => $form->label(),
                        'fields' => $form->get('fields') ?: [],
                      ];
                    }
                  }
                }
              }
            }
          }
        }
      }

      return new JsonResponse([
        'forms' => $forms,
        'debug' => $debug,
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('formulario_candidatura_dinamico')->error('Article layout error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ], 500);
    }
  }

}
