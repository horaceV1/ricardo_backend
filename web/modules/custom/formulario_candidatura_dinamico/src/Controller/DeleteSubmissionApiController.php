<?php

namespace Drupal\formulario_candidatura_dinamico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission;

/**
 * API Controller for deleting submissions.
 */
class DeleteSubmissionApiController extends ControllerBase {

  /**
   * Delete a single submission.
   */
  public function deleteSubmission($submission_id) {
    $response_headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'DELETE, OPTIONS',
      'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
    ];

    try {
      $current_user = \Drupal::currentUser();
      
      if ($current_user->isAnonymous()) {
        return new JsonResponse([
          'success' => false,
          'message' => 'You must be logged in to delete submissions.',
        ], 403, $response_headers);
      }

      // Load the submission
      $submission = DynamicFormSubmission::load($submission_id);
      
      if (!$submission) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Submission not found.',
        ], 404, $response_headers);
      }

      // Check if user owns this submission
      $submission_email = $submission->getEmail();
      $user_email = $current_user->getEmail();
      
      if ($submission_email !== $user_email && !$current_user->hasPermission('administer users')) {
        return new JsonResponse([
          'success' => false,
          'message' => 'You do not have permission to delete this submission.',
        ], 403, $response_headers);
      }

      // Delete associated files
      $data = $submission->getData();
      if (is_array($data)) {
        foreach ($data as $field_data) {
          if (is_array($field_data) && isset($field_data['type']) && $field_data['type'] === 'file' && isset($field_data['value'])) {
            $file = \Drupal\file\Entity\File::load($field_data['value']);
            if ($file) {
              $file->delete();
            }
          }
        }
      }

      // Delete the submission entity
      $submission->delete();

      // Also remove from user profile if exists
      $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
      $profiles = $profile_storage->loadByProperties([
        'uid' => $current_user->id(),
        'type' => 'user_submissions',
      ]);

      if (!empty($profiles)) {
        $profile = reset($profiles);
        if ($profile->hasField('field_submissions')) {
          $field_value = $profile->get('field_submissions')->value;
          if ($field_value) {
            $submissions = json_decode($field_value, TRUE);
            if (is_array($submissions)) {
              // Remove submission with matching entity_id or submission_id
              $submissions = array_filter($submissions, function($sub) use ($submission_id) {
                return !isset($sub['entity_id']) || $sub['entity_id'] != $submission_id;
              });
              
              $submissions = array_values($submissions);
              $profile->set('field_submissions', json_encode($submissions));
              $profile->save();
            }
          }
        }
      }

      \Drupal::logger('formulario_candidatura_dinamico')->info('User @uid deleted submission @id', [
        '@uid' => $current_user->id(),
        '@id' => $submission_id,
      ]);

      return new JsonResponse([
        'success' => true,
        'message' => 'Submission deleted successfully.',
      ], 200, $response_headers);

    } catch (\Exception $e) {
      \Drupal::logger('formulario_candidatura_dinamico')->error('Error deleting submission: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => false,
        'message' => 'An error occurred while deleting the submission.',
        'error' => $e->getMessage(),
      ], 500, $response_headers);
    }
  }

  /**
   * Delete all submissions for the current user.
   */
  public function clearAllSubmissions() {
    $response_headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'DELETE, OPTIONS',
      'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
    ];

    try {
      $current_user = \Drupal::currentUser();
      
      if ($current_user->isAnonymous()) {
        return new JsonResponse([
          'success' => false,
          'message' => 'You must be logged in to clear submissions.',
        ], 403, $response_headers);
      }

      $user_email = $current_user->getEmail();
      
      // Get all submissions for this user
      $storage = \Drupal::entityTypeManager()->getStorage('dynamic_form_submission');
      $query = $storage->getQuery()
        ->condition('email', $user_email)
        ->accessCheck(FALSE);
      
      $ids = $query->execute();
      
      if (empty($ids)) {
        return new JsonResponse([
          'success' => true,
          'message' => 'No submissions to delete.',
          'count' => 0,
        ], 200, $response_headers);
      }

      $submissions = $storage->loadMultiple($ids);
      $count = 0;

      foreach ($submissions as $submission) {
        // Delete associated files
        $data = $submission->getData();
        if (is_array($data)) {
          foreach ($data as $field_data) {
            if (is_array($field_data) && isset($field_data['type']) && $field_data['type'] === 'file' && isset($field_data['value'])) {
              $file = \Drupal\file\Entity\File::load($field_data['value']);
              if ($file) {
                $file->delete();
              }
            }
          }
        }

        $submission->delete();
        $count++;
      }

      // Clear user profile submissions
      $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
      $profiles = $profile_storage->loadByProperties([
        'uid' => $current_user->id(),
        'type' => 'user_submissions',
      ]);

      if (!empty($profiles)) {
        $profile = reset($profiles);
        if ($profile->hasField('field_submissions')) {
          $profile->set('field_submissions', json_encode([]));
          $profile->save();
        }
      }

      \Drupal::logger('formulario_candidatura_dinamico')->info('User @uid cleared all @count submissions', [
        '@uid' => $current_user->id(),
        '@count' => $count,
      ]);

      return new JsonResponse([
        'success' => true,
        'message' => "Successfully deleted $count submission(s).",
        'count' => $count,
      ], 200, $response_headers);

    } catch (\Exception $e) {
      \Drupal::logger('formulario_candidatura_dinamico')->error('Error clearing submissions: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => false,
        'message' => 'An error occurred while clearing submissions.',
        'error' => $e->getMessage(),
      ], 500, $response_headers);
    }
  }

}
