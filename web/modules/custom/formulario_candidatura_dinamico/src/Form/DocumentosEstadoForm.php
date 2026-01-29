<?php
namespace Drupal\formulario_candidatura_dinamico\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

class DocumentosEstadoForm extends FormBase {

  public function getFormId() {
    return 'formulario_candidatura_dinamico_documentos_estado_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $connection = Database::getConnection();

    $query = $connection->select('dynamic_form_submission', 'd')
      ->fields('d')
      ->execute();
    $submissoes = $query->fetchAll();

    $options = [
      'submetido' => 'Submetido',
      'em_falta' => 'Em falta',
      'caducado' => 'Caducado',
      'validado' => 'Validado',
    ];

    foreach ($submissoes as $sub) {
      $data = @unserialize($sub->data);
      if (!empty($data['documentos'])) {
        foreach ($data['documentos'] as $idx => $doc) {
          $form['estado_' . $sub->id . '_' . $idx] = [
            '#type' => 'select',
            '#title' => $this->t('Submissão @id - Documento: @nome', ['@id' => $sub->id, '@nome' => $doc['filename'] ?? 'Sem nome']),
            '#options' => $options,
            '#default_value' => $doc['estado'] ?? 'submetido',
          ];
        }
      }
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar estados'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    foreach ($form_state->getValues() as $key => $value) {
      if (preg_match('/^estado_(\d+)_(\d+)$/', $key, $matches)) {
        $sub_id = $matches[1];
        $doc_idx = $matches[2];

        // Vai buscar a submissão.
        $sub = $connection->select('dynamic_form_submission', 'd')
          ->fields('d')
          ->condition('id', $sub_id)
          ->execute()
          ->fetchObject();

        if ($sub) {
          $data = @unserialize($sub->data);
          if (isset($data['documentos'][$doc_idx])) {
            $data['documentos'][$doc_idx]['estado'] = $value;
            // Atualiza o campo data.
            $connection->update('dynamic_form_submission')
              ->fields(['data' => serialize($data)])
              ->condition('id', $sub_id)
              ->execute();
          }
        }
      }
    }
    $this->messenger()->addStatus($this->t('Estados atualizados com sucesso.'));
  }
}