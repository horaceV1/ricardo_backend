<?php

namespace Drupal\jwt_auth_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for course progress/completion tracking.
 */
class CourseProgressController extends ControllerBase {

  /**
   * Get course progress for the authenticated user.
   *
   * GET /api/auth/course-progress
   * Optional query param: ?course_uuid=xxx to get progress for a specific course.
   */
  public function getProgress(Request $request) {
    $response = new JsonResponse();
    $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin') ?: '*');
    $response->headers->set('Access-Control-Allow-Credentials', 'true');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    $user = $this->currentUser();
    if ($user->isAnonymous()) {
      $response->setData(['error' => 'Authentication required']);
      $response->setStatusCode(401);
      return $response;
    }

    $uid = $user->id();
    $database = \Drupal::database();
    $course_uuid = $request->query->get('course_uuid');

    if ($course_uuid) {
      // Get progress for a specific course
      $record = $database->select('course_progress', 'cp')
        ->fields('cp')
        ->condition('uid', $uid)
        ->condition('course_uuid', $course_uuid)
        ->execute()
        ->fetchAssoc();

      if ($record) {
        $response->setData([
          'data' => $this->formatProgressRecord($record),
        ]);
      } else {
        $response->setData([
          'data' => NULL,
        ]);
      }
    } else {
      // Get all course progress for the user
      $records = $database->select('course_progress', 'cp')
        ->fields('cp')
        ->condition('uid', $uid)
        ->orderBy('changed', 'DESC')
        ->execute()
        ->fetchAll();

      $progress = [];
      foreach ($records as $record) {
        $progress[] = $this->formatProgressRecord((array) $record);
      }

      $response->setData([
        'data' => $progress,
      ]);
    }

    return $response;
  }

  /**
   * Update or create course progress for the authenticated user.
   *
   * POST /api/auth/course-progress
   * Body: { course_uuid, course_nid, current_module, total_modules, status }
   */
  public function updateProgress(Request $request) {
    $response = new JsonResponse();
    $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin') ?: '*');
    $response->headers->set('Access-Control-Allow-Credentials', 'true');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    $user = $this->currentUser();
    if ($user->isAnonymous()) {
      $response->setData(['error' => 'Authentication required']);
      $response->setStatusCode(401);
      return $response;
    }

    $uid = $user->id();
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content['course_uuid'])) {
      $response->setData(['error' => 'course_uuid is required']);
      $response->setStatusCode(400);
      return $response;
    }

    $course_uuid = $content['course_uuid'];
    $course_nid = $content['course_nid'] ?? 0;
    $current_module = $content['current_module'] ?? 0;
    $total_modules = $content['total_modules'] ?? 0;
    $status = $content['status'] ?? 'in_progress';

    // Validate status
    if (!in_array($status, ['in_progress', 'completed'])) {
      $response->setData(['error' => 'Invalid status. Must be "in_progress" or "completed"']);
      $response->setStatusCode(400);
      return $response;
    }

    $database = \Drupal::database();
    $now = \Drupal::time()->getRequestTime();

    // Check if a record already exists
    $existing = $database->select('course_progress', 'cp')
      ->fields('cp', ['id', 'status'])
      ->condition('uid', $uid)
      ->condition('course_uuid', $course_uuid)
      ->execute()
      ->fetchAssoc();

    if ($existing) {
      // Update existing record
      $update = [
        'current_module' => $current_module,
        'total_modules' => $total_modules,
        'status' => $status,
        'changed' => $now,
      ];

      // Set completed_at timestamp when marking as completed
      if ($status === 'completed' && $existing['status'] !== 'completed') {
        $update['completed_at'] = $now;
      }

      // If course_nid was provided, update it
      if ($course_nid) {
        $update['course_nid'] = $course_nid;
      }

      $database->update('course_progress')
        ->fields($update)
        ->condition('id', $existing['id'])
        ->execute();

      $record = $database->select('course_progress', 'cp')
        ->fields('cp')
        ->condition('id', $existing['id'])
        ->execute()
        ->fetchAssoc();

      $response->setData([
        'message' => $status === 'completed' ? 'Course marked as completed' : 'Progress updated',
        'data' => $this->formatProgressRecord($record),
      ]);
    } else {
      // Create new record
      $fields = [
        'uid' => $uid,
        'course_uuid' => $course_uuid,
        'course_nid' => $course_nid,
        'current_module' => $current_module,
        'total_modules' => $total_modules,
        'status' => $status,
        'created' => $now,
        'changed' => $now,
      ];

      if ($status === 'completed') {
        $fields['completed_at'] = $now;
      }

      $id = $database->insert('course_progress')
        ->fields($fields)
        ->execute();

      $record = $database->select('course_progress', 'cp')
        ->fields('cp')
        ->condition('id', $id)
        ->execute()
        ->fetchAssoc();

      $response->setData([
        'message' => $status === 'completed' ? 'Course marked as completed' : 'Progress saved',
        'data' => $this->formatProgressRecord($record),
      ]);
      $response->setStatusCode(201);
    }

    return $response;
  }

  /**
   * Format a progress record for API response.
   */
  private function formatProgressRecord(array $record): array {
    $progress_percent = 0;
    if (!empty($record['total_modules']) && $record['total_modules'] > 0) {
      if ($record['status'] === 'completed') {
        $progress_percent = 100;
      } else {
        $progress_percent = round(($record['current_module'] / $record['total_modules']) * 100);
      }
    }

    return [
      'course_uuid' => $record['course_uuid'],
      'course_nid' => (int) $record['course_nid'],
      'status' => $record['status'],
      'current_module' => (int) $record['current_module'],
      'total_modules' => (int) $record['total_modules'],
      'progress_percent' => $progress_percent,
      'completed_at' => $record['completed_at'] ? date('c', $record['completed_at']) : NULL,
      'created' => date('c', $record['created']),
      'changed' => date('c', $record['changed']),
    ];
  }

}
