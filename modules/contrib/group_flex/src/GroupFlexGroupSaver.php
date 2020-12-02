<?php

namespace Drupal\group_flex;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\group_permissions\GroupPermissionsManager;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Saving of a Group to implement the correct group type permissions.
 */
class GroupFlexGroupSaver {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\group_permissions\GroupPermissionsManager definition.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManager
   */
  protected $groupPermissionGroupPermissionsManager;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new GroupFlexGroupSaver object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupPermissionsManager $group_permission_group_permissions_manager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupPermissionGroupPermissionsManager = $group_permission_group_permissions_manager;
    $this->messenger = $messenger;
  }

  /**
   * Save the group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to save.
   * @param int $group_visibility
   *   The desired visibility of the group.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveGroup(GroupInterface $group, int $group_visibility) {
    $group_type = $group->getGroupType();
    $group_permission = NULL;
    if (!$group->isNew()) {
      $group_permission = $this->groupPermissionGroupPermissionsManager->getGroupPermission($group);
    }
    if ($group_permission === NULL) {
      // Create the entity.
      $group_permission = GroupPermission::create([
        'gid' => $group->id(),
        'permissions' => [],
      ]);
    }
    $existing_permissions = $group_permission->getPermissions();

    // Save the permissions.
    $new_permissions = $existing_permissions;
    if ($group_visibility == GROUP_FLEX_TYPE_VIS_PUBLIC) {
      if (!array_key_exists($group_type->getOutsiderRoleId(), $existing_permissions) || !in_array('view group', $existing_permissions[$group_type->getOutsiderRoleId()])) {
        $new_permissions[$group_type->getOutsiderRoleId()][] = 'view group';
      }
    }
    if (!array_key_exists($group_type->getMemberRoleId(), $existing_permissions) || !in_array('view group', $existing_permissions[$group_type->getMemberRoleId()])) {
      $new_permissions[$group_type->getMemberRoleId()][] = 'view group';
    }
    $group_permission->setPermissions($new_permissions);

    $violations = $group_permission->validate();
    if (count($violations) == 0) {
      $group_permission->save();
    }
    else {
      foreach ($violations as $violation) {
        $this->messenger->addError((string) $violation->getMessage());
      }
    }
    // Save the group entity to reset the cache tags.
    $group->save();
  }

}
