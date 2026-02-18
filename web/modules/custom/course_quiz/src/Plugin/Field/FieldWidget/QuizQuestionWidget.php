<?php

namespace Drupal\course_quiz\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'course_quiz_widget' widget.
 *
 * @FieldWidget(
 *   id = "course_quiz_widget",
 *   label = @Translation("Quiz Question Builder"),
 *   field_types = {
 *     "text_long",
 *     "string_long"
 *   }
 * )
 */
class QuizQuestionWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Decode existing questions from JSON.
    $existing_value = $items[$delta]->value ?? '';
    $questions = [];
    if (!empty($existing_value)) {
      $decoded = json_decode($existing_value, TRUE);
      if (is_array($decoded)) {
        $questions = $decoded;
      }
    }

    // Check if we need to add/remove questions via form state.
    $field_name = $this->fieldDefinition->getName();
    $parents_key = $field_name . '_' . $delta;

    // Get the number of questions from form state or existing data.
    $num_questions = $form_state->get($parents_key . '_num_questions');
    if ($num_questions === NULL) {
      $num_questions = count($questions) > 0 ? count($questions) : 0;
      $form_state->set($parents_key . '_num_questions', $num_questions);
    }

    // Check for question removal.
    $remove_index = $form_state->get($parents_key . '_remove_index');
    if ($remove_index !== NULL) {
      // Remove the question and reindex.
      if (isset($questions[$remove_index])) {
        array_splice($questions, $remove_index, 1);
      }
      $num_questions = max(0, $num_questions - 1);
      $form_state->set($parents_key . '_num_questions', $num_questions);
      $form_state->set($parents_key . '_remove_index', NULL);
    }

    $wrapper_id = 'quiz-questions-wrapper-' . $delta;

    $element['#type'] = 'details';
    $element['#title'] = new TranslatableMarkup('Quiz / Perguntas');
    $element['#open'] = $num_questions > 0;
    $element['#description'] = new TranslatableMarkup('Configure perguntas de escolha múltipla e resposta de texto para este módulo do curso.');

    $element['questions_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => $wrapper_id],
      '#tree' => TRUE,
    ];

    for ($i = 0; $i < $num_questions; $i++) {
      $question = $questions[$i] ?? [];
      $question_type = $question['type'] ?? 'multiple_choice';
      
      // Check form state for type changes.
      $state_type = $form_state->getValue([
        $field_name, $delta, 'questions_wrapper', 'question_' . $i, 'question_type',
      ]);
      if ($state_type) {
        $question_type = $state_type;
      }

      $element['questions_wrapper']['question_' . $i] = [
        '#type' => 'details',
        '#title' => new TranslatableMarkup('Pergunta @num', ['@num' => $i + 1]),
        '#open' => TRUE,
        '#attributes' => ['class' => ['quiz-question-item']],
      ];

      $q = &$element['questions_wrapper']['question_' . $i];

      $q['question_type'] = [
        '#type' => 'select',
        '#title' => new TranslatableMarkup('Tipo de Pergunta'),
        '#options' => [
          'multiple_choice' => new TranslatableMarkup('Escolha Múltipla'),
          'text_response' => new TranslatableMarkup('Resposta de Texto'),
        ],
        '#default_value' => $question_type,
        '#ajax' => [
          'callback' => [static::class, 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
      ];

      $q['question_text'] = [
        '#type' => 'textarea',
        '#title' => new TranslatableMarkup('Texto da Pergunta'),
        '#default_value' => $question['question'] ?? '',
        '#required' => FALSE,
        '#rows' => 2,
      ];

      $q['required'] = [
        '#type' => 'checkbox',
        '#title' => new TranslatableMarkup('Obrigatória'),
        '#default_value' => $question['required'] ?? TRUE,
        '#description' => new TranslatableMarkup('Se marcada, o aluno deve responder a esta pergunta.'),
      ];

      $q['points'] = [
        '#type' => 'number',
        '#title' => new TranslatableMarkup('Pontos'),
        '#default_value' => $question['points'] ?? 1,
        '#min' => 0,
        '#max' => 100,
        '#description' => new TranslatableMarkup('Número de pontos para esta pergunta.'),
      ];

      if ($question_type === 'multiple_choice') {
        // Multiple choice options.
        $options = $question['options'] ?? ['', '', '', ''];
        $correct_answer = $question['correct_answer'] ?? 0;

        $q['options_container'] = [
          '#type' => 'fieldset',
          '#title' => new TranslatableMarkup('Opções de Resposta'),
          '#description' => new TranslatableMarkup('Adicione as opções e selecione a resposta correta.'),
        ];

        // Get number of options from form state.
        $num_options_key = $parents_key . '_q' . $i . '_num_options';
        $num_options = $form_state->get($num_options_key);
        if ($num_options === NULL) {
          $num_options = max(count($options), 2);
          $form_state->set($num_options_key, $num_options);
        }

        $options_wrapper_id = 'quiz-options-wrapper-' . $delta . '-' . $i;

        $q['options_container']['options_wrapper'] = [
          '#type' => 'container',
          '#attributes' => ['id' => $options_wrapper_id],
        ];

        $q['options_container']['options_wrapper']['correct_answer'] = [
          '#type' => 'radios',
          '#title' => new TranslatableMarkup('Resposta Correta'),
          '#options' => [],
          '#default_value' => (string) $correct_answer,
        ];

        for ($j = 0; $j < $num_options; $j++) {
          $q['options_container']['options_wrapper']['option_' . $j] = [
            '#type' => 'textfield',
            '#title' => new TranslatableMarkup('Opção @letter', ['@letter' => chr(65 + $j)]),
            '#default_value' => $options[$j] ?? '',
            '#size' => 80,
          ];
          $q['options_container']['options_wrapper']['correct_answer']['#options'][(string) $j] = new TranslatableMarkup('Opção @letter é a correta', ['@letter' => chr(65 + $j)]);
        }

        $q['options_container']['options_wrapper']['add_option'] = [
          '#type' => 'submit',
          '#value' => new TranslatableMarkup('+ Adicionar Opção'),
          '#name' => 'add_option_' . $delta . '_' . $i,
          '#submit' => [[static::class, 'addOptionSubmit']],
          '#ajax' => [
            'callback' => [static::class, 'ajaxRefresh'],
            'wrapper' => $wrapper_id,
          ],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['button--small']],
        ];

        $q['allow_multiple'] = [
          '#type' => 'checkbox',
          '#title' => new TranslatableMarkup('Permitir múltiplas respostas'),
          '#default_value' => $question['allow_multiple'] ?? FALSE,
          '#description' => new TranslatableMarkup('Se marcada, o aluno pode selecionar mais de uma opção.'),
        ];

        // If allow multiple, show multiple correct answers.
        if (!empty($question['allow_multiple'])) {
          $correct_answers = $question['correct_answers'] ?? [];
          $q['options_container']['options_wrapper']['correct_answer']['#type'] = 'checkboxes';
          $q['options_container']['options_wrapper']['correct_answer']['#title'] = new TranslatableMarkup('Respostas Corretas');
          $q['options_container']['options_wrapper']['correct_answer']['#default_value'] = $correct_answers;
        }
      }
      else {
        // Text response fields.
        $q['validation_type'] = [
          '#type' => 'select',
          '#title' => new TranslatableMarkup('Tipo de Validação'),
          '#options' => [
            'none' => new TranslatableMarkup('Sem validação (resposta livre)'),
            'exact_match' => new TranslatableMarkup('Correspondência exata'),
            'contains' => new TranslatableMarkup('Contém texto'),
            'min_length' => new TranslatableMarkup('Comprimento mínimo'),
            'regex' => new TranslatableMarkup('Expressão regular (regex)'),
          ],
          '#default_value' => $question['validation_type'] ?? 'none',
        ];

        $q['expected_answer'] = [
          '#type' => 'textarea',
          '#title' => new TranslatableMarkup('Resposta Esperada'),
          '#default_value' => $question['expected_answer'] ?? '',
          '#rows' => 2,
          '#description' => new TranslatableMarkup('Para correspondência exata: o texto exato esperado. Para "contém": palavras-chave separadas por vírgula. Para regex: a expressão regular. Para comprimento mínimo: o número mínimo de caracteres.'),
          '#states' => [
            'invisible' => [
              ':input[name="' . $field_name . '[' . $delta . '][questions_wrapper][question_' . $i . '][validation_type]"]' => ['value' => 'none'],
            ],
          ],
        ];

        $q['case_sensitive'] = [
          '#type' => 'checkbox',
          '#title' => new TranslatableMarkup('Sensível a maiúsculas/minúsculas'),
          '#default_value' => $question['case_sensitive'] ?? FALSE,
          '#states' => [
            'visible' => [
              [':input[name="' . $field_name . '[' . $delta . '][questions_wrapper][question_' . $i . '][validation_type]"]' => ['value' => 'exact_match']],
              [':input[name="' . $field_name . '[' . $delta . '][questions_wrapper][question_' . $i . '][validation_type]"]' => ['value' => 'contains']],
            ],
          ],
        ];

        $q['max_length'] = [
          '#type' => 'number',
          '#title' => new TranslatableMarkup('Comprimento máximo'),
          '#default_value' => $question['max_length'] ?? 500,
          '#min' => 1,
          '#max' => 10000,
          '#description' => new TranslatableMarkup('Número máximo de caracteres permitidos na resposta.'),
        ];

        $q['placeholder'] = [
          '#type' => 'textfield',
          '#title' => new TranslatableMarkup('Texto de placeholder'),
          '#default_value' => $question['placeholder'] ?? '',
          '#description' => new TranslatableMarkup('Texto de exemplo mostrado no campo de resposta.'),
        ];
      }

      // Explanation shown after answering.
      $q['explanation'] = [
        '#type' => 'textarea',
        '#title' => new TranslatableMarkup('Explicação'),
        '#default_value' => $question['explanation'] ?? '',
        '#rows' => 2,
        '#description' => new TranslatableMarkup('Explicação opcional mostrada ao aluno após responder (feedback).'),
      ];

      // Remove question button.
      $q['remove_question'] = [
        '#type' => 'submit',
        '#value' => new TranslatableMarkup('Remover Pergunta @num', ['@num' => $i + 1]),
        '#name' => 'remove_question_' . $delta . '_' . $i,
        '#submit' => [[static::class, 'removeQuestionSubmit']],
        '#ajax' => [
          'callback' => [static::class, 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['button--danger', 'button--small']],
      ];
    }

    // Add question button.
    $element['questions_wrapper']['add_question'] = [
      '#type' => 'submit',
      '#value' => new TranslatableMarkup('+ Adicionar Pergunta'),
      '#name' => 'add_question_' . $delta,
      '#submit' => [[static::class, 'addQuestionSubmit']],
      '#ajax' => [
        'callback' => [static::class, 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
      '#limit_validation_errors' => [],
    ];

    // Summary info.
    if ($num_questions > 0) {
      $element['questions_wrapper']['summary'] = [
        '#markup' => '<div class="messages messages--status" style="margin-top:10px;"><strong>' . $this->t('Total de perguntas: @count', ['@count' => $num_questions]) . '</strong></div>',
        '#weight' => -10,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => &$value) {
      $questions = [];

      if (!empty($value['questions_wrapper'])) {
        foreach ($value['questions_wrapper'] as $key => $question_data) {
          if (strpos($key, 'question_') !== 0) {
            continue;
          }

          $question = [
            'type' => $question_data['question_type'] ?? 'multiple_choice',
            'question' => trim($question_data['question_text'] ?? ''),
            'required' => !empty($question_data['required']),
            'points' => (int) ($question_data['points'] ?? 1),
            'explanation' => trim($question_data['explanation'] ?? ''),
          ];

          // Skip empty questions.
          if (empty($question['question'])) {
            continue;
          }

          if ($question['type'] === 'multiple_choice') {
            $options = [];
            $options_wrapper = $question_data['options_container']['options_wrapper'] ?? [];
            
            foreach ($options_wrapper as $opt_key => $opt_value) {
              if (strpos($opt_key, 'option_') === 0 && is_string($opt_value) && trim($opt_value) !== '') {
                $options[] = trim($opt_value);
              }
            }

            $question['options'] = $options;
            $question['allow_multiple'] = !empty($question_data['allow_multiple']);

            if ($question['allow_multiple']) {
              $correct = $options_wrapper['correct_answer'] ?? [];
              $question['correct_answers'] = is_array($correct) ? array_values(array_filter($correct, function($v) { return $v !== 0 && $v !== '0'; })) : [$correct];
            } else {
              $question['correct_answer'] = (int) ($options_wrapper['correct_answer'] ?? 0);
            }
          }
          else {
            // Text response.
            $question['validation_type'] = $question_data['validation_type'] ?? 'none';
            $question['expected_answer'] = trim($question_data['expected_answer'] ?? '');
            $question['case_sensitive'] = !empty($question_data['case_sensitive']);
            $question['max_length'] = (int) ($question_data['max_length'] ?? 500);
            $question['placeholder'] = trim($question_data['placeholder'] ?? '');
          }

          $questions[] = $question;
        }
      }

      $value = [
        'value' => !empty($questions) ? json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '',
      ];
    }

    return $values;
  }

  /**
   * AJAX callback to refresh the questions wrapper.
   */
  public static function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    // Navigate up to find the questions_wrapper.
    $parents = $trigger['#array_parents'];
    
    // Find the 'questions_wrapper' in the parents.
    $wrapper_parents = [];
    foreach ($parents as $parent) {
      $wrapper_parents[] = $parent;
      if ($parent === 'questions_wrapper') {
        break;
      }
    }

    $element = $form;
    foreach ($wrapper_parents as $parent) {
      $element = $element[$parent];
    }

    return $element;
  }

  /**
   * Submit handler for adding a question.
   */
  public static function addQuestionSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    // Extract delta from the button name.
    $name_parts = explode('_', $trigger['#name']);
    $delta = end($name_parts);

    $field_name = '';
    foreach ($trigger['#array_parents'] as $parent) {
      if (strpos($parent, 'field_') === 0) {
        $field_name = $parent;
        break;
      }
    }

    $key = $field_name . '_' . $delta . '_num_questions';
    $num = $form_state->get($key) ?? 0;
    $form_state->set($key, $num + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for removing a question.
   */
  public static function removeQuestionSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    // Extract delta and question index from the button name: remove_question_{delta}_{index}
    $name = $trigger['#name'];
    preg_match('/remove_question_(\d+)_(\d+)/', $name, $matches);
    $delta = $matches[1] ?? 0;
    $question_index = $matches[2] ?? 0;

    $field_name = '';
    foreach ($trigger['#array_parents'] as $parent) {
      if (strpos($parent, 'field_') === 0) {
        $field_name = $parent;
        break;
      }
    }

    $key = $field_name . '_' . $delta;
    $form_state->set($key . '_remove_index', (int) $question_index);
    
    $num_key = $key . '_num_questions';
    $num = $form_state->get($num_key) ?? 0;
    $form_state->set($num_key, max(0, $num - 1));
    
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for adding an option to a multiple choice question.
   */
  public static function addOptionSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    // Extract delta and question index: add_option_{delta}_{question_index}
    $name = $trigger['#name'];
    preg_match('/add_option_(\d+)_(\d+)/', $name, $matches);
    $delta = $matches[1] ?? 0;
    $question_index = $matches[2] ?? 0;

    $field_name = '';
    foreach ($trigger['#array_parents'] as $parent) {
      if (strpos($parent, 'field_') === 0) {
        $field_name = $parent;
        break;
      }
    }

    $key = $field_name . '_' . $delta . '_q' . $question_index . '_num_options';
    $num = $form_state->get($key) ?? 4;
    $form_state->set($key, $num + 1);
    $form_state->setRebuild(TRUE);
  }

}
