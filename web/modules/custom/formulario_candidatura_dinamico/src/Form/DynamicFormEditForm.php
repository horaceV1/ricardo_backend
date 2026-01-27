<?php
namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

class DynamicFormEditForm extends EntityForm {
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->getEntity();
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nome do formulário'),
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\formulario_candidatura_dinamico\Entity\DynamicForm::load',
      ],
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    ];

    // Campos do formulário
    $form['fields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Campos do formulário'),
      '#tree' => TRUE,
    ];

    $fields = $entity->getFields();
    $num_fields = $form_state->get('num_fields');
    if ($num_fields === NULL) {
      $num_fields = !empty($fields) ? count($fields) : 1;
      $form_state->set('num_fields', $num_fields);
    }

    for ($i = 0; $i < $num_fields; $i++) {
      $form['fields'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Campo @num', ['@num' => $i + 1]),
        '#open' => TRUE,
      ];

      $form['fields'][$i]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#default_value' => $fields[$i]['label'] ?? '',
      ];

      $form['fields'][$i]['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Tipo'),
        '#options' => [
          'texto' => $this->t('Texto'),
          'imagem' => $this->t('Imagem'),
          'documento' => $this->t('Documento'),
        ],
        '#default_value' => $fields[$i]['type'] ?? 'texto',
      ];

      $form['fields'][$i]['required'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Obrigatório'),
        '#default_value' => $fields[$i]['required'] ?? FALSE,
      ];

      $form['fields'][$i]['link'] = [
        '#type' => 'url',
        '#title' => $this->t('Onde obter este documento (link)'),
        '#description' => $this->t('Link para o site específico onde obter o documento'),
        '#default_value' => $fields[$i]['link'] ?? '',
        '#states' => [
          'visible' => [
            ':input[name="fields[' . $i . '][type]"]' => ['value' => 'documento'],
          ],
        ],
      ];
    }

    $form['fields']['actions'] = [
      '#type' => 'actions',
    ];
    $form['fields']['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Adicionar campo'),
      '#submit' => ['::addField'],
      '#ajax' => [
        'callback' => '::ajaxRefreshFields',
        'wrapper' => 'fields-wrapper',
      ],
    ];

    $form['fields']['#prefix'] = '<div id="fields-wrapper">';
    $form['fields']['#suffix'] = '</div>';

    // Mailchimp integration settings
    $form['mailchimp'] = [
      '#type' => 'details',
      '#title' => $this->t('Integração Mailchimp'),
      '#open' => FALSE,
      '#description' => $this->t('Configure a autenticação OAuth do Mailchimp em /admin/config/services/mailchimp primeiro.'),
    ];

    $form['mailchimp']['mailchimp_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ativar integração com Mailchimp'),
      '#default_value' => $entity->isMailchimpEnabled(),
    ];

    // Get available Mailchimp lists
    $list_options = [];
    if (\Drupal::moduleHandler()->moduleExists('mailchimp')) {
      $lists = mailchimp_get_lists();
      foreach ($lists as $list_id => $list) {
        $list_options[$list_id] = $list->name;
      }
    }

    $form['mailchimp']['mailchimp_list_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Lista Mailchimp'),
      '#options' => $list_options,
      '#empty_option' => $this->t('- Selecione -'),
      '#default_value' => $entity->getMailchimpListId(),
      '#states' => [
        'visible' => [
          ':input[name="mailchimp_enabled"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="mailchimp_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  public function addField(array &$form, FormStateInterface $form_state) {
    $num_fields = $form_state->get('num_fields');
    $form_state->set('num_fields', $num_fields + 1);
    $form_state->setRebuild();
  }

  public function ajaxRefreshFields(array &$form, FormStateInterface $form_state) {
    return $form['fields'];
  }

  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    
    // Processar campos
    $fields = $form_state->getValue('fields');
    unset($fields['actions']);
    $fields = array_values(array_filter($fields, function($field) {
      return !empty($field['label']);
    }));
    
    $entity->setFields($fields);
    
    // Save Mailchimp settings
    $entity->set('mailchimp_enabled', $form_state->getValue('mailchimp_enabled'));
    $entity->set('mailchimp_list_id', $form_state->getValue('mailchimp_list_id'));
    
    $status = $entity->save();
    
    $this->messenger()->addMessage($this->t('Formulário @label foi guardado.', [
      '@label' => $entity->label(),
    ]));
    
    $form_state->setRedirect('entity.dynamic_form.collection');
  }
}
