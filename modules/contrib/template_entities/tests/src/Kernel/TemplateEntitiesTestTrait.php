<?php

namespace Drupal\Tests\template_entities\Kernel;

use Drupal;
use Drupal\Tests\template_entities\Traits\TemplateCreationTrait;
use Drupal\Tests\template_entities\Traits\TemplateTypeCreationTrait;

/**
 * A trait with common workspaces testing functionality.
 */
trait TemplateEntitiesTestTrait {

  use TemplateTypeCreationTrait;
  use TemplateCreationTrait;

  /**
   * The workspaces manager.
   *
   * @var \Drupal\template_entities\TemplateManagerInterface
   */
  protected $templateManager;

  /**
   * Enables the Workspaces module and creates two workspaces.
   */
  protected function initializeTemplateEntitiesModule() {
    // Enable the Workspaces module here instead of the static::$modules array
    // so we can test it with default content.
    $this->enableModules(['template_entities']);
    $this->container = Drupal::getContainer();
    $this->entityTypeManager = Drupal::entityTypeManager();
    $this->templateManager = Drupal::service('template_entities.manager');

    $this->installEntitySchema('template_type');
    $this->installEntitySchema('template');

    $permissions = array_intersect([
      'administer template entities',
    ], array_keys($this->container->get('user.permissions')->getPermissions()));
    $this->setCurrentUser($this->createUser($permissions));
  }


  /**
   * Asserts that entity queries are giving the correct results for entities
   * linked to templates.
   *
   * @param array $expected_values
   *   An array of expected values, as defined in ::testWorkspaces().
   * @param string $entity_type_id
   *   The ID of the entity type to check.
   */
  protected function assertEntityQuery(array $expected_values, $entity_type_id) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    // Check entity query counts.
    $result = $storage->getQuery()->count()->execute();
    $this->assertEquals(count($expected_values), $result);

    $result = $storage->getAggregateQuery()->count()->execute();
    $this->assertEquals(count($expected_values), $result);

    // Check entity queries with no conditions.
    $result = $storage->getQuery()->execute();
    $expected_result = array_map(function ($v) {
      return $v->id();
    }, $expected_values);
    $this->assertEqual($expected_result, $result);
  }

}
