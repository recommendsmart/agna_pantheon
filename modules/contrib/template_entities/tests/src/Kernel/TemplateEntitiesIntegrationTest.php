<?php

namespace Drupal\Tests\template_entities\Kernel;

use Drupal;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;

/**
 * Tests a complete deployment scenario.
 *
 * @group #slow
 * @group workspaces
 */
class TemplateEntitiesIntegrationTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;
  use UserCreationTrait;
  use ViewResultAssertionTrait;
  use TemplateEntitiesTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'node',
    'text',
    'user',
    'system',
    'path_alias',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creation timestamp that should be incremented for each new entity.
   *
   * @var int
   */
  protected $createdTimestamp;

  /**
   * An array of nodes created before installing the template entities module.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * Tests various scenarios for creating and marking nodes as templates.
   */
  public function testTemplateNodes() {
    // Initialise template entities and log in as template administrator.
    $this->initializeTemplateEntitiesModule();

    // Create a node (page) template type.
    $template_types[] = $this->createTemplateType([]);

    $this->assertNotEmpty($template_types, 'Template types not empty.');

    $template_type_id = $template_types[0]->id();

    // Create a template.
    $template = $this->createTemplate([
      'template_entity_id' => $this->nodes[0]->id(),
      'type' => $template_type_id,
    ]);

    $this->assertEqual($template->getSourceEntity()
      ->id(), $this->nodes[0]->id(), 'Source entity of template is expected.');

    $nid = $this->nodes[0]->id();

    $this->entityTypeManager->getStorage('node')->resetCache([$nid]);
    $expected_nodes = [$this->nodes[1]->id() => $this->nodes[1]];
    $this->assertEntityQuery($expected_nodes, 'node');

    $loaded_node = Node::load($nid);
    $this->assertEqual($this->nodes[0]->id(), $loaded_node->id(), 'Node load is not subject to entity query decoration.');

    /** @var \Drupal\Core\Entity\EntityListBuilder $node_list_builder */
    $node_list_builder = $this->entityTypeManager->getHandler('node', 'list_builder');
    $node_list = $node_list_builder->load();
    $this->assertEqual(array_keys($node_list), array_keys($expected_nodes), 'List builder nodes');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = Drupal::entityTypeManager();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $this->installConfig(['filter', 'node', 'system']);

    $this->installSchema('system', ['key_value_expire', 'sequences']);
    $this->installSchema('node', ['node_access']);

    $this->createContentType(['type' => 'page']);

    $this->setCurrentUser($this->createUser(['administer nodes']));

    // Create two nodes, a published and an unpublished one, so we can test the
    // behavior of the module with default/existing content.
    $this->createdTimestamp = Drupal::time()->getRequestTime();
    $this->nodes[] = $this->createNode([
      'title' => 'Node 1 - published',
      'body' => 'node 1',
      'created' => $this->createdTimestamp++,
      'status' => TRUE,
    ]);
    $this->nodes[] = $this->createNode([
      'title' => 'Node 2 - unpublished',
      'body' => 'node 2',
      'created' => $this->createdTimestamp++,
      'status' => FALSE,
    ]);
  }

}
