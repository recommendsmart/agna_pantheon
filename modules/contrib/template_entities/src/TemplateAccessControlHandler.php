<?php

namespace Drupal\template_entities;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Template entity.
 *
 * @see \Drupal\template_entities\Entity\Template.
 */
class TemplateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if (!$entity->getSourceEntity() && $operation === 'new_from_template') {
      return AccessResult::forbidden();
    }
    $access_result = parent::checkAccess($entity, $operation, $account);

    // Useful generic entity checks.
    if (!$access_result->isNeutral()) {
      return $access_result;
    }

    $type_id = $entity->bundle();

    $manage_permission = TemplatePermissions::manageTemplatesId($type_id);
    $new_permission = TemplatePermissions::newFromTemplateId($type_id);

    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, $manage_permission);
        }
        return AccessResult::allowedIfHasPermissions($account, [
          $manage_permission,
          $new_permission,
        ], 'OR');
      case 'new_from_template':
        return AccessResult::allowedIfHasPermissions($account, [
          $manage_permission,
          $new_permission,
        ], 'OR');
      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, $manage_permission);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, "manage $entity_bundle template");
  }

}
