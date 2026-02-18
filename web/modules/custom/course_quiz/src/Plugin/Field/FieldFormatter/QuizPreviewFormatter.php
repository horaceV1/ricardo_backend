<?php

namespace Drupal\course_quiz\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'course_quiz_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "course_quiz_formatter",
 *   label = @Translation("Quiz Preview"),
 *   field_types = {
 *     "text_long",
 *     "string_long"
 *   }
 * )
 */
class QuizPreviewFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $value = $item->value;
      if (empty($value)) {
        continue;
      }

      $questions = json_decode($value, TRUE);
      if (!is_array($questions) || empty($questions)) {
        continue;
      }

      $rows = [];
      foreach ($questions as $i => $question) {
        $type_label = $question['type'] === 'multiple_choice' ? 'Escolha Múltipla' : 'Resposta de Texto';
        $required = !empty($question['required']) ? '✓' : '✗';
        $points = $question['points'] ?? 1;
        
        $rows[] = [
          ($i + 1),
          $question['question'],
          $type_label,
          $points,
          $required,
        ];
      }

      $elements[$delta] = [
        '#type' => 'table',
        '#header' => ['#', 'Pergunta', 'Tipo', 'Pontos', 'Obrigatória'],
        '#rows' => $rows,
        '#attributes' => ['class' => ['quiz-preview-table']],
        '#prefix' => '<h3>Quiz / Perguntas (' . count($questions) . ')</h3>',
      ];
    }

    return $elements;
  }

}
