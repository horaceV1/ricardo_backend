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

    // Email field (required) - appears after first field
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#weight' => 0.5,
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

    // RGPD checkbox with modal
    $form['rgpd_consent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Aceito as <a href="#" class="open-rgpd-modal">normas de proteção de dados (RGPD)</a> e autorizo o tratamento dos meus dados pessoais.'),
      '#required' => TRUE,
      '#weight' => 999,
    ];

    // RGPD modal content
    $form['rgpd_modal'] = [
      '#type' => 'markup',
      '#markup' => '
        <div id="rgpd-modal" class="rgpd-modal">
          <div class="rgpd-modal-content">
            <span class="rgpd-modal-close">&times;</span>
            <h2>' . $this->t('Política de Proteção de Dados (RGPD)') . '</h2>
            
            <div class="rgpd-section">
              <h3>' . $this->t('Finalidade do Tratamento de Dados') . '</h3>
              <p>' . $this->t('Os dados pessoais recolhidos através deste formulário serão utilizados exclusivamente para responder ao seu pedido e para entrar em contacto consigo.') . '</p>
            </div>

            <div class="rgpd-section">
              <h3>' . $this->t('Prazo de Conservação') . '</h3>
              <p>' . $this->t('Os seus dados pessoais serão armazenados na nossa base de dados durante o período necessário para dar resposta ao seu pedido, com um máximo de 2 anos.') . '</p>
            </div>

            <div class="rgpd-section">
              <h3>' . $this->t('Eliminação de Dados') . '</h3>
              <p>' . $this->t('Após o prazo de conservação, os seus dados serão eliminados de forma segura e permanente da nossa base de dados. Pode solicitar a eliminação antecipada dos seus dados através do email de contacto disponibilizado no nosso site.') . '</p>
            </div>

            <div class="rgpd-section">
              <h3>' . $this->t('Direitos do Titular') . '</h3>
              <p>' . $this->t('Tem o direito de aceder, retificar, apagar e limitar o tratamento dos seus dados pessoais, bem como o direito à portabilidade dos mesmos.') . '</p>
            </div>
          </div>
        </div>
      ',
      '#weight' => 998,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 1000,
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    
    $email = $form_state->getValue('email');
    
    // Additional email validation
    if (!empty($email)) {
      // Check if email format is valid (basic check)
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_state->setErrorByName('email', $this->t('Por favor, introduza um endereço de email válido.'));
      }
      
      // Check for common typos in email domains
      $common_typos = [
        'gmial.com' => 'gmail.com',
        'gmai.com' => 'gmail.com',
        'hotmial.com' => 'hotmail.com',
        'outlok.com' => 'outlook.com',
        'yaho.com' => 'yahoo.com',
        'yahooo.com' => 'yahoo.com',
      ];
      
      foreach ($common_typos as $typo => $correct) {
        if (stripos($email, '@' . $typo) !== false) {
          $form_state->setErrorByName('email', $this->t('Verificou o seu email? Talvez queira dizer @correct em vez de @typo', [
            '@correct' => $correct,
            '@typo' => $typo,
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_id = $form_state->get('dynamic_form_id');
    $values = $form_state->getValues();
    
    $email = $values['email'];
    unset($values['email'], $values['submit'], $values['form_build_id'], $values['form_token'], $values['form_id'], $values['op'], $values['rgpd_consent'], $values['rgpd_modal']);

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

    // Mailchimp integration
    $dynamic_form = DynamicForm::load($form_id);
    if ($dynamic_form && $dynamic_form->isMailchimpEnabled()) {
      $this->subscribeToMailchimp(
        $email,
        $dynamic_form->getMailchimpListId(),
        $data
      );
    }

    $this->messenger()->addMessage($this->t('Os seus dados foram submetidos com sucesso. Será contactado pela nossa equipa muito brevemente.'));
    
    // Optionally redirect
    // $form_state->setRedirect('<front>');
  }

  /**
   * Subscribe email to Mailchimp list using Mailchimp module.
   */
  protected function subscribeToMailchimp($email, $list_id, $data = []) {
    if (empty($list_id)) {
      return;
    }

    try {
      // Check if Mailchimp module is available
      if (!\Drupal::moduleHandler()->moduleExists('mailchimp')) {
        \Drupal::logger('formulario_candidatura_dinamico')->warning('Mailchimp module is not enabled.');
        return;
      }

      // Load the Mailchimp list
      $lists = mailchimp_get_lists();
      if (!isset($lists[$list_id])) {
        \Drupal::logger('formulario_candidatura_dinamico')->error('Mailchimp list not found: @list_id', [
          '@list_id' => $list_id,
        ]);
        return;
      }

      // Prepare merge fields - only send simple text values
      // Skip file fields (images, documents) as they contain arrays with fid, uri, filename
      $merge_vars = [];
      foreach ($data as $key => $value) {
        // Only include simple string values (text fields)
        // Skip arrays (file fields) and empty values
        if (is_string($value) && !empty($value)) {
          // Remove 'field_' prefix and convert to uppercase for Mailchimp
          $field_name = strtoupper(str_replace('field_', '', $key));
          $merge_vars[$field_name] = $value;
        }
      }

      // Subscribe to list with single opt-in (immediate subscription)
      // No confirmation email - subscriber is added immediately to the list
      // Use the automation in Mailchimp to send welcome emails
      $result = mailchimp_subscribe($list_id, $email, $merge_vars, [], FALSE);
      
      if (!$result) {
        \Drupal::logger('formulario_candidatura_dinamico')->error('Failed to subscribe @email to Mailchimp list @list_id', [
          '@email' => $email,
          '@list_id' => $list_id,
        ]);
      }
      else {
        \Drupal::logger('formulario_candidatura_dinamico')->info('Successfully subscribed @email to Mailchimp list @list_id', [
          '@email' => $email,
          '@list_id' => $list_id,
        ]);
      }
    } catch (\Exception $e) {
      \Drupal::logger('formulario_candidatura_dinamico')->error('Mailchimp subscription error: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }
}
