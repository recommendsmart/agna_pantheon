<?php

namespace Drupal\commerce_ticketing;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the commerce ticket entity type.
 */
class CommerceTicketAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view commerce ticket');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ['edit commerce ticket', 'administer commerce ticket'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ['delete commerce ticket', 'administer commerce ticket'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ['create commerce ticket', 'administer commerce ticket'], 'OR');
  }

}
