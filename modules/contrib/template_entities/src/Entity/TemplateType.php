<?php

namespace Drupal\template_entities\Entity;

use Drupal;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines the Template type entity.
 *
 * @ConfigEntityType(
 *   id = "template_type",
 *   label = @Translation("Template type"),
 *   label_collection = @Translation("Template types"),
 *   label_singular = @Translation("template type"),
 *   label_plural = @Translation("template types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count template type",
 *     plural = "@count template types",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\template_entities\TemplateTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\template_entities\Form\TemplateTypeForm",
 *       "edit" = "Drupal\template_entities\Form\TemplateTypeForm",
 *       "delete" = "Drupal\template_entities\Form\TemplateTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "template_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "template",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/templates/template_types/add",
 *     "edit-form" =
 *   "/admin/structure/templates/template_types/{template_type}",
 *     "delete-form" =
 *   "/admin/structure/templates/template_types/{template_type}/delete",
 *     "collection" = "/admin/structure/templates/template_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "description",
 *     "collection_pages",
 *     "add_action_link",
 *     "listing_pages",
 *     "bundles",
 *     "settings",
 *   }
 * )
 */
class TemplateType extends ConfigEntityBundleBase implements TemplateTypeInterface, EntityWithPluginCollectionInterface {

  /**
   * The Template type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin instance settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The Template type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this template type.
   *
   * @var string
   */
  protected $description;

  /**
   * The entity type this template type applies to.
   *
   * @var string
   */
  protected $type;

  /**
   * The bundles this template type applies to.
   *
   * @var array
   */
  protected $bundles;

  /**
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $templatePluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    $plugin = $this->getTemplatePlugin();
    return $plugin ? $plugin->getEntityType()->id() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplatePlugin() {
    return !empty($this->type) ? $this->getPluginCollection()->get($this->type) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    return array_filter($this->bundles);
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionRoutes(RouteCollection $route_collection) {
    // Use collection_pages property if set, or fall back to route provided
    // by template plugin - e.g. node plugin will be system.admin_content.
    if ($collection_pages = $this->get('collection_pages')) {
      $routes = new RouteCollection();

      $paths = explode(PHP_EOL, $collection_pages);
      /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
      $route_provider = Drupal::service('router.route_provider');
      foreach ($paths as $path) {
        // Get routes for path pattern.
        // Routes matching /entity_test/list may include e.g. /entity_test/list
        // and /entity_test/{entity_test}. Best fit is ordered first so use
        // that.
        $path_routes = $route_provider->getRoutesByPattern($path);
        if ($path_routes->count() >= 1) {
          foreach ($path_routes as $route_name => $path_route) {
            $routes->add($route_name, $path_route);
            break;
          }
        }
      }
    }
    else {
      $routes = $this->getTemplatePlugin()
        ->getCollectionRoute($route_collection);
    }

    return $routes->all();
  }

  /**
   * {@inheritdoc}
   */
  public function getListingRoutes() {
    // Use collection_pages property if set, or fall back to route provided
    // by template plugin - e.g. node plugin will be system.admin_content.
    if ($collection_pages = $this->get('listing_pages')) {
      $routes = new RouteCollection();

      $paths = explode(PHP_EOL, $collection_pages);
      /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
      $route_provider = Drupal::service('router.route_provider');
      foreach ($paths as $path) {
        // Get routes for path pattern.
        // Routes matching /entity_test/list may include e.g. /entity_test/list
        // and /entity_test/{entity_test}. Best fit is ordered first so use
        // that.
        $path_routes = $route_provider->getRoutesByPattern($path);
        if ($path_routes->count() >= 1) {
          foreach ($path_routes as $route_name => $path_route) {
            $routes->add($route_name, $path_route);
            break;
          }
        }
      }
      if (!empty($routes)) {
        return $routes->all();
      }
    }

    return NULL;
  }

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Force router rebuild.
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'settings' => $this->getPluginCollection(),
    ];
  }

  /**
   * Encapsulates the creation of the template type's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The template type's plugin collection.
   */
  protected function getPluginCollection() {
    // Moved from a member variable to avoid database connection serialisation error in ajax callback.
    $templatePluginCollection = &drupal_static(__FUNCTION__);

    if (empty($this->type) && !$templatePluginCollection instanceof DefaultLazyPluginCollection) {
      // Return empty collection.
      return new DefaultLazyPluginCollection(Drupal::service('plugin.manager.template_plugin'));
    }
    elseif (!empty($this->type) && !isset($templatePluginCollection) || !$templatePluginCollection->has($this->type)) {
      // Create the collection if not set or changed type/instanceId.
      $templatePluginCollection = new DefaultSingleLazyPluginCollection(Drupal::service('plugin.manager.template_plugin'), $this->type, $this->settings);
    }

    return $templatePluginCollection;
  }
}
