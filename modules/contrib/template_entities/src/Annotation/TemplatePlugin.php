<?php

namespace Drupal\template_entities\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Template plugin item annotation object.
 *
 * @see \Drupal\template_entities\Plugin\TemplatePluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class TemplatePlugin extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The entity type that the plugin applies to.
   *
   * @var string
   */
  public $entity_type_id;
}
