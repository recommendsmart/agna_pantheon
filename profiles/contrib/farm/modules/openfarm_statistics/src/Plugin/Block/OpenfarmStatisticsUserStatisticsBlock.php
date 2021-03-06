<?php

namespace Drupal\openfarm_statistics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\openfarm_holding\OpenfarmContextEntityTrait;

/**
 * Provides a 'OpenfarmStatisticsUserStatisticsBlock' block.
 *
 * @Block(
 *  id = "openfarm_statistics_user_statistics",
 *  admin_label = @Translation("User statistics block"),
 *   context = {
 *      "node" = @ContextDefinition(
 *       "entity:user",
 *       label = @Translation("Current user"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class OpenfarmStatisticsUserStatisticsBlock extends BlockBase {

  use OpenfarmContextEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $id = NULL;

    if ($user = $this->getEntity($this->getContexts())) {
      $id = $user->id();
    }
    else {
      return [];
    }

    $build['#theme'] = 'site_wide_statistics_block';
    $build['#main_class'] = 'record-statistics-block';
    $build['#show_title'] = FALSE;
    $build['#content'] = [
      'points' => [
        'bottom' => [
          '#markup' => (int) $user->field_points->value,
        ],
        'title' => $this->t('@user points', ['@user' => $user->getDisplayName()]),
        'img_class' => 'score_tag',
      ],
      'records' => [
        'bottom' => [
          '#lazy_builder' => ['openfarm_statistics.lazy_builder:getUserRecords', [$id]],
          '#create_placeholder' => TRUE,
        ],
        'title' => $this->t('@user records', ['@user' => $user->getDisplayName()]),
        'img_class' => 'public_stream_record',
      ],
      'votes' => [
        'bottom' => [
          '#lazy_builder' => ['openfarm_statistics.lazy_builder:getUserVotes', [$id]],
          '#create_placeholder' => TRUE,
        ],
        'title' => $this->t('@user votes', ['@user' => $user->getDisplayName()]),
        'img_class' => 'public_stream_like',
      ],
      'comments' => [
        'bottom' => [
          '#lazy_builder' => ['openfarm_statistics.lazy_builder:getUserComments', [$id]],
          '#create_placeholder' => TRUE,
        ],
        'title' => $this->t('@user comments', ['@user' => $user->getDisplayName()]),
        'img_class' => 'public_stream_comment',
      ],
    ];

    return $build;
  }

}
