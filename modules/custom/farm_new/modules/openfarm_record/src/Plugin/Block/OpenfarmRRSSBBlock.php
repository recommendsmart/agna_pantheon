<?php

namespace Drupal\openfarm_record\Plugin\Block;

use Drupal\openfarm_holding\OpenfarmContextEntityTrait;
use Drupal\rrssb\Plugin\Block\RRSSBBlock;

/**
 * Provides a 'OpenfarmRRSSBBlock' block.
 *
 * @Block(
 *   id = "openfarm_rrssb_block",
 *   admin_label = @Translation("Openfarm RRSSB block with ability to set contextual entity"),
 *   category = @Translation("RRSSB"),
 *   context = {
 *      "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class OpenfarmRRSSBBlock extends RRSSBBlock {

  use OpenfarmContextEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($node = $this->getEntity($this->getContexts())) {
      $config = $this->getConfiguration();
      return rrssb_get_buttons($config['button_set'], $node, 'url.path');
    }
    return [];
  }

}
