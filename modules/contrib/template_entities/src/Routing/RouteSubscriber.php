<?php

namespace Drupal\template_entities\Routing;

use Drupal;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\template_entities\Controller\TemplateController;
use Drupal\template_entities\TemplateManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for template entities routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The template manager service.
   *
   * @var \Drupal\template_entities\TemplateManagerInterface
   */
  protected $templateManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\template_entities\TemplateManagerInterface $template_manager
   *   The template manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, TemplateManagerInterface $template_manager) {
    $this->entityTypeManager = $entity_manager;
    $this->templateManager = $template_manager;
  }

  /**
   * Callback from template_entities.routing.yml.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function routes() {
    $collection = new RouteCollection();

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Add routes per content page to display information on associated template entities.
      // E.g. a task tab on a node page to list any template entities that use the node as
      // template content.
      if ($entity_type_id !== 'template' && $route = $this->getTemplatesRoute($entity_type, $entity_type->getLinkTemplate('templates'))) {
        $collection->add("entity.$entity_type_id.templates", $route);
      }
    }

    return $collection;
  }

  /**
   * Gets the new_from_template route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @param string $path
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getTemplatesRoute(EntityTypeInterface $entity_type, string $path) {
    if ($path) {
      /** @var \Drupal\template_entities\TemplateManager $template_manager */
      $template_manager = Drupal::service('template_entities.manager');

      $entity_type_id = $entity_type->id();

      if ($template_manager->isEntityTypeTemplateable($entity_type_id)) {
        $route = new Route($path);
        $route
          ->addDefaults([
            '_controller' => TemplateController::class . '::templates',
            '_title' => 'Templates',
            'entity_type_id' => $entity_type_id,
          ])
          ->addRequirements([
            '_permission' => 'administer template entities',
            '_has_linked_templates' => 'true',
          ])
          ->setOption('_admin_route', TRUE)
          ->setOption('parameters', [
            $entity_type_id => ['type' => 'entity:' . $entity_type_id],
          ]);
        $route->setRequirement($entity_type_id, '\d+');
        return $route;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // alter routes.
    // For all out template types.
    // Look at the entity routes
    // And add our own based on those.
    $template_types = $this->entityTypeManager->getStorage('template_type')
      ->loadMultiple();
    /** @var \Drupal\template_entities\Entity\TemplateTypeInterface $template_type */
    foreach ($template_types as $template_type_id => $template_type) {
      $entity_type_id = $template_type->getTargetEntityTypeId();

      if ($add_route = $collection->get("entity.$entity_type_id.add")
        ?: $collection->get("$entity_type_id.add")
          ?: $collection->get("entity.$entity_type_id.add_form")
            ?: $collection->get("$entity_type_id.add_form")) {
        // Add an new from template route.
        $route = new Route($add_route->getPath() . '/template/{template}');
        $parameters = $add_route->getOption('parameters');
        $parameters['template'] = ['type' => 'entity:template'];
        $route->setOption('parameters', $parameters);
        $route
          ->addDefaults([
            '_controller' => TemplateController::class . '::newFromTemplate',
            '_title' => 'New from template ' . $template_type->label(),
            'entity_type_id' => $entity_type_id,
          ])
          ->addRequirements([
            '_new_from_template' => 'true',
          ])
          ->setOption('_admin_route', TRUE);

        $collection->add("template.$template_type_id.new_from_template", $route);
      }

      foreach ($template_type->getCollectionRoutes($collection) as $collection_route_id => $collection_route) {
        $new_page_route = new Route($collection_route->getPath() . '/template/' . $template_type_id . '/new');
        if ($parameters = $collection_route->getOption('parameters')) {
          $new_page_route->setOption('parameters', $parameters);
        }
        $new_page_route
          ->addDefaults([
            '_controller' => TemplateController::class . '::newFromTemplatePage',
            '_title' => 'Choose template',
            'entity_type_id' => $entity_type_id,
            'template_type' => $template_type_id,
          ])
          ->addRequirements([
            '_new_from_template' => 'true',
          ])
          ->setOption('_admin_route', TRUE);

        $collection->add("template.$template_type_id.new_from_template_page", $new_page_route);
      }
    }

    $new_page_route = new Route('/template/{template_type}/new');

    $new_page_route->setDefault('_controller', TemplateController::class . '::newFromTemplatePage');
    $new_page_route->setDefault('_title', 'Choose template');

    $new_page_route->setOption('parameters', [
      'template_type' => [],
    ]);
    $new_page_route->setOption('_admin_route', TRUE);
    $new_page_route->setRequirement('_new_from_template', "true");
    $collection->add("entity.template.new_from_template_page", $new_page_route);
  }

  /**
   * Gets the new_from_template route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @param string $path
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getNewFromTemplateRoute(EntityTypeInterface $entity_type, string $path) {
    if ($path) {
      /** @var \Drupal\template_entities\TemplateManager $template_manager */
      $template_manager = Drupal::service('template_entities.manager');

      if ($template_manager->isEntityTypeTemplateable($entity_type->id())) {

        $route = new Route($path);
        $route
          ->addDefaults([
            '_controller' => TemplateController::class . '::newFromTemplate',
            '_title' => 'New from template ' . $entity_type->getLabel(),
            'entity_type_id' => $entity_type->id(),
          ])
          ->addRequirements([
            '_new_from_template' => 'true',
          ])
          ->setOption('_admin_route', TRUE)
          ->setOption('parameters', [
            'template' => ['type' => 'entity:template'],
          ]);
        return $route;
      }
    }

    return NULL;
  }

}
