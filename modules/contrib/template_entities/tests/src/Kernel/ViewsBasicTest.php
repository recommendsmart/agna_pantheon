<?php

namespace Drupal\Tests\template_entities\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * A basic query test for Views.
 *
 * @group views
 */
class ViewsBasicTest extends ViewsKernelTestBase {

  use TemplateEntitiesTestTrait;
  use UserCreationTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['entity_test_row'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'template_entities',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $entity_test_info = \Drupal::entityTypeManager()->getDefinition('entity_test');

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');


    $this->setCurrentUser($this->createUser());


    // Create two nodes, a published and an unpublished one, so we can test the
    // behavior of the module with default/existing content.
//    $this->createdTimestamp = Drupal::time()->getRequestTime();
//    $this->nodes[] = $this->createNode([
//      'title' => 'Node 1 - published',
//      'body' => 'node 1',
//      'created' => $this->createdTimestamp++,
//      'status' => TRUE,
//    ]);
//    $this->nodes[] = $this->createNode([
//      'title' => 'Node 2 - unpublished',
//      'body' => 'node 2',
//      'created' => $this->createdTimestamp++,
//      'status' => FALSE,
//    ]);
  }

  /**
   * Tests a trivial result set.
   */
  public function testSimpleResultSet() {
    /** @var \Drupal\entity_test\Entity\EntityTest $test_entity1 */
    $test_entity1 = EntityTest::create();
    $test_entity1->save();

    /** @var \Drupal\entity_test\Entity\EntityTest $test_entity2 */
    $test_entity2 = EntityTest::create();
    $test_entity2->save();

    $this->initializeTemplateEntitiesModule();

    $templates = $this->createTemplateTypeAndTemplate('canonical_entities:entity_test', ['entity_test'], [$test_entity1]);

    $view = Views::getView('entity_test_row');
    $view->setDisplay();

    // Execute the view.
    $this->executeView($view);

    // Verify the result.
    $this->assertCount(1, $view->result, 'The number of returned rows match.');
  }

  protected function createTemplateTypeAndTemplate($plugin_id, $bundles, $source_entities) {
    // Create a term template type.
    $template_type = $this->createTemplateType([
      'type' => $plugin_id,
      'label' => $plugin_id . ' template type',
      'description' => $plugin_id . ' template type.',
      'bundles' => $bundles,
    ]);

    $templates = [];

    foreach ($source_entities as $source_entity) {
      // Create a template.
      $templates[] = $this->createTemplate([
        'template_entity_id' => $source_entity->id(),
        'type' => $template_type->id(),
      ]);
    }

    return $templates;
  }

}
