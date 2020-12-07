<?php

namespace Drupal\template_entities_layout_builder\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\template_entities_layout_builder\Controller\ChooseBlockTemplateController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for template entities layout routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Replace the default layout builder choose block controller.
    if ($choose_block_route = $collection->get("layout_builder.choose_block")) {
      $choose_block_route->setDefault('_controller', ChooseBlockTemplateController::class . ':build');
    }
  }

}
