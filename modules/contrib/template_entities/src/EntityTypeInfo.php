<?php

namespace Drupal\template_entities;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 *
 * @internal
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Adds template routes to other content entity routes.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array $entity_types) {
    foreach ($entity_types as $entity_type_id => $entity_type) {
      // For all content entity types...
      if ($entity_type_id != 'template' && $entity_type instanceof ContentEntityType) {
        if ($entity_type->hasLinkTemplate('canonical')) {
          // Add a "templates" link template to the canonical path to access
          // a list of templates using the entity. E.g. /node/1/templates
          $templates_path = $entity_type->getLinkTemplate('canonical');
          $entity_type->setLinkTemplate('templates', $templates_path . '/templates');
        }
      }
    }
  }

}
