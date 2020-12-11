<?php

namespace Drupal\entity_recycle;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Handles all items related to viewing entity_recycle item.
 */
class EntityRecycleViewManager implements EntityRecycleViewManagerInterface {
  use StringTranslationTrait;

  /**
   * AccountProxy service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Recycle bin manager service.
   *
   * @var \Drupal\entity_recycle\EntityRecycleManagerInterface
   */
  protected $recycleBinManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxyInterface $account, EntityRecycleManagerInterface $recycleBinManager) {
    $this->currentUser = $account;
    $this->recycleBinManager = $recycleBinManager;
  }

  /**
   * The permission to enable viewing an item in recycle bin.
   *
   * @var string
   */
  const RECYCLE_BIN_VIEW_PERMISSION = 'view entity recycle bin items';

  /**
   * The permission to enable restoring an item in recycle bin.
   *
   * @var string
   */
  const RECYCLE_BIN_RESTORE_PERMISSION = 'restore entity recycle bin items';

  /**
   * The permission to enable deleting an item in recycle bin.
   *
   * @var string
   */
  const RECYCLE_BIN_DELETE_PERMISSION = 'delete entity recycle bin items';

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, AccountInterface $account = NULL) {
    $settings = $this->recycleBinManager->getSettings();
    if (!$settings) {
      return AccessResult::neutral();
    }

    $enabledTypes = $this->recycleBinManager->getSetting('types');
    if (!in_array($entity->getEntityTypeId(), array_keys($enabledTypes))) {
      return AccessResult::neutral();
    }

    $enabledBundles = $this->recycleBinManager->getEnabledBundles($entity->getEntityTypeId());
    if (is_array($enabledBundles)) {
      if (!in_array($entity->bundle(), $enabledBundles)) {
        return AccessResult::forbidden();
      }
    }

    if ($this->checkViewPermission($account) == FALSE) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function checkViewPermission(AccountInterface $account = NULL) {
    if (!$account) {
      return $this->currentUser->hasPermission(self::RECYCLE_BIN_VIEW_PERMISSION);
    }

    return $account->hasPermission(self::RECYCLE_BIN_VIEW_PERMISSION);
  }

  /**
   * {@inheritdoc}
   */
  public function checkRestorePermission(AccountInterface $account = NULL) {
    if (!$account) {
      return $this->currentUser->hasPermission(self::RECYCLE_BIN_RESTORE_PERMISSION);
    }

    return $account->hasPermission(self::RECYCLE_BIN_RESTORE_PERMISSION);
  }

  /**
   * {@inheritdoc}
   */
  public function checkDeletePermission(AccountInterface $account = NULL) {
    if (!$account) {
      return $this->currentUser->hasPermission(self::RECYCLE_BIN_DELETE_PERMISSION);
    }

    return $account->hasPermission(self::RECYCLE_BIN_DELETE_PERMISSION);
  }

}
