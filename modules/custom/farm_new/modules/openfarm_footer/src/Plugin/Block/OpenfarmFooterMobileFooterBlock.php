<?php

namespace Drupal\openfarm_footer\Plugin\Block;

use Drupal\openfarm_holding\OpenfarmContextEntityTrait;
use Drupal\openfarm_record\Plugin\Block\OpenfarmRecordFlagAndLikeBlock;

/**
 * Provides a 'MobileFooterBlock' block.
 *
 * @Block(
 *  id = "openfarm_footer_mobile_footer_block",
 *  admin_label = @Translation("Mobile footer block"),
 *   context = {
 *      "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class OpenfarmFooterMobileFooterBlock extends OpenfarmRecordFlagAndLikeBlock {

  use OpenfarmContextEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($node = $this->getEntity($this->getContexts())) {
      // We should only display share section for article and anonymous user,
      // because article has not comments, follow, and likes at all
      // and anonymous user has no access to it.
      if ($node->bundle() != 'article' || $this->currentUser->isAnonymous()) {
        $build = parent::build();
        $build['#comment'] = TRUE;
      }
      $build['#cols'] = empty($build) ? 'col-24' : 'col-6';
      $build['#main_class'] = 'site-footer-mobile-block';
      $build['#share'] = TRUE;
    }

    $build['#theme'] = 'openfarm_footer_mobile_footer_block';
    return $build;
  }

}
