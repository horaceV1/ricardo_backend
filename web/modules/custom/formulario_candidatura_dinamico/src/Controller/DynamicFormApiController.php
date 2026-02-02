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
   * Test endpoint to verify API is working.
   */
  public function testEndpoint(Request $request) {
    $response_headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
      'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
    ];
    
    return new JsonResponse([
      'status' => 'ok',
      'message' => 'API is working',
      'method' => $request->getMethod(),
      'time' => time(),
    ], 200, $response_headers);
  }

  /**
   * Submit a dynamic form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with submission result.
   */
  public function submitForm(Request $request) {
    // Force error handling to return JSON
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
      throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    });
    
    // Add CORS headers
    $response_headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'POST, OPTIONS',
      'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
    ];
    
    // Handle preflight requests
    if ($request->getMethod() === 'OPTIONS') {
      return new JsonResponse(null, 200, $response_headers);
    }
    
    try {
      // Log the incoming request
      \Drupal::logger('formulario_candidatura_dinamico')->info('Form submission received: form_id=@form_id, email=@email', [
        '@form_id' => $request->request->get('form_id'),
        '@email' => $request->request->get('email'),
      ]);
      
      $form_id = $request->request->get('form_id');
      $email = $request->request->get('email');

      if (!$form_id) {
        return new JsonResponse(['error' => 'Form ID is required'], 400, $response_headers);
      }

      if (!$email) {
        return new JsonResponse(['error' => 'Email is required'], 400, $response_headers);
      }
      
      // Check if entity storage exists
      try {
        $form_storage = $this->entityTypeManager->getStorage('dynamic_form');
      } catch (\Exception $e) {
        \Drupal::logger('formulario_candidatura_dinamico')->error('Cannot load form storage: @message', [
          '@message' => $e->getMessage(),
        ]);
        return new JsonResponse(['error' => 'Form system not available: ' . $e->getMessage()], 500, $response_headers);
      }

      $form = $form_storage->load($form_id);

      if (!$form) {
        return new JsonResponse(['error' => 'Form not found'], 404, $response_headers);
      }
      
      if (!$form) {
        return new JsonResponse(['error' => 'Form not found'], 404, $response_headers);
      }
      
      $fields = $form->get('fields') ?: [];
      $submission_data = [];

      // Process form fields
      foreach ($fields as $index => $field) {
        $field_key = "field_{$index}";
        
        if ($field['type'] === 'documento' || $field['type'] === 'imagem') {
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
            $submission_data[$field['label']] = $value;
          }
        }
      }

      // Store directly in user profile instead of creating submission entity
      $current_user = \Drupal::currentUser();
      
      \Drupal::logger('formulario_candidatura_dinamico')->info('Attempting to save submission for user @uid', [
        '@uid' => $current_user->id(),
      ]);
      
      if (!$current_user->isAnonymous()) {
        try {
          $profile_storage = $this->entityTypeManager->getStorage('profile');
          
          $profiles = $profile_storage->loadByProperties([
            'uid' => $current_user->id(),
            'type' => 'user_submissions',
          ]);
          
          if (empty($profiles)) {
            $profile = $profile_storage->create([
              'type' => 'user_submissions',
              'uid' => $current_user->id(),
            ]);
          } else {
            $profile = reset($profiles);
          }
          
          // Get existing submissions only if field exists
          $existing_data = [];
          if ($profile->hasField('field_submissions')) {
            $field_value = $profile->get('field_submissions')->value;
            if ($field_value) {
              $existing_data = json_decode($field_value, TRUE);
              if (!is_array($existing_data)) {
                $existing_data = [];
              }
            }
          }
          
          // Add new submission
          $existing_data[] = [
            'webform_id' => $form_id,
            'submission_id' => 'direct_' . time(),
            'timestamp' => time(),
            'email' => $email,
            'data' => $submission_data,
          ];
          
          // Update profile only if field exists
          if ($profile->hasField('field_submissions')) {
            $profile->set('field_submissions', json_encode($existing_data));
          }
          
          $profile->save();
          
          \Drupal::logger('formulario_candidatura_dinamico')->info('Submission saved to profile for user @uid', [
            '@uid' => $current_user->id(),
          ]);
        } catch (\Exception $e) {
          \Drupal::logger('formulario_candidatura_dinamico')->error('Error saving to profile: @message', [
            '@message' => $e->getMessage(),
          ]);
          // Continue anyway - don't fail the submission
        }
      } else {
        \Drupal::logger('formulario_candidatura_dinamico')->warning('User is anonymous, cannot save to profile');
      }

      // Handle Mailchimp if enabled
      if ($form->get('mailchimp_enabled') && !empty($form->get('mailchimp_list_id'))) {
        $this->subscribeToMailchimp($submission_data, $form->get('mailchimp_list_id'));
      }

      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Form submitted successfully',
        'submission_id' => 'direct_' . time(),
      ], 200, $response_headers);

    } catch (\Exception $e) {
      restore_error_handler();
      
      \Drupal::logger('formulario_candidatura_dinamico')->error('Form submission error: @message - @trace', [
        '@message' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);
      
      $error_message = $e->getMessage();
      
      // Check if it's a database table error
      if (strpos($error_message, "doesn't exist") !== FALSE || strpos($error_message, "Table") !== FALSE) {
        $error_message = "Database table 'dynamic_form_submission' does not exist. Please run: drush updb -y";
      }
      
      return new JsonResponse([
        'error' => $error_message,
        'details' => $e->getMessage(),
        'type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ], 500, $response_headers);
    } catch (\Throwable $e) {
      restore_error_handler();
      
      return new JsonResponse([
        'error' => 'Fatal error: ' . $e->getMessage(),
        'details' => $e->getMessage(),
        'type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ], 500, $response_headers);
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
    // Bloco removido: lógica de dynamic_form_block e layout builder.
    return new JsonResponse([
      'forms' => [],
      'debug' => ['info' => 'dynamic_form_block removido'],
    ]);
  }

}
