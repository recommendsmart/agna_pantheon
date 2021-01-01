<?php

namespace Drupal\openfarm_statistics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\openfarm_holding\OpenfarmContextEntityTrait;

/**
 * Provides a 'OpenfarmStatisticsRecordStatisticsBlock' block.
 *
 * @Block(
 *  id = "openfarm_statistics_record_statistics",
 *  admin_label = @Translation("Record statistics block"),
 *   context = {
 *      "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class OpenfarmStatisticsRecordStatisticsBlock extends BlockBase {

  use OpenfarmContextEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function build($holding = NULL) {
    $build = [];
    $contexts = $this->getContexts();
    $is_not_full = isset($contexts['view_mode']) && $contexts['view_mode']->getContextValue() != 'full';
    $id = NULL;

    if ($node = $this->getEntity($this->getContexts())) {
      $id = $node->id();
    }
    else {
      return [];
    }

    $build['#theme'] = 'site_wide_statistics_block';
    $build['#main_class'] = 'record-statistics-block';
    $build['#show_title'] = !$is_not_full;
    $build['#content'] = [
      'overall_score' => [
        'bottom' => [
          $holding ? '' : $node->field_overall_score->first()->view(['settings' => ['scale' => 0]]),
        ],
        'title' => $this->t('Overall score'),
        'img_class' => 'score_tag',
      ],
      'votes' => [
        'bottom' => [
          '#lazy_builder' => ['openfarm_statistics.lazy_builder:getVotes', [$id]],
          '#create_placeholder' => TRUE,
        ],
        'title' => $this->t('Votes'),
        'img_class' => $is_not_full ? 'public_stream_like' : 'like_tag',
      ],
      'comments' => [
        'bottom' => [
          '#lazy_builder' => ['openfarm_statistics.lazy_builder:getComments', [$id]],
          '#create_placeholder' => TRUE,
        ],
        'title' => $this->t('Comments'),
        'img_class' => $is_not_full ? 'public_stream_comment' : 'comment_tag',
      ],
      'views' => [
        'bottom' => [
          '#lazy_builder' => ['openfarm_statistics.lazy_builder:getViews', [$id]],
          '#create_placeholder' => TRUE,
        ],
        'title' => $this->t('Views'),
        'img_class' => $is_not_full ? 'public_stream_view' : 'view_tag',
      ],
    ];
    return $build;
  }

}
