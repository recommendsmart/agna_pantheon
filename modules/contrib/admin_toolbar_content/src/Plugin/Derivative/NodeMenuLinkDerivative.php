<?php
namespace Drupal\admin_toolbar_content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NodeMenuLinkDerivative extends DeriverBase implements ContainerDeriverInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
    $entityManager = \Drupal::service('entity.manager');

    /** @var EntityAccessControlHandlerInterface $nodeAccessControlHandler */
    $nodeAccessControlHandler = $entityManager->getAccessControlHandler('node');

    $content_type_collections = \Drupal::service('module_handler')->invokeAll('content_type_collections');

    /** @var \Drupal\node\NodeTypeInterface[] $contentTypes */
    $contentTypes = $entityManager->getStorage('node_type')->loadMultiple();
    $default_content_types = [];
    foreach ($contentTypes as $contentType) {
      $default_content_types[$contentType->id()] = $contentType->label();
    }

    foreach ($content_type_collections as $collection => $content_type_collection) {
      $links[$collection] = [
          'title' => t((string)$content_type_collection['label']),
          'route_name' => 'system.admin_content',
          'route_parameters' => [
            'collection' => $collection,
          ],
          'menu_name' => 'admin',
          'parent' => 'system.admin_content',
          'id' => $base_plugin_definition['id'] . ':' . $collection
        ] + $base_plugin_definition;

      foreach ($content_type_collection['content_types'] as $content_type) {
        $links[$collection . '.' . $content_type] = [
            'title' => t((string)$default_content_types[$content_type]),
            'route_name' => 'system.admin_content',
            'route_parameters' => [
              'type' => $content_type,
              'collection' => $collection,
            ],
            'menu_name' => 'admin',
            'parent' => 'admin_toolbar_content.admin_content:' . $collection,
            'id' => $base_plugin_definition['id'] . ':' . $collection . '.' . $content_type
          ] + $base_plugin_definition;
        unset($default_content_types[$content_type]);
      }
    }

    $collection = 'content';
    foreach($default_content_types as $content_type => $label) {
      $links[$collection . '.' . $content_type] = [
          'title' => t((string) $label),
          'route_name' => 'system.admin_content',
          'route_parameters' => [
            'type' => $content_type,
            'collection' => $collection,
          ],
          'menu_name' => 'admin',
          'parent' => 'system.admin_content',
          'id' => $base_plugin_definition['id'] . ':' . $collection . '.' . $content_type
        ] + $base_plugin_definition;
    }

    return $links;
  }
}