<?php

namespace Drupal\entity_recycle;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for entity_recycle view manager class.
 */
interface EntityRecycleViewManagerInterface {

  /**
   * Checks access to the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns TRUE/FALSE.
   */
  public function entityAccess(EntityInterface $entity, AccountInterface $account = NULL);

  /**
   * Checks if user has view recycle bin permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   *
   * @return bool
   *   Returns TRUE/FALSE.
   */
  public function checkViewPermission(AccountInterface $account = NULL);

  /**
   * Checks if user has restore recycle bin permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   *
   * @return bool
   *   Returns TRUE/FALSE.
   */
  public function checkRestorePermission(AccountInterface $account = NULL);

  /**
   * Checks if user has delete recycle bin permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   *
   * @return bool
   *   Returns TRUE/FALSE.
   */
  public function checkDeletePermission(AccountInterface $account = NULL);

}
