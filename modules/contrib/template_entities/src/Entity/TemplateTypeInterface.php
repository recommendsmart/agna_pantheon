<?php

namespace Drupal\template_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides an interface for defining Template type entities.
 */
interface TemplateTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * @return \Drupal\template_entities\Plugin\TemplatePluginInterface|NULL
   */
  public function getTemplatePlugin();

  /**
   * Gets the target entity type id for this template type.
   *
   * @return string|NULL
   *   The target entity type id or NULL if not yet set.
   */
  public function getTargetEntityTypeId();

  /**
   * Get routes for entity collection pages associated with this template type.
   *
   * @param \Symfony\Component\Routing\RouteCollection $route_collection
   *
   * @return array
   *   An array of routes.
   */
  public function getCollectionRoutes(RouteCollection $route_collection);

  /**
   * Get the plugin id that this template type uses.
   *
   * @return string|NULL
   *   The plugin id.
   */
  public function getType();

  /**
   * Get the bundles that this template type applies to.
   *
   * @return array
   *   Array of bundle ids.
   */
  public function getBundles();

  /**
   * Get routes for additional template listing pages associated with this template type.
   *
   * @return array
   *   An array of routes.
   */
  public function getListingRoutes();

}
