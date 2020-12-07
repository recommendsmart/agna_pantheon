<?php

namespace Drupal\template_entities_block_starter\Plugin\Menu\LocalAction;

use Drupal\block_content\Plugin\Menu\LocalAction\BlockContentAddLocalAction as OriginalBlockContentAddLocalAction;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Modifies the 'Add custom block' local action.
 */
class BlockContentAddLocalAction extends OriginalBlockContentAddLocalAction {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    // Adds a destination on custom block listing (templates version).
    if ($route_match->getRouteName() == 'view.content_block_library.page_1') {
      $options['query']['destination'] = Url::fromRoute('<current>')->toString();
    }
    return $options;
  }

}
