<?php

namespace Drupal\course_quiz\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for course quiz operations.
 */
class QuizApiController extends ControllerBase {

  /**
   * Get quiz questions for a course module (by UUID).
   *
   * GET /api/course/{course_uuid}/quiz
   *
   * Returns questions WITHOUT correct answers (for the student to take).
   */
  public function getQuiz(string $course_uuid, Request $request) {
    $response = new JsonResponse();
    $this->setCorsHeaders($response, $request);

    // Load node by UUID.
    $nodes = $this->entityTypeManager()->getStorage('node')->loadByProperties([
      'uuid' => $course_uuid,
      'type' => 'cursos',
    ]);

    if (empty($nodes)) {
      $response->setData(['error' => 'Course not found']);
      $response->setStatusCode(404);
      return $response;
    }

    $node = reset($nodes);

    // Check if quiz is enabled.
    if (!$node->hasField('field_quiz_enabled') || empty($node->get('field_quiz_enabled')->value)) {
      $response->setData(['data' => null, 'message' => 'No quiz configured for this module']);
      return $response;
    }

    // Get quiz questions.
    $questions_json = $node->hasField('field_quiz_questions') ? $node->get('field_quiz_questions')->value : '';
    if (empty($questions_json)) {
      $response->setData(['data' => null, 'message' => 'No questions configured']);
      return $response;
    }

    $questions = json_decode($questions_json, TRUE);
    if (!is_array($questions) || empty($questions)) {
      $response->setData(['data' => null, 'message' => 'Invalid questions data']);
      return $response;
    }

    // Strip correct answers from the response (student shouldn't see them).
    $student_questions = [];
    foreach ($questions as $i => $question) {
      $q = [
        'id' => $i,
        'type' => $question['type'],
        'question' => $question['question'],
        'required' => $question['required'] ?? TRUE,
        'points' => $question['points'] ?? 1,
      ];

      if ($question['type'] === 'multiple_choice') {
        $q['options'] = $question['options'] ?? [];
        $q['allow_multiple'] = $question['allow_multiple'] ?? FALSE;
      }
      else {
        // Text response - provide metadata but not the answer.
        $q['max_length'] = $question['max_length'] ?? 500;
        $q['placeholder'] = $question['placeholder'] ?? '';
        $q['validation_type'] = $question['validation_type'] ?? 'none';
        // Don't send expected_answer for 'exact_match' or 'contains'.
        // Only send min_length info if that's the validation type.
        if ($question['validation_type'] === 'min_length') {
          $q['min_length'] = (int) ($question['expected_answer'] ?? 0);
        }
      }

      $student_questions[] = $q;
    }

    $passing_score = $node->hasField('field_quiz_passing_score') 
      ? (int) $node->get('field_quiz_passing_score')->value 
      : 70;

    $response->setData([
      'data' => [
        'course_uuid' => $course_uuid,
        'course_title' => $node->getTitle(),
        'quiz_enabled' => TRUE,
        'passing_score' => $passing_score,
        'total_questions' => count($student_questions),
        'total_points' => array_sum(array_column($student_questions, 'points')),
        'questions' => $student_questions,
      ],
    ]);

    return $response;
  }

