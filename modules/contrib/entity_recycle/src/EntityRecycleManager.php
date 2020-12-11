<?php

namespace Drupal\entity_recycle;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Handles all items related to entity_recycle.
 */
class EntityRecycleManager implements EntityRecycleManagerInterface {
  use StringTranslationTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManagerInterface, LoggerChannelFactoryInterface $loggerChannelFactoryInterface) {
    $this->config = $config_factory->getEditable('entity_recycle.settings');
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManagerInterface;
    $this->logger = $loggerChannelFactoryInterface->get('entity_recycle');
  }

  /**
   * The field name used by the module.
   *
   * @var string
   */
  const RECYCLE_BIN_FIELD = 'recycle_bin';

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->config;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting) {
    return $this->config->get($setting);
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(EntityInterface $entity, $bundle = NULL) {
    $entityTypeId = $entity->getEntityTypeId();
    $enabledTypes = $this->getSetting('types');

    if (!isset($enabledTypes[$entityTypeId])) {
      return FALSE;
    }

    if ($bundle && isset($enabledTypes[$entityTypeId][$bundle])) {
      return TRUE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasBundles($entityTypeId) {
    $definition = $this->entityTypeManager->getDefinition($entityTypeId);

    if (!$definition->getBundleEntityType()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles($entityTypeId) {
    $definition = $this->entityTypeManager->getDefinition($entityTypeId);

    $bundles = $this->entityTypeManager
      ->getStorage($definition->getBundleEntityType())
      ->loadMultiple();

    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledBundles($entityTypeId) {
    if (!$this->hasBundles($entityTypeId)) {
      return;
    }

    $typeSettings = $this->getSetting('types');
    return isset($typeSettings[$entityTypeId]) ? $typeSettings[$entityTypeId] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function inRecycleBin(EntityInterface $entity, $bundle = NULL) {
    if (!$this->isEnabled($entity, $bundle)) {
      return FALSE;
    }

    $field = $entity->get(self::RECYCLE_BIN_FIELD);
    if (!$field) {
      return FALSE;
    }

    return $field->value ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItem($entityType, $id) {
    $entity = $this->entityTypeManager->getStorage($entityType)->load($id);

    if (!$entity) {
      return NULL;
    }

    if (!$this->inRecycleBin($entity, $entity->bundle())) {
      return NULL;
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllItems() {
    $types = $this->getSetting('types');
    $items = [];

    foreach ($types as $entityTypeId => $bundles) {
      if (!$bundles) {
        $items[$entityTypeId] = $this->entityTypeManager
          ->getStorage($entityTypeId)->loadByProperties([
            self::RECYCLE_BIN_FIELD => 1,
          ]);
        continue;
      }

      $items[$entityTypeId] = [];
      $definition = $this->entityTypeManager->getDefinition($entityTypeId);
      $bundleField = $definition->getKey('bundle');
      foreach ($bundles as $bundle) {
        $bundleItems = $this->entityTypeManager
          ->getStorage($entityTypeId)->loadByProperties([
            $bundleField => $bundle,
            self::RECYCLE_BIN_FIELD => 1,
          ]);

        foreach ($bundleItems as $entity) {
          $items[$entityTypeId][] = $entity;
        }
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function addItem(EntityInterface $entity) {
    // Don't do anything if it's already added.
    if ($this->inRecycleBin($entity, $entity->bundle())) {
      return $entity;
    }

    $field = $entity->get(self::RECYCLE_BIN_FIELD);
    if (!$field) {
      return FALSE;
    }

    // Move it.
    $field->value = 1;

    // Unpublish.
    $entity->setPublished(FALSE);

    $entity->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem(EntityInterface $entity) {
    // Don't do anything if the item is not in recycle bin.
    if (!$this->inRecycleBin($entity, $entity->bundle())) {
      return FALSE;
    }

    $field = $entity->get(self::RECYCLE_BIN_FIELD);
    if (!$field) {
      return FALSE;
    }

    // Remove it.
    $field->value = 0;

    $entity->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldExists($entityTypeId, $bundle = NULL) {
    $fields = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle);
    if (!in_array(self::RECYCLE_BIN_FIELD, array_keys($fields))) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createField($entityTypeId, $bundle = NULL) {
    $fieldStorage = $this->getFieldStorage($entityTypeId);

    // Create the storage if none is found.
    if (!$fieldStorage) {
      $fieldStorageData = $this->getFieldStorageData();
      $fieldStorageData['entity_type'] = $entityTypeId;

      $fieldStorage = $this->entityTypeManager
        ->getStorage('field_storage_config')
        ->create($fieldStorageData);

      $fieldStorage->save();
    }

    // Check if we already have a field.
    $fieldConfig = $this->entityTypeManager->getStorage('field_config')->load($entityTypeId . "." . $bundle . "." . self::RECYCLE_BIN_FIELD);
    if ($fieldConfig) {
      return $fieldConfig;
    }

    // Create field config.
    $fieldConfig = [
      'field_storage' => $fieldStorage,
      'label' => $this->t('Recycle Bin'),
      'settings' => [],
    ];

    // Bundable entity without specific bundle set.
    if ($this->hasBundles($entityTypeId) && !$bundle) {
      $bundles = $this->getBundles($entityTypeId);

      foreach ($bundles as $id => $bundle) {
        $fieldConfig['bundle'] = $id;

        $field = $this->entityTypeManager
          ->getStorage('field_config')
          ->create($fieldConfig);

        $field->save();
      }

      return $field;
    }

    // Bundlable entity with bundles set.
    if ($this->hasBundles($entityTypeId) && $bundle) {
      $fieldConfig['bundle'] = $bundle;

      $field = $this->entityTypeManager
        ->getStorage('field_config')
        ->create($fieldConfig);

      $field->save();

      return $field;
    }

    // Non bundlable entity.
    // @TODO: Check if this actually works.
    $field = $this->entityTypeManager
      ->getStorage('field_config')
      ->create($fieldConfig);

    $field->save();

    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorage($entityTypeId) {
    $fieldStorageDefinitions = $this->entityFieldManager
      ->getFieldStorageDefinitions($entityTypeId);

    if (isset($fieldStorageDefinitions[self::RECYCLE_BIN_FIELD])) {
      return $fieldStorageDefinitions[self::RECYCLE_BIN_FIELD];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageData() {
    return [
      'field_name' => self::RECYCLE_BIN_FIELD,
      'type' => 'boolean',
      'locked' => TRUE,
      'cardinality' => 1,
      'settings' => [],
      'indexes' => [],
      'persist_with_no_fields' => FALSE,
      'custom_storage' => FALSE,
      'status' => TRUE,
      'translatable' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getField($entityTypeId, $bundle = NULL) {
    if (!$bundle) {
      $bundle = 'default';
    }

    $field = $this->entityTypeManager
      ->getStorage('field_config')
      ->load($entityTypeId . "." . $bundle . "." . self::RECYCLE_BIN_FIELD);

    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteField($entityTypeId, $bundle = NULL) {
    // If no bundle is set
    // delete the field from all bundles.
    // and delete the storage as well.
    // @TODO: Refactor.
    if (!$bundle) {
      $fieldStorage = $this->getFieldStorage($entityTypeId);
      if ($fieldStorage) {
        $fieldStorage->delete();
      }

      if ($this->hasBundles($entityTypeId)) {
        $bundles = $this->getBundles($entityTypeId);

        foreach ($bundles as $bundle => $item) {
          $field = $this->getField($entityTypeId, $bundle);
          if ($field) {
            $field->delete();
          }
        }

        return;
      }

      $field = $this->getField($entityTypeId);
      if ($field) {
        $field->delete();
      }

      return;
    }

    $field = $this->getField($entityTypeId, $bundle);
    if ($field) {
      $field->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPurgeTime(EntityInterface $entity) {
    if (!$this->isEnabled($entity)) {
      return FALSE;
    }

    if (!$this->getSetting('purge_time')) {
      return FALSE;
    }

    $entityUpdateTime = $entity->getChangedTime();
    $entityUpdateDiff = (time() - $entityUpdateTime) / 60;

    $purge_time = $this->getSetting('purge_time');
    $minuteDiff = $purge_time - $entityUpdateDiff;

    return round($minuteDiff);
  }

  /**
   * {@inheritdoc}
   */
  public function purge(EntityInterface $entity) {
    if (!$this->isEnabled($entity)) {
      return FALSE;
    }

    if (!$this->getSetting('purge_time')) {
      return FALSE;
    }

    $purge_time = $this->getPurgeTime($entity);
    if ($purge_time < 0) {
      $entity->delete();

      $this->logger->notice($this->t(
        'Permanently removed @entity-type %label from recycle bin.',
        [
          '@entity-type' => $entity->bundle(),
          '%label' => $entity->label(),
        ],
      ));

      return TRUE;
    }

    return FALSE;
  }

}
