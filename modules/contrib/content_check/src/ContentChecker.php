<?php

namespace Drupal\content_check;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\content_check\Plugin\ContentCheckItem;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * The content checker service implementation.
 *
 * @package Drupal\content_check
 */
class ContentChecker {

  /**
   * The content check plugin manager.
   *
   * @var \Drupal\content_check\Plugin\ContentCheckPluginManager
   */
  protected $contentCheckPluginManager;

  /**
   * ContentChecker constructor.
   */
  public function __construct(PluginManagerInterface $content_check_plugin_manager) {
    $this->contentCheckPluginManager = $content_check_plugin_manager;
  }

  /**
   * Run the content checks against a specific entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to test.
   *
   * @return array
   *   Results of the tests.
   */
  public function checkEntity(ContentEntityInterface $entity) {
    $results = [];

    foreach ($this->contentCheckPluginManager->getDefinitions() as $id => $definition) {
      /** @var \Drupal\content_check\Plugin\ContentCheckInterface $instance */
      try {
        $instance = $this->contentCheckPluginManager->createInstance($id);
      }
      catch (PluginException $e) {
        continue;
      }

      if (!$instance->isApplicable($entity)) {
        continue;
      }

      // Run the test.
      $item = new ContentCheckItem($entity);
      $results[$id] = $instance->check($item);
    }

    return $results;
  }

}
