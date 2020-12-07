<?php

namespace Drupal\template_entities\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Routing\Router;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\template_entities\Entity\TemplateType;
use Drupal\template_entities\Plugin\Menu\LocalAction\AddTemplateLocalAction;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Provides local task definitions for the template entities user interface.
 *
 * @internal
 */
class EntityTemplateActionLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  protected $routeProvider;

  /**
   * The router.
   *
   * @var \Drupal\Core\Routing\Router
   */
  protected $router;

  /**
   * Constructs a new LayoutBuilderLocalTaskDeriver.
   *
   * @param \Drupal\Core\Routing\RouteProvider $route_provider
   * @param \Drupal\Core\Routing\Router $router
   */
  public function __construct(RouteProvider $route_provider, Router $router) {
    $this->routeProvider = $route_provider;
    $this->router = $router;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('router.no_access_checks')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $template_types = TemplateType::loadMultiple();

    $route_collection = $this->router->getRouteCollection();

    /** @var TemplateType $template_type */
    foreach ($template_types as $template_type_id => $template_type) {
      // Add action links to collection pages.
      if ($collection_routes = $template_type->getCollectionRoutes($route_collection)) {
        if ($template_type->get('add_action_link')) {
          try {
            $this->derivatives["template.$template_type_id.new_from_template_page"] = $base_plugin_definition + [
                'route_name' => "template.$template_type_id.new_from_template_page",
                'title' => $this->t('Add using @label template', ['@label' => $template_type->label()]),
                'appears_on' => array_keys($collection_routes),
                'class' => AddTemplateLocalAction::class,
              ];
          } catch (RouteNotFoundException $e) {
            // Ignore.
          }
        }
      }

      if ($listing_routes = $template_type->getListingRoutes($route_collection)) {
        $this->derivatives["template.$template_type_id.add_form"] = $base_plugin_definition + [
            'route_name' => "entity.template.add_form",
            'route_parameters' => [
              'template_type' => $template_type_id,
            ],
            'title' => $this->t('Add @label template', ['@label' => $template_type->label()]),
            'appears_on' => array_keys($listing_routes),
            'class' => AddTemplateLocalAction::class,
          ];
      }
    }

    return $this->derivatives;
  }

}
