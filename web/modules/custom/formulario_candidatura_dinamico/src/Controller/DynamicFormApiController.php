<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileExists;

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
    // Add CORS headers first
    $response_headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'POST, OPTIONS',
      'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
      'Access-Control-Allow-Credentials' => 'true',
    ];
    
    // Handle preflight requests
    if ($request->getMethod() === 'OPTIONS') {
      return new JsonResponse(null, 200, $response_headers);
    }
    
    // Log request headers for debugging
    $auth_header = $request->headers->get('Authorization');
    \Drupal::logger('formulario_candidatura_dinamico')->info('Headers received - Authorization: @auth, Content-Type: @ct, All headers: @all', [
      '@auth' => $auth_header ? substr($auth_header, 0, 50) . '...' : 'NONE',
      '@ct' => $request->headers->get('Content-Type'),
      '@all' => implode(', ', array_keys($request->headers->all())),
    ]);
    
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
            try {
              // Save the file
              $directory = 'public://formularios-dinamicos';
              $file_system = \Drupal::service('file_system');
              
              // Ensure directory exists with proper permissions
              $file_system->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
              
              // Get real directory path to verify it exists
              $real_path = $file_system->realpath($directory);
              \Drupal::logger('formulario_candidatura_dinamico')->info('Directory path: @path exists: @exists', [
                '@path' => $real_path,
                '@exists' => is_dir($real_path) ? 'yes' : 'no',
              ]);
              
              $file_entity = \Drupal::service('file.repository')->writeData(
                file_get_contents($file->getRealPath()),
                $directory . '/' . $file->getClientOriginalName(),
                FileExists::Rename
              );
              
              if ($file_entity) {
                $file_entity->setPermanent();
                $file_entity->save();
                
                $submission_data[$field['label']] = [
                  'type' => 'file',
                  'value' => $file_entity->id(),
                  'filename' => $file_entity->getFilename(),
                  'uri' => $file_entity->getFileUri(),
                ];
              }
            } catch (\Exception $e) {
              \Drupal::logger('formulario_candidatura_dinamico')->error('File upload error: @message', [
                '@message' => $e->getMessage(),
              ]);
              
              // Try with Replace instead of Rename
              try {
                $file_entity = \Drupal::service('file.repository')->writeData(
                  file_get_contents($file->getRealPath()),
                  $directory . '/' . time() . '_' . $file->getClientOriginalName(),
                  FileExists::Replace
                );
                
                if ($file_entity) {
                  $file_entity->setPermanent();
                  $file_entity->save();
                  
                  $submission_data[$field['label']] = [
                    'type' => 'file',
                    'value' => $file_entity->id(),
                    'filename' => $file_entity->getFilename(),
                    'uri' => $file_entity->getFileUri(),
                  ];
                }
              } catch (\Exception $e2) {
                // If still fails, store error message
                $submission_data[$field['label']] = 'Error uploading file: ' . $e2->getMessage();
              }
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
            \Drupal::logger('formulario_candidatura_dinamico')->info('Profile has field_submissions, existing count: @count', [
              '@count' => count($existing_data),
            ]);
          } else {
            \Drupal::logger('formulario_candidatura_dinamico')->error('Profile does NOT have field_submissions field!');
          }
          
          // Add new submission
          $new_submission = [
            'webform_id' => $form_id,
            'submission_id' => 'direct_' . time(),
            'timestamp' => time(),
            'email' => $email,
            'data' => $submission_data,
          ];
          $existing_data[] = $new_submission;
          
          \Drupal::logger('formulario_candidatura_dinamico')->info('New submission data: @data', [
            '@data' => json_encode($new_submission),
          ]);
          
          // Update profile only if field exists
          if ($profile->hasField('field_submissions')) {
            $json_data = json_encode($existing_data);
            $profile->set('field_submissions', $json_data);
            \Drupal::logger('formulario_candidatura_dinamico')->info('Set field_submissions to JSON (length: @length)', [
              '@length' => strlen($json_data),
            ]);
          }
          
          $profile->save();
          
          \Drupal::logger('formulario_candidatura_dinamico')->info('Submission saved to profile for user @uid. Total submissions: @count', [
            '@uid' => $current_user->id(),
            '@count' => count($existing_data),
          ]);
        } catch (\Exception $e) {
          \Drupal::logger('formulario_candidatura_dinamico')->error('Error saving to profile: @message at @file:@line. Trace: @trace', [
            '@message' => $e->getMessage(),
            '@file' => $e->getFile(),
            '@line' => $e->getLine(),
            '@trace' => substr($e->getTraceAsString(), 0, 500),
          ]);
          
          // Return error instead of continuing
          return new JsonResponse([
            'error' => 'Failed to save submission',
            'details' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
          ], 500, $response_headers);
        }
      } else {
        \Drupal::logger('formulario_candidatura_dinamico')->warning('User is anonymous, cannot save to profile');
      }

      // Also create a DynamicFormSubmission entity for the approval system
      $submission_entity_id = null;
      try {
        $submission_entity = \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission::create([
          'form_id' => $form_id,
          'email' => $email,
          'data' => $submission_data,
          'approval_status' => 'pending',
        ]);
        $submission_entity->save();
        $submission_entity_id = $submission_entity->id();
        
        \Drupal::logger('formulario_candidatura_dinamico')->info('Created DynamicFormSubmission entity @id for email @email', [
          '@id' => $submission_entity_id,
          '@email' => $email,
        ]);
        
        // Update the profile submission with the actual entity ID for better matching
        if (!$current_user->isAnonymous() && $submission_entity_id) {
          try {
            $profile_storage = $this->entityTypeManager->getStorage('profile');
            $profiles = $profile_storage->loadByProperties([
              'uid' => $current_user->id(),
              'type' => 'user_submissions',
            ]);
            
            if (!empty($profiles)) {
              $profile = reset($profiles);
              if ($profile->hasField('field_submissions')) {
                $field_value = $profile->get('field_submissions')->value;
                if ($field_value) {
                  $existing_data = json_decode($field_value, TRUE);
                  if (is_array($existing_data) && !empty($existing_data)) {
                    // Update the last submission with the entity ID
                    $last_index = count($existing_data) - 1;
                    $existing_data[$last_index]['submission_id'] = (string)$submission_entity_id;
                    $existing_data[$last_index]['entity_id'] = $submission_entity_id;
                    
                    $profile->set('field_submissions', json_encode($existing_data));
                    $profile->save();
                    
                    \Drupal::logger('formulario_candidatura_dinamico')->info('Updated profile submission with entity ID @id', [
                      '@id' => $submission_entity_id,
                    ]);
                  }
                }
              }
            }
          } catch (\Exception $e) {
            \Drupal::logger('formulario_candidatura_dinamico')->error('Error updating profile with entity ID: @message', [
              '@message' => $e->getMessage(),
            ]);
          }
        }
      } catch (\Exception $e) {
        \Drupal::logger('formulario_candidatura_dinamico')->error('Error creating DynamicFormSubmission entity: @message', [
          '@message' => $e->getMessage(),
        ]);
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
      \Drupal::logger('formulario_candidatura_dinamico')->error('Form submission error: @message. File: @file:@line. Trace: @trace', [
        '@message' => $e->getMessage(),
        '@file' => $e->getFile(),
        '@line' => $e->getLine(),
        '@trace' => $e->getTraceAsString(),
      ]);
      
      return new JsonResponse([
        'error' => $e->getMessage(),
        'details' => $e->getMessage(),
        'type' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", substr($e->getTraceAsString(), 0, 1000)),
      ], 500, $response_headers);
    } catch (\Throwable $e) {
      \Drupal::logger('formulario_candidatura_dinamico')->error('Fatal error: @message', [
        '@message' => $e->getMessage(),
      ]);
      
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
    $node_entity = \Drupal::entityTypeManager()->getStorage('node')->load($node);
    
    if (!$node_entity) {
      \Drupal::logger('formulario_candidatura_dinamico')->error('Article layout API: Node @nid not found', ['@nid' => $node]);
      return new JsonResponse(['error' => 'Node not found', 'forms' => []], 404);
    }

    \Drupal::logger('formulario_candidatura_dinamico')->info('Article layout API called for node @nid', ['@nid' => $node]);

    $forms = [];
    
    // Check if node has layout builder enabled
    if ($node_entity->hasField('layout_builder__layout')) {
      $sections = $node_entity->get('layout_builder__layout')->getSections();
      
      \Drupal::logger('formulario_candidatura_dinamico')->info('Node @nid has @count layout sections', [
        '@nid' => $node,
        '@count' => count($sections),
      ]);
      
      foreach ($sections as $section) {
        $components = $section->getComponents();
        
        foreach ($components as $component) {
          $plugin = $component->getPlugin();
          $plugin_id = $plugin->getPluginId();
          
          \Drupal::logger('formulario_candidatura_dinamico')->info('Found block: @id', ['@id' => $plugin_id]);
          
          // Look for any blocks containing "formulario", "dynamic", or specific block IDs
          if (
            strpos(strtolower($plugin_id), 'formulario') !== FALSE || 
            strpos(strtolower($plugin_id), 'dynamic') !== FALSE ||
            strpos(strtolower($plugin_id), 'candidatura') !== FALSE ||
            strpos($plugin_id, 'inline_block') !== FALSE
          ) {
            $configuration = $plugin->getConfiguration();
            
            // Get the block label/title
            $label = 'Dynamic Form';
            if (isset($configuration['label'])) {
              $label = $configuration['label'];
            } elseif (method_exists($plugin, 'label')) {
              $label = $plugin->label();
            }
            
            // Try to get the form ID from configuration
            $form_id = null;
            if (isset($configuration['form_id'])) {
              $form_id = $configuration['form_id'];
            } elseif (isset($configuration['block_serialized'])) {
              // For inline blocks, the content might be serialized
              $block_content = unserialize($configuration['block_serialized']);
              if (isset($block_content['form_id'])) {
                $form_id = $block_content['form_id'][0]['value'] ?? null;
              }
            }
            
            $form_data = [
              'id' => $plugin_id,
              'label' => $label,
              'plugin_id' => $plugin_id,
              'form_id' => $form_id,
            ];
            
            $forms[] = $form_data;
            
            \Drupal::logger('formulario_candidatura_dinamico')->info('Found form block: @id with label: @label, form_id: @form_id', [
              '@id' => $plugin_id,
              '@label' => $label,
              '@form_id' => $form_id ?? 'null',
            ]);
          }
        }
      }
    } else {
      \Drupal::logger('formulario_candidatura_dinamico')->warning('Node @nid does not have layout_builder__layout field', ['@nid' => $node]);
    }
    
    \Drupal::logger('formulario_candidatura_dinamico')->info('Article @nid: Returning @count form blocks', [
      '@nid' => $node,
      '@count' => count($forms),
    ]);
    
    return new JsonResponse(['forms' => $forms]);
  }

}
