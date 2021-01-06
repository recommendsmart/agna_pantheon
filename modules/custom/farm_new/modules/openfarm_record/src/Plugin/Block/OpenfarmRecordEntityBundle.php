<?php

namespace Drupal\openfarm_record\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\openfarm_holding\OpenfarmContextEntityTrait;

/**
 * Provides a OpenfarmRecordEntityBundle class.
 *
 * @Block(
 *   id = "openfarm_record_node_bundle",
 *   admin_label = @Translation("Entity bundle"),
 *   category = @Translation("Openfarm"),
 *   context = {
 *      "entity" = @ContextDefinition(
 *       "entity",
 *       label = @Translation("Current Node"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class OpenfarmRecordEntityBundle extends BlockBase {

  use OpenfarmContextEntityTrait;

  /**
   * {@inheritDoc}
   */
  public function build() {
    $build = [];

    // If displayed in layout builder node isn't presented.
    if ($entity = $this->getEntity($this->getContexts(), 'entity')) {
      $build['content_type'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['entity_bundle_label', 'entity_bundle_label--' . $entity->bundle()]],
        '#value' => $entity->bundle() == 'article' ? $this->t('News') : $entity->bundle(),
      ];
    }

    return $build;
  }

}
