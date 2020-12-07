<?php

namespace Drupal\Tests\template_entities\Functional\Entity;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\template_entities\Entity\Template;
use Drupal\template_entities\Entity\TemplateType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the visibility of template and non-template entities.
 *
 * @group Entity
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TemplateEntityVisibilityTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_test',
    'user',
    'template_entities',
    'block',
  ];

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @inheritdoc
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the delete form for translatable entities.
   */
  public function testEntityListing() {
    $assert = $this->assertSession();

    // Create 2 entities.
    $entity1 = EntityTest::create(['type' => 'default', 'name' => 'entity1']);
    $entity1->save();

    $entity2 = EntityTest::create(['type' => 'default', 'name' => 'entity2']);
    $entity2->save();

    $this->drupalGet(Url::fromRoute('entity.entity_test.collection'));

    // Item 51 should not be present.
    $assert->responseContains('entity1');
    $assert->responseContains('entity2');

    // Use entity1 as a template.
    $template = Template::create([
      'type' => 'default_template',
      'name' => 'template1',
      'template_entity_id' => $entity1->id(),
    ]);
    $template->save();

    $this->drupalGet(Url::fromRoute('entity.entity_test.collection'));

    $assert->responseNotContains('entity1');
    $assert->responseContains('entity2');

    // Check that add action button appears.
    $assert->responseContains('Add using Default template template');

    // Check what appears on templates list.
    // (routes defined in entity annotation).
    $this->drupalGet(Url::fromRoute('entity.template.collection'));

    $assert->statusCodeEquals(200);
    $assert->responseContains('template1');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');

    EntityTestBundle::create([
      'id' => 'default',
      'label' => 'Default',
    ])->save();

    $collection_url = Url::fromRoute('entity.entity_test.collection');

    // Set up a template type for use with entity_test entities.
    TemplateType::create([
      'id' => 'default_template',
      'label' => 'Default template',
      'type' => 'canonical_entities:entity_test',
      'bundles' => ['default' => 'default'],
      'collection_pages' => $collection_url->toString(),
      'add_action_link' => TRUE,
      'settings' => [],
    ])->save();

    $this->account = $this->drupalCreateUser([
      'administer entity_test content',
      'view test entity',
      'manage default_template template',
      'administer template entities',
      'new from default_template template',
    ]);
    $this->drupalLogin($this->account);
  }

}
