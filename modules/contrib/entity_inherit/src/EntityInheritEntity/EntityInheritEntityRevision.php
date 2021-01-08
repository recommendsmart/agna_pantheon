<?php

namespace Drupal\entity_inherit\EntityInheritEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValueCollectionInterface;
use Drupal\entity_inherit\EntityInheritFieldValue\EntityInheritFieldValue;
use Drupal\entity_inherit\EntityInheritField\EntityInheritFieldListInterface;

/**
 * An entity or entity revision.
 */
abstract class EntityInheritEntityRevision implements EntityInheritEntityRevisionInterface, EntityInheritReadableEntityInterface {

  /**
   * The injected app singleton.
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * The Drupal entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $drupalEntity;

  /**
   * The Drupal entity type.
   *
   * @var string
   */
  protected $type;

  /**
   * Constructor.
   *
   * @param string $type
   *   The Drupal entity type such as "node".
   * @param null|\Drupal\Core\Entity\EntityInterface $entity
   *   The Drupal entity object, or NULL if we don't have it.
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The global app.
   */
  public function __construct(string $type, $entity, EntityInherit $app) {
    $this->type = $type;
    $this->app = $app;
    $this->drupalEntity = $entity;
  }

  /**
   * Get all inheritable field names.
   *
   * @return array
   *   All inheritable field names.
   */
  public function allFieldNames() : array {
    return $this->app->bundleFieldNames($this->getType(), $this->getBundle());
  }

  /**
   * Get the Drupal entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   This Drupal entity.
   */
  abstract public function getDrupalEntity() : EntityInterface;

  /**
   * {@inheritdoc}
   */
  public function getMergedParents() : EntityInheritExistingMultipleEntitiesInterface {
    $return = $this->app->getEntityFactory()->newCollection();

    $fields = $this->app->getParentEntityFields()->validOnly('parent');

    $return->add($this->referencedEntities($fields));

    return $return;
  }

  /**
   * Get this entity's bundle.
   */
  public function getBundle() : string {
    return $this->getDrupalEntity()->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getType() : string {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldValues() : EntityInheritFieldValueCollectionInterface {
    $return = $this->app->getFieldValueFactory()->newCollection();

    foreach ($this->allFieldNames() as $field_name) {
      $return->add(new EntityInheritFieldValue($this->app, $field_name, $this->value($field_name), $this->originalValue($field_name)));
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function hasField(string $field) : bool {
    return array_key_exists($field, $this->allFieldNames());
  }

  /**
   * {@inheritdoc}
   */
  public function inheritableFields() : array {
    return $this->app->inheritableFields($this->getType(), $this->getBundle());
  }

  /**
   * Get the original value of a field.
   *
   * @param string $field_name
   *   A field.
   *
   * @return array
   *   An original value.
   */
  abstract public function originalValue(string $field_name) : array;

  /**
   * {@inheritdoc}
   */
  public function referencedEntities(EntityInheritFieldListInterface $fields) : EntityInheritExistingMultipleEntitiesInterface {
    $return = $this->app->getEntityFactory()->newCollection();

    foreach ($fields->toArray() as $field) {
      $drupal_entities = $this->getDrupalEntity()->{$field->__toString()}->referencedEntities();
      foreach ($drupal_entities as $drupal_entity) {
        $return->add(new EntityInheritExistingEntity($drupal_entity->getEntityTypeId(), $drupal_entity->id(), $drupal_entity, $this->app));
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function value(string $field_name) : array {
    return $this->getDrupalEntity()->{$field_name}->getValue();
  }

}
