<?php

namespace Drupal\entity_recycle\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\entity_recycle\EntityRecycleManagerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for entity delete routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Entity recycle manager service.
   *
   * @var \Drupal\entity_recycle\EntityRecycleManagerInterface
   */
  protected $entityRecycleManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\entity_recycle\EntityRecycleManagerInterface $entityRecycleManagerInterface
   *   The entity recycle manager service.
   */
  public function __construct(EntityRecycleManagerInterface $entityRecycleManagerInterface) {
    $this->entityRecycleManager = $entityRecycleManagerInterface;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $settings = $this->entityRecycleManager->getSettings();
    if (!$settings) {
      return;
    }

    $entityTypes = $this->entityRecycleManager->getSetting('types');
    if (!$entityTypes) {
      return;
    }

    foreach ($entityTypes as $entityType => $bundles) {
      $route = $collection->get("entity." . $entityType . ".delete_form");

      if (!empty($route)) {
        $defaults = $route->getDefaults();
        unset($defaults['_entity_form']);
        $defaults['_form'] = '\Drupal\entity_recycle\Form\EntityRecycleDeleteForm';
        $route->setDefaults($defaults);

        $options = $route->getOptions();
        $options['entity_type'] = $entityType;
        // @TODO: Maybe merge with existing values ?
        $options['parameters'] = [
          $entityType => [
            'type' => 'entity:' . $entityType,
          ],
        ];
        $route->setOptions($options);
        $route->setDefaults($defaults);

        $route->setRequirements(['_permission' => 'add entity recycle bin items']);
      }
    }
  }

}