  /**
   * Submit quiz answers and get grading results.
   *
   * POST /api/course/{course_uuid}/quiz/submit
   * Body: { answers: [{ question_id: 0, answer: "..." }, ...] }
   */
  public function submitQuiz(string $course_uuid, Request $request) {
    $response = new JsonResponse();
    $this->setCorsHeaders($response, $request);

    $user = $this->currentUser();
    if ($user->isAnonymous()) {
      $response->setData(['error' => 'Authentication required']);
      $response->setStatusCode(401);
      return $response;
    }

    // Load node by UUID.
    $nodes = $this->entityTypeManager()->getStorage('node')->loadByProperties([
      'uuid' => $course_uuid,
      'type' => 'cursos',
    ]);

    if (empty($nodes)) {
      $response->setData(['error' => 'Course not found']);
      $response->setStatusCode(404);
      return $response;
    }

    $node = reset($nodes);

    // Get questions with correct answers.
    $questions_json = $node->hasField('field_quiz_questions') ? $node->get('field_quiz_questions')->value : '';
    if (empty($questions_json)) {
      $response->setData(['error' => 'No quiz configured']);
      $response->setStatusCode(400);
      return $response;
    }

    $questions = json_decode($questions_json, TRUE);
    if (!is_array($questions)) {
      $response->setData(['error' => 'Invalid quiz data']);
      $response->setStatusCode(500);
      return $response;
    }

    $content = json_decode($request->getContent(), TRUE);
    $submitted_answers = $content['answers'] ?? [];

    // Grade each question.
    $results = [];
    $total_points = 0;
    $earned_points = 0;

    foreach ($questions as $i => $question) {
      $points = $question['points'] ?? 1;
      $total_points += $points;

      // Find the submitted answer for this question.
      $submitted = NULL;
      foreach ($submitted_answers as $answer) {
        if (($answer['question_id'] ?? -1) == $i) {
          $submitted = $answer['answer'] ?? '';
          break;
        }
      }

      $is_correct = FALSE;
      $feedback = '';

      if ($question['type'] === 'multiple_choice') {
        if (!empty($question['allow_multiple'])) {
          // Multiple correct answers.
          $correct_answers = $question['correct_answers'] ?? [];
          $submitted_array = is_array($submitted) ? $submitted : [$submitted];
          sort($correct_answers);
          sort($submitted_array);
          $is_correct = $correct_answers == $submitted_array;
        }
        else {
          // Single correct answer.
          $correct = $question['correct_answer'] ?? 0;
          $is_correct = ((string) $submitted === (string) $correct);
        }
      }
      else {
        // Text response grading.
        $validation_type = $question['validation_type'] ?? 'none';
        $expected = $question['expected_answer'] ?? '';
        $case_sensitive = $question['case_sensitive'] ?? FALSE;

        switch ($validation_type) {
          case 'none':
            // Free text - always correct if not empty.
            $is_correct = !empty(trim((string) $submitted));
            break;

          case 'exact_match':
            if ($case_sensitive) {
              $is_correct = trim((string) $submitted) === trim($expected);
            }
            else {
              $is_correct = mb_strtolower(trim((string) $submitted)) === mb_strtolower(trim($expected));
            }
            break;

          case 'contains':
            $keywords = array_map('trim', explode(',', $expected));
            $answer_text = $case_sensitive ? (string) $submitted : mb_strtolower((string) $submitted);
            $is_correct = TRUE;
            foreach ($keywords as $keyword) {
              $search = $case_sensitive ? $keyword : mb_strtolower($keyword);
              if (empty($search)) continue;
              if (mb_strpos($answer_text, $search) === FALSE) {
                $is_correct = FALSE;
                break;
              }
            }
            break;

          case 'min_length':
            $min = (int) $expected;
            $is_correct = mb_strlen(trim((string) $submitted)) >= $min;
            break;

          case 'regex':
            $is_correct = (bool) preg_match('/' . $expected . '/u', (string) $submitted);
            break;
        }
      }

      if ($is_correct) {
        $earned_points += $points;
      }

      $result = [
        'question_id' => $i,
        'is_correct' => $is_correct,
        'points_earned' => $is_correct ? $points : 0,
        'points_possible' => $points,
      ];

      // Include explanation if available.
      if (!empty($question['explanation'])) {
        $result['explanation'] = $question['explanation'];
      }

      // For multiple choice, include the correct answer.
      if ($question['type'] === 'multiple_choice') {
        if (!empty($question['allow_multiple'])) {
          $result['correct_answers'] = $question['correct_answers'] ?? [];
        }
        else {
          $result['correct_answer'] = $question['correct_answer'] ?? 0;
        }
      }

      $results[] = $result;
    }

    $score_percent = $total_points > 0 ? round(($earned_points / $total_points) * 100) : 0;
    $passing_score = $node->hasField('field_quiz_passing_score')
      ? (int) $node->get('field_quiz_passing_score')->value
      : 70;
    $passed = $score_percent >= $passing_score;

    // Store the result in the database.
    $database = \Drupal::database();
    $now = \Drupal::time()->getRequestTime();

    // Check for existing attempt.
    $existing = $database->select('quiz_results', 'qr')
      ->fields('qr', ['id', 'attempts'])
      ->condition('uid', $user->id())
      ->condition('course_uuid', $course_uuid)
      ->execute()
      ->fetchAssoc();

    if ($existing) {
      $database->update('quiz_results')
        ->fields([
          'score' => $score_percent,
          'points_earned' => $earned_points,
          'points_total' => $total_points,
          'passed' => $passed ? 1 : 0,
          'answers' => json_encode($submitted_answers),
          'attempts' => $existing['attempts'] + 1,
          'changed' => $now,
        ])
        ->condition('id', $existing['id'])
        ->execute();
    }
    else {
      $database->insert('quiz_results')
        ->fields([
          'uid' => $user->id(),
          'course_uuid' => $course_uuid,
          'course_nid' => $node->id(),
          'score' => $score_percent,
          'points_earned' => $earned_points,
          'points_total' => $total_points,
          'passed' => $passed ? 1 : 0,
          'answers' => json_encode($submitted_answers),
          'attempts' => 1,
          'created' => $now,
          'changed' => $now,
        ])
        ->execute();
    }

    $response->setData([
      'data' => [
        'score' => $score_percent,
        'passing_score' => $passing_score,
        'passed' => $passed,
        'points_earned' => $earned_points,
        'points_total' => $total_points,
        'results' => $results,
      ],
    ]);

    return $response;
  }

  /**
   * Get quiz results for the authenticated user.
   *
   * GET /api/auth/quiz-results?course_uuid=xxx (optional filter)
   */
  public function getResults(Request $request) {
    $response = new JsonResponse();
    $this->setCorsHeaders($response, $request);

    $user = $this->currentUser();
    if ($user->isAnonymous()) {
      $response->setData(['error' => 'Authentication required']);
      $response->setStatusCode(401);
      return $response;
    }

    $database = \Drupal::database();
    $course_uuid = $request->query->get('course_uuid');

    $query = $database->select('quiz_results', 'qr')
      ->fields('qr')
      ->condition('uid', $user->id())
      ->orderBy('changed', 'DESC');

    if ($course_uuid) {
      $query->condition('course_uuid', $course_uuid);
    }

    $records = $query->execute()->fetchAll();

    $results = [];
    foreach ($records as $record) {
      $results[] = [
        'course_uuid' => $record->course_uuid,
        'course_nid' => (int) $record->course_nid,
        'score' => (int) $record->score,
        'points_earned' => (int) $record->points_earned,
        'points_total' => (int) $record->points_total,
        'passed' => (bool) $record->passed,
        'attempts' => (int) $record->attempts,
        'created' => date('c', $record->created),
        'changed' => date('c', $record->changed),
      ];
    }

    $response->setData(['data' => $results]);
    return $response;
  }

  /**
   * Set CORS headers on the response.
   */
  private function setCorsHeaders(JsonResponse $response, Request $request) {
    $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin') ?: '*');
    $response->headers->set('Access-Control-Allow-Credentials', 'true');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  }

}
