<?php
namespace Drupal\formulario_candidatura_dinamico;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a class to build a listing of Dynamic Form Submission entities.
 */
class DynamicFormSubmissionListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['form_id'] = $this->t('Form');
    $header['email'] = $this->t('Email');
    $header['data'] = $this->t('Data');
    $header['approval_status'] = $this->t('Status');
    $header['created'] = $this->t('Data de submissão');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\formulario_candidatura_dinamico\Entity\DynamicFormSubmission $entity */
    $row['id'] = $entity->id();
    $row['form_id'] = $entity->getFormId();
    $row['email'] = $entity->getEmail();
    
    // Format the data column to show file information
    $data = $entity->getData();
    $data_output = [];
    
    if (!empty($data) && is_array($data)) {
      // Data might be wrapped in an array, extract it
      if (isset($data[0]) && is_array($data[0])) {
        $data = $data[0];
      }
      
      foreach ($data as $field_name => $field_data) {
        if (is_array($field_data) && isset($field_data['type']) && $field_data['type'] === 'file') {
          // File field
          $file_id = $field_data['value'] ?? null;
          if ($file_id && is_numeric($file_id)) {
            $file = \Drupal::entityTypeManager()->getStorage('file')->load($file_id);
            if ($file) {
              $filename = $field_data['filename'] ?? $file->getFilename();
              $url = $file->createFileUrl();
              $data_output[] = '<strong>' . $field_name . ':</strong> <a href="' . $url . '" target="_blank">' . $filename . '</a>';
            }
          }
        } elseif (!is_array($field_data)) {
          // Simple text field
          $data_output[] = '<strong>' . $field_name . ':</strong> ' . htmlspecialchars($field_data);
        }
      }
    }
    
    $row['data'] = [
      'data' => [
        '#markup' => !empty($data_output) ? implode('<br>', $data_output) : $this->t('No data'),
      ],
    ];
    
    // Add status badge
    $status = $entity->getApprovalStatus();
    $status_icon = $status === 'approved' ? '✅' : ($status === 'denied' ? '❌' : '⏳');
    $row['approval_status'] = [
      'data' => [
        '#markup' => '<span class="submission-status-badge status-' . $status . '">' . 
                     $status_icon . ' ' . ucfirst($status) . '</span>',
      ],
    ];
    
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');
    return $row + parent::buildRow($entity);
  }

}
