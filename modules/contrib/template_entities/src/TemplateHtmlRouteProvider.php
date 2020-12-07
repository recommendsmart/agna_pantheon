<?php

namespace Drupal\template_entities;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\template_entities\Controller\TemplateController;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Template entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class TemplateHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    // Override add form title callback.
    $collection->get('entity.template.add_form')->setDefault('_title_callback', TemplateController::class . '::addBundleTitle');

    // This will always be 'template'.
    $entity_type_id = $entity_type->id();

    // Provide a route on template entities to create content from the template.
    // The route calls the redirect method on the TemplateController to
    // open the new content form in the context of the target entity type.
    // E.g. a node template will redirect from template/template/1/new to
    // node/template/1.
    if ($entity_type_id === 'template') {
      if ($new_from_template_route = $this->getNewFromTemplateRoute($entity_type)) {
        $collection->add("entity.{$entity_type_id}.new_from_template", $new_from_template_route);
      }
    }

    return $collection;
  }

  /**
   * Gets the new from template route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getNewFromTemplateRoute(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $route = new Route($entity_type->getLinkTemplate('new-from-template'));
    $route->setDefault('_controller', TemplateController::class . '::newFromTemplateRedirect');
    $route->setDefault('_title_callback', TemplateController::class . '::newFromTemplateTitle');
    $route->setDefault('entity_type_id', $entity_type->id());
    $route->setOption('parameters', [
      $entity_type->id() => ['type' => 'entity:' . $entity_type->id()],
    ]);
    $route->setOption('_admin_route', TRUE);

    $route->setRequirement('_entity_access', "{$entity_type_id}.new_from_template");

    return $route;
  }

  /**
   * Gets the generic new from template route.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getNewFromTemplatePageRoute() {
    $route = new Route('/template/{template_type}/new');
    $route->setDefault('_controller', TemplateController::class . '::newFromTemplatePage');
    $route->setDefault('_title', 'Choose template');
    $route->setOption('parameters', [
      'template_type' => [],
    ]);
    $route->setOption('_admin_route', TRUE);
    $route->setRequirement('_new_from_template', "true");
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCollectionRoute($entity_type)) {
      $route->setOption('_admin_route', TRUE);
    }

    return $route;
  }

}
