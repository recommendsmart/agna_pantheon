<?php

namespace Drupal\template_entities;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides a field definition class for bundle fields.
 *
 * Copied from the entity api module as this was the only dependency.
 *
 * @see \Drupal\entity\BundleFieldDefinition
 */
class BundleFieldDefinition extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
  }

}
