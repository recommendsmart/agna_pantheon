<?php

namespace Drupal\template_entities;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\template_entities\Entity\TemplateType;

/**
 * Provides dynamic permissions for templates of different types.
 */
class TemplatePermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of template type permissions.
   *
   * @return array
   *   The template type permissions.
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function templateTypePermissions() {
    $perms = [];
    // Generate permissions for all template types.
    foreach (TemplateType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of template permissions for a given template type.
   *
   * @param \Drupal\template_entities\Entity\TemplateType $type
   *   The template type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(TemplateType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      static::manageTemplatesId($type_id) => [
        'title' => $this->t('%type_name: Administer templates', $type_params),
      ],
      static::newFromTemplateId($type_id) => [
        'title' => $this->t('%type_name: Create new content from templates', $type_params),
      ],
    ];
  }

  /**
   * Helper to get a type specific "manage template" permission id.
   *
   * @param $template_type_id
   *
   * @return string
   */
  public static function manageTemplatesId($template_type_id) {
    return "manage $template_type_id template";
  }

  /**
   * Helper to get a type specific "new from template" permission id.
   *
   * @param $template_type_id
   *
   * @return string
   */
  public static function newFromTemplateId($template_type_id) {
    return "new from $template_type_id template";
  }

}
