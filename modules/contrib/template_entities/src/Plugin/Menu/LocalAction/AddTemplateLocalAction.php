<?php

namespace Drupal\template_entities\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Modifies the 'Add template' local action.
 */
class AddTemplateLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    // Adds a destination.
    $options['query']['destination'] = Url::fromRoute('<current>')->toString();
    return $options;
  }

}
