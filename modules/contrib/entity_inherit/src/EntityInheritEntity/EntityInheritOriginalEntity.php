<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_inherit\EntityInherit;

/**
 * An original entity.
 */
class EntityInheritOriginalEntity extends EntityInheritEntityRevision {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity.
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The global app.
   */
  public function __construct(EntityInterface $entity, EntityInherit $app) {
    $this->drupalEntity = $entity;
    parent::__construct($entity->getEntityTypeId(), $entity, $app);
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalEntity() : EntityInterface {
    return $this->drupalEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function originalValue(string $field_name) : array {
    return [];
  }

}
