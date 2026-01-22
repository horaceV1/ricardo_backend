<?php
namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicForm;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for submitting dynamic forms.
 */
class DynamicFormSubmissionForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dynamic_form_submission_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $form_id = NULL) {
    if (!$form_id) {
      return $form;
    }

    $dynamic_form = DynamicForm::load($form_id);
    if (!$dynamic_form) {
      $this->messenger()->addError($this->t('Formulário não encontrado.'));
      return $form;
    }

    $form_state->set('dynamic_form_id', $form_id);
    $fields = $dynamic_form->getFields();

    if (empty($fields)) {
      $form['empty'] = [
        '#markup' => '<p>' . $this->t('Este formulário não tem campos definidos.') . '</p>',
      ];
      return $form;
    }

    $form['#attached']['library'][] = 'formulario_candidatura_dinamico/form_submission';

    // Email field (required)
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#weight' => -100,
    ];

    foreach ($fields as $index => $field) {
      $field_key = 'field_' . $index;
      $weight = $index;

      switch ($field['type']) {
        case 'texto':
          $form[$field_key] = [
            '#type' => 'textfield',
            '#title' => $field['label'],
            '#required' => !empty($field['required']),
            '#weight' => $weight,
          ];
          break;

        case 'imagem':
          $form[$field_key] = [
            '#type' => 'file',
            '#title' => $field['label'],
            '#weight' => $weight,
          ];
          break;

        case 'documento':
          $form[$field_key] = [
            '#type' => 'file',
            '#title' => $field['label'],
            '#weight' => $weight,
          ];

          if (!empty($field['link'])) {
            $form[$field_key . '_link'] = [
              '#type' => 'markup',
              '#markup' => '<div class="document-help-link"><a href="' . $field['link'] . '" target="_blank">' . 
                           $this->t('Onde obter este documento') . '</a></div>',
              '#weight' => $weight + 0.1,
            ];
          }
          break;
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submeter'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_id = $form_state->get('dynamic_form_id');
    $values = $form_state->getValues();
    
    $email = $values['email'];
    unset($values['email'], $values['submit'], $values['form_build_id'], $values['form_token'], $values['form_id'], $values['op']);

    // Get uploaded files
    $files = $this->getRequest()->files->get('files', []);
    
    // Process fields
    $data = [];
    foreach ($values as $key => $value) {
      // Skip non-field keys and link fields
      if (strpos($key, 'field_') !== 0 || strpos($key, '_link') !== false) {
        continue;
      }
      
      // Check if there's an uploaded file for this field
      if (isset($files[$key]) && $files[$key]->isValid()) {
        $uploaded_file = $files[$key];
        
        // Determine directory based on field type
        $directory = strpos($key, 'imagem') !== false || strpos($key, 'image') !== false
          ? 'public://formularios/imagens'
          : 'public://formularios/documentos';
        
        // Prepare directory
        \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
        
        // Save file
        $file = \Drupal::service('file.repository')->writeData(
          file_get_contents($uploaded_file->getPathname()),
          $directory . '/' . $uploaded_file->getClientOriginalName(),
          \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME
        );
        
        if ($file) {
          $file->setPermanent();
          $file->save();
          
          $data[$key] = [
            'fid' => $file->id(),
            'uri' => $file->getFileUri(),
            'filename' => $file->getFilename(),
          ];
        }
      } else {
        // It's a text field
        $data[$key] = $value;
      }
    }

    // Create submission entity
    $submission = DynamicFormSubmission::create([
      'form_id' => $form_id,
      'email' => $email,
      'data' => $data,
    ]);
    $submission->save();

    $this->messenger()->addMessage($this->t('A sua candidatura foi submetida com sucesso!'));
    
    // Optionally redirect
    // $form_state->setRedirect('<front>');
  }
}
