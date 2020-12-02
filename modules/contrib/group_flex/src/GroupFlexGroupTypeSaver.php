<?php

namespace Drupal\group_flex;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Saves a group flex settings form in the group type interface.
 */
class GroupFlexGroupTypeSaver {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupFlexSaver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Save the group flex settings.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The Form State.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(GroupTypeInterface $groupType, FormStateInterface $formState) {
    $mappedPerm = $this->getMappedPermGroupVisibility($formState->getValue('group_type_visibility'));
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * Get the mapped permissions for a given type visibility.
   *
   * @param int $group_type_visibility
   *   The Group Type Visibility.
   *
   * @return array|array[]|\string[][]
   *   The array of mapped permissions.
   */
  private function getMappedPermGroupVisibility($group_type_visibility) {
    switch ($group_type_visibility) {
      case GROUP_FLEX_TYPE_VIS_PUBLIC:
        return [
          'outsider' => [
            'view group' => TRUE,
          ],
          'member' => [
            'view group' => TRUE,
          ],
        ];

      case GROUP_FLEX_TYPE_VIS_PRIVATE:
        return [
          'outsider' => [
            'view group' => FALSE,
          ],
          'member' => [
            'view group' => TRUE,
          ],
        ];

      case GROUP_FLEX_TYPE_VIS_FLEX:
        return [
          'outsider' => [
            'view group' => FALSE,
          ],
          'member' => [
            'view group' => TRUE,
          ],
        ];

    }
    return [];
  }

  /**
   * Save the mapped permissions.
   *
   * @param array $mappedPerm
   *   The Mapped permissions.
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *   The Group Type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function saveMappedPerm(array $mappedPerm, GroupTypeInterface $groupType) {
    foreach ($mappedPerm as $role_name => $permissions) {
      $group_role_storage = $this->entityTypeManager->getStorage('group_role');
      $group_role_id = $groupType->id() . '-' . $role_name;
      /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
      $group_role = $group_role_storage->load($group_role_id);

      if ($group_role && !empty($permissions)) {
        foreach ($permissions as $perm => $value) {
          if ($value === TRUE) {
            $group_role->grantPermission($perm);
            continue;
          }
          $group_role->revokePermission($perm);
        }
        $group_role->save();
      }
    }
  }

}
