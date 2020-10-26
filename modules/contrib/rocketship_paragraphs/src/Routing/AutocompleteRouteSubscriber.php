<?php

# Src: https://www.chapterthree.com/blog/how-alter-entity-autocomplete-results-drupal-8

namespace Drupal\rocketship_paragraphs\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class AutocompleteRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\rocketship_paragraphs\Controller\EntityAutocompleteController::handleAutocomplete');
    }
  }

}
