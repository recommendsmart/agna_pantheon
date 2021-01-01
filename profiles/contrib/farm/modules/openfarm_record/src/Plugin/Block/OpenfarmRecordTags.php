<?php

namespace Drupal\openfarm_record\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\openfarm_holding\OpenfarmContextEntityTrait;

/**
 * Provides a 'Node tags' block.
 *
 * @Block(
 *  id = "openfarm_record_tags_block",
 *  admin_label = @Translation("Node tags"),
 *   context = {
 *      "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class OpenfarmRecordTags extends BlockBase {

  use OpenfarmContextEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if ($node = $this->getEntity($this->getContexts())) {
      $field = $node->bundle() == 'record' ? 'field_record_tags' : 'field_tags';
      // In case when field is empty do not need to render anything at all.
      $build['#cache']['tags'] = $node->getCacheTags();
      if ($node->{$field}->isEmpty()) {
        return $build;
      }

      $build = [
        '#theme' => 'item_list',
        '#title' => $this->t('Tags'),
        '#attributes' => ['class' => ['record-tags']],
      ];
      $items = [];
      // @Todo: Unify field names.
      foreach ($node->{$field} as $tag) {
        $items[] = $tag->entity->label();
      }
      $build['#items'] = $items;
    }

    return $build;
  }

}
