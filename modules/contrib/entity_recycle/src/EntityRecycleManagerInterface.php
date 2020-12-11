<?php

namespace Drupal\entity_recycle;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for entity_recycle manager class.
 */
interface EntityRecycleManagerInterface {

  /**
   * Get all settings.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  public function getSettings();

  /**
   * Get one specific setting.
   *
   * @param string $setting
   *   Name of the setting.
   *
   * @return array
   *   A configuration array.
   */
  public function getSetting($setting);

  /**
   * Determines if the entity is enabled.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param string $bundle
   *   Bundle id.
   *
   * @return bool
   *   Returns TRUE on success.
   */
  public function isEnabled(EntityInterface $entity, $bundle = NULL);

  /**
   * Determines if entity has bundles.
   *
   * @param string $entityTypeId
   *   Entity type id.
   *
   * @return bool
   *   Returns TRUE on success.
   */
  public function hasBundles($entityTypeId);

  /**
   * Get all available entity bundles.
   *
   * @param string $entityTypeId
   *   Entity type id.
   *
   * @return array
   *   Returns array of bundle ids.
   */
  public function getBundles($entityTypeId);

  /**
   * Get all enabled entity bundles for recycle bin.
   *
   * @param string $entityTypeId
   *   Entity type id.
   *
   * @return array
   *   Returns array of bundle ids.
   */
  public function getEnabledBundles($entityTypeId);

  /**
   * Determines if entity is in recycle bin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param string $bundle
   *   Bundle id.
   *
   * @return bool
   *   Returns TRUE on success.
   */
  public function inRecycleBin(EntityInterface $entity, $bundle = NULL);

  /**
   * Get an item from recycle bin.
   *
   * @param string $entityType
   *   Entity type id.
   * @param int $id
   *   Entity id.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A entity object.
   */
  public function getItem($entityType, $id);

  /**
   * Get all items in recycle bin.
   *
   * @return array
   *   Returns array with entity type ids as keys.
   */
  public function getAllItems();

  /**
   * Adds an item to recycle bin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A entity object.
   */
  public function addItem(EntityInterface $entity);

  /**
   * Removes an item from recycle bin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A entity object.
   */
  public function removeItem(EntityInterface $entity);

  /**
   * Determines if recycle bin field exists on entity.
   *
   * @param string $entityTypeId
   *   Entity type id.
   * @param string $bundle
   *   Bundle id.
   *
   * @return bool
   *   Return TRUE on success.
   */
  public function fieldExists($entityTypeId, $bundle = NULL);

  /**
   * Creates a recycle bin field.
   *
   * @param string $entityTypeId
   *   Entity type id.
   * @param string $bundle
   *   Bundle id.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity object.
   */
  public function createField($entityTypeId, $bundle = NULL);

  /**
   * Get recycle bin field storage.
   *
   * @param string $entityTypeId
   *   Entity type id.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   The array of field storage definitions for the entity type, keyed by
   *   field name.
   */
  public function getFieldStorage($entityTypeId);

  /**
   * Get default data for recycle bin field storage.
   *
   * @return array
   *   Returns array with field storage data.
   */
  public function getFieldStorageData();

  /**
   * Deletes a recycle bin field.
   *
   * @param string $entityTypeId
   *   Entity type id.
   * @param string $bundle
   *   Bundle id.
   */
  public function deleteField($entityTypeId, $bundle = NULL);

  /**
   * Get entity purging time.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return int
   *   Returns time in minutes.
   */
  public function getPurgeTime(EntityInterface $entity);

  /**
   * Permanently remove an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return bool
   *   True on success.
   */
  public function purge(EntityInterface $entity);

}
