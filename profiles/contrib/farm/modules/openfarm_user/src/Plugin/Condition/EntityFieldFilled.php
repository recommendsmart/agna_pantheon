<?php

namespace Drupal\openfarm_user\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a generic 'Entity field X was filled' condition.
 *
 * @Condition(
 *   id = "openfarm_record_field_filled",
 *   deriver = "Drupal\openfarm_user\Plugin\Condition\EntityFieldFilledDeriver"
 * )
 */
class EntityFieldFilled extends RulesConditionBase {

  /**
   * Check if the field X was filled in entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The record entity.
   *
   * @return bool
   *   TRUE if the field was filled.
   */
  protected function doEvaluate(EntityInterface $entity) {
    list(,, $field) = explode('.', $this->getDerivativeId());
    return !$entity->get($field)->isEmpty();
  }

}
