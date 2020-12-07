<?php

namespace Drupal\template_entities;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Template entities.
 *
 * @ingroup template_entities
 */
class TemplateListBuilder extends EntityListBuilder {

  /**
   * The template type entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $templateTypeStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityStorageInterface $template_type_storage
   *   The template type storage class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager class.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityStorageInterface $template_type_storage, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $storage);

    $this->templateTypeStorage = $template_type_storage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager')->getStorage('template_type'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Template type');
    $header['source_entity'] = $this->t('Source');
    $header['source_entity_type'] = $this->t('Source type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\template_entities\Entity\Template */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.template.canonical',
      ['template' => $entity->id()]
    );

    $template_type = $this->templateTypeStorage->load($entity->bundle());


    $row['template_type'] = $template_type ? $template_type->label() : '';
    $row['source_entity'] = $entity->getSourceEntity() ? $entity->getSourceEntity()
      ->toLink() : '';
    if ($template_type) {
      $source_type = $this->entityTypeManager->getDefinition($template_type->getTargetEntityTypeId());
      $row['source_entity_type'] = [
        'data' => [
          '#plain_text' => $source_type->getLabel(),
        ],
      ];
    }
    else {
      $row['source_entity_type'] = '';
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if ($entity->access('new_from_template') && $entity->hasLinkTemplate('new-from-template')) {
      $operations['new_from_template'] = [
        'title' => $this->t('New from template'),
        'url' => $this->ensureDestination($entity->toUrl('new-from-template')),
        'parameter' => $entity,
      ];
    }

    return $operations;
  }

}
