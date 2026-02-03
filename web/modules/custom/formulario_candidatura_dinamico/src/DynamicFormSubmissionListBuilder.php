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
    $header['email'] = $this->t('Email');
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
    $row['email'] = $entity->getEmail();
    
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
