<?php

namespace Drupal\lms_subscription\Plugin\Commerce\LicenseType;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_license\Plugin\Commerce\LicenseType\LicenseTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a license type that grants group membership.
 *
 * @CommerceLicenseType(
 *   id = "group_membership",
 *   label = @Translation("Group membership"),
 * )
 */
class GroupMembership extends LicenseTypeBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'all_courses' => TRUE,
      'group_ids' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['all_courses'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Grant access to all courses'),
      '#description' => $this->t('If checked, this license will automatically grant access to all LMS courses, including courses created in the future.'),
      '#default_value' => $this->configuration['all_courses'],
    ];

    // Get all LMS course groups.
    $groups = \Drupal::entityTypeManager()
      ->getStorage('group')
      ->loadByProperties(['type' => 'lms_course']);

    $group_options = [];
    foreach ($groups as $group) {
      $group_options[$group->id()] = $group->label();
    }

    $form['group_ids'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Specific courses'),
      '#description' => $this->t('Select specific courses. Only used if "Grant access to all courses" is unchecked.'),
      '#options' => $group_options,
      '#default_value' => $this->configuration['group_ids'],
      '#states' => [
        'visible' => [
          ':input[name="configuration[group_membership][all_courses]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['all_courses'] = (bool) $values['all_courses'];
      // Filter out unchecked values (checkboxes return 0 for unchecked).
      $this->configuration['group_ids'] = array_filter($values['group_ids']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function grantLicense(LicenseInterface $license) {
    $user = $license->getOwner();
    $group_storage = \Drupal::entityTypeManager()->getStorage('group');

    // Determine which groups to grant access to.
    if (!empty($this->configuration['all_courses'])) {
      // Grant access to all LMS courses.
      $groups = $group_storage->loadByProperties(['type' => 'lms_course']);
    }
    else {
      // Grant access to specific courses only.
      $group_ids = $this->configuration['group_ids'];
      if (empty($group_ids)) {
        return;
      }
      $groups = $group_storage->loadMultiple($group_ids);
    }

    foreach ($groups as $group) {
      // Check if user is already a member.
      if (!$group->getMember($user)) {
        // Add user to group.
        $group->addMember($user);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function revokeLicense(LicenseInterface $license) {
    $user = $license->getOwner();
    $group_storage = \Drupal::entityTypeManager()->getStorage('group');

    // Determine which groups to revoke access from.
    if (!empty($this->configuration['all_courses'])) {
      // Revoke access from all LMS courses.
      $groups = $group_storage->loadByProperties(['type' => 'lms_course']);
    }
    else {
      // Revoke access from specific courses only.
      $group_ids = $this->configuration['group_ids'];
      if (empty($group_ids)) {
        return;
      }
      $groups = $group_storage->loadMultiple($group_ids);
    }

    foreach ($groups as $group) {
      // Check if user is a member and remove.
      if ($member = $group->getMember($user)) {
        $group->removeMember($user);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkLicenseHasGrantedRights(LicenseInterface $license) {
    $group_ids = $this->configuration['group_ids'];
    if (empty($group_ids)) {
      return FALSE;
    }

    $user = $license->getOwner();
    $group_storage = \Drupal::entityTypeManager()->getStorage('group');

    // Check if user is member of all configured groups.
    foreach ($group_ids as $group_id) {
      $group = $group_storage->load($group_id);
      if (!$group || !$group->getMember($user)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLabel(LicenseInterface $license) {
    if (!empty($this->configuration['all_courses'])) {
      return $this->t('Access to all courses');
    }

    $group_ids = $this->configuration['group_ids'];
    if (empty($group_ids)) {
      return $this->t('Group membership');
    }

    $group_storage = \Drupal::entityTypeManager()->getStorage('group');
    $groups = $group_storage->loadMultiple($group_ids);

    if (empty($groups)) {
      return $this->t('Group membership');
    }

    $course_names = [];
    foreach ($groups as $group) {
      $course_names[] = $group->label();
    }

    return $this->t('Access to @courses', ['@courses' => implode(', ', $course_names)]);
  }

}
