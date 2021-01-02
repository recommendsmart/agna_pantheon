<?php

namespace Drupal\openfarm_holding\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\openfarm_holding\OpenfarmContextEntityTrait;

/**
 * Class HoldingRecordsTitleWithCount.
 *
 * @Block(
 *   id = "openfarm_holding_holding_records_title",
 *   admin_label = @Translation("Holding records title with records count"),
 *   category = @Translation("Openfarm"),
 *   context = {
 *      "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class HoldingRecordsTitleWithCount extends BlockBase {

  use OpenfarmContextEntityTrait;

  /**
   * {@inheritDoc}
   */
  public function build() {
    $build = [];
    if ($node = $this->getEntity($this->getContexts())) {
      $build['content'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['holdings-records-title']],
        'title' => [
          '#markup' => $this->t('Holding records'),
        ],
        // The lazy builder element do not supports the prefix and suffix,
        // so add them like this.
        'prefix' => [
          '#markup' => ' (',
        ],
        'count' => [
          '#lazy_builder' => ['openfarm_statistics.lazy_builder:getHoldingRecords', [$node->id()]],
          '#create_placeholder' => TRUE,
        ],
        'suffix' => [
          '#markup' => ')',
        ],
      ];
      $build['#cache']['tags'] = ['node_list:record'];
    }

    return $build;
  }

}
