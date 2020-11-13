<?php

namespace Drupal\notification_system_dispatch\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines notification_system_dispatcher annotation object.
 *
 * @Annotation
 */
class NotificationSystemDispatcher extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
