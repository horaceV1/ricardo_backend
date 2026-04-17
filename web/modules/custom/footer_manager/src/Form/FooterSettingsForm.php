<?php

namespace Drupal\footer_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FooterSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['footer_manager.settings'];
  }

  public function getFormId() {
    return 'footer_manager_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('footer_manager.settings');

    // Branding section
    $form['branding'] = [
      '#type' => 'details',
      '#title' => $this->t('Branding'),
      '#open' => TRUE,
    ];
    $form['branding']['brand_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título'),
      '#default_value' => $config->get('brand_title') ?? 'Clínica do Empresário',
    ];
    $form['branding']['brand_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Descrição'),
      '#default_value' => $config->get('brand_description') ?? 'Consultoria especializada e soluções práticas para o crescimento do seu negócio.',
    ];
    $form['branding']['copyright'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Copyright'),
      '#default_value' => $config->get('copyright') ?? '© 2026 Clínica do Empresário. Todos os direitos reservados.',
    ];

    // Social links
    $form['social'] = [
      '#type' => 'details',
      '#title' => $this->t('Redes Sociais'),
      '#open' => TRUE,
    ];
    $form['social']['facebook_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Facebook URL'),
      '#default_value' => $config->get('facebook_url') ?? '',
      '#description' => $this->t('Deixar vazio para esconder o ícone.'),
    ];
    $form['social']['instagram_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Instagram URL'),
      '#default_value' => $config->get('instagram_url') ?? '',
      '#description' => $this->t('Deixar vazio para esconder o ícone.'),
    ];

    // Columns (up to 4)
    $columns = $config->get('columns') ?? [];
    $num_columns = $form_state->get('num_columns');
    if ($num_columns === NULL) {
      $num_columns = count($columns) ?: 3;
      $form_state->set('num_columns', $num_columns);
    }

    $form['columns_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Colunas de Links'),
      '#open' => TRUE,
      '#prefix' => '<div id="columns-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($c = 0; $c < $num_columns; $c++) {
      $col = $columns[$c] ?? [];
      $form['columns_wrapper']['column_' . $c] = [
        '#type' => 'details',
        '#title' => $this->t('Coluna @num', ['@num' => $c + 1]),
        '#open' => TRUE,
      ];
      $form['columns_wrapper']['column_' . $c]['title_' . $c] = [
        '#type' => 'textfield',
        '#title' => $this->t('Título da Coluna'),
        '#default_value' => $col['title'] ?? '',
      ];

      $links = $col['links'] ?? [];
      $num_links = $form_state->get('num_links_' . $c);
      if ($num_links === NULL) {
        $num_links = count($links) ?: 1;
        $form_state->set('num_links_' . $c, $num_links);
      }

      $form['columns_wrapper']['column_' . $c]['links_' . $c] = [
        '#type' => 'table',
        '#header' => [$this->t('Texto'), $this->t('URL'), $this->t('Abrir em nova janela')],
        '#prefix' => '<div id="links-wrapper-' . $c . '">',
        '#suffix' => '</div>',
      ];

      for ($l = 0; $l < $num_links; $l++) {
        $link = $links[$l] ?? [];
        $form['columns_wrapper']['column_' . $c]['links_' . $c][$l]['text'] = [
          '#type' => 'textfield',
          '#default_value' => $link['text'] ?? '',
          '#size' => 30,
        ];
        $form['columns_wrapper']['column_' . $c]['links_' . $c][$l]['url'] = [
          '#type' => 'textfield',
          '#default_value' => $link['url'] ?? '',
          '#size' => 40,
        ];
        $form['columns_wrapper']['column_' . $c]['links_' . $c][$l]['external'] = [
          '#type' => 'checkbox',
          '#default_value' => $link['external'] ?? FALSE,
        ];
      }

      $form['columns_wrapper']['column_' . $c]['add_link_' . $c] = [
        '#type' => 'submit',
        '#value' => $this->t('+ Adicionar link à Coluna @num', ['@num' => $c + 1]),
        '#submit' => ['::addLink'],
        '#ajax' => [
          'callback' => '::ajaxRefresh',
          'wrapper' => 'columns-wrapper',
        ],
        '#name' => 'add_link_' . $c,
        '#limit_validation_errors' => [],
      ];
    }

    $form['columns_wrapper']['add_column'] = [
      '#type' => 'submit',
      '#value' => $this->t('+ Adicionar Coluna'),
      '#submit' => ['::addColumn'],
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => 'columns-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    if ($num_columns > 1) {
      $form['columns_wrapper']['remove_column'] = [
        '#type' => 'submit',
        '#value' => $this->t('- Remover Última Coluna'),
        '#submit' => ['::removeColumn'],
        '#ajax' => [
          'callback' => '::ajaxRefresh',
          'wrapper' => 'columns-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  public function addLink(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $col_index = str_replace('add_link_', '', $trigger['#name']);
    $num = $form_state->get('num_links_' . $col_index) ?? 1;
    $form_state->set('num_links_' . $col_index, $num + 1);
    $form_state->setRebuild();
  }

  public function addColumn(array &$form, FormStateInterface $form_state) {
    $num = $form_state->get('num_columns') ?? 3;
    $form_state->set('num_columns', $num + 1);
    $form_state->setRebuild();
  }

  public function removeColumn(array &$form, FormStateInterface $form_state) {
    $num = $form_state->get('num_columns') ?? 3;
    if ($num > 1) {
      $form_state->set('num_columns', $num - 1);
    }
    $form_state->setRebuild();
  }

  public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    return $form['columns_wrapper'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('footer_manager.settings');

    $config->set('brand_title', $form_state->getValue('brand_title'));
    $config->set('brand_description', $form_state->getValue('brand_description'));
    $config->set('copyright', $form_state->getValue('copyright'));
    $config->set('facebook_url', $form_state->getValue('facebook_url') ?? '');
    $config->set('instagram_url', $form_state->getValue('instagram_url') ?? '');

    $num_columns = $form_state->get('num_columns') ?? 3;
    $columns = [];
    for ($c = 0; $c < $num_columns; $c++) {
      $title = $form_state->getValue('title_' . $c) ?? '';
      $links_data = $form_state->getValue('links_' . $c) ?? [];
      $links = [];
      foreach ($links_data as $link) {
        if (!empty($link['text']) || !empty($link['url'])) {
          $links[] = [
            'text' => $link['text'] ?? '',
            'url' => $link['url'] ?? '',
            'external' => !empty($link['external']),
          ];
        }
      }
      if (!empty($title) || !empty($links)) {
        $columns[] = [
          'title' => $title,
          'links' => $links,
        ];
      }
    }
    $config->set('columns', $columns);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
