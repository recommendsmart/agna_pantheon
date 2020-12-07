<?php

namespace Drupal\Tests\template_entities\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\template_entities\Traits\TemplateCreationTrait;
use Drupal\Tests\template_entities\Traits\TemplateTypeCreationTrait;

/**
 * Tests uninstalling the template entities module.
 *
 * @group template_entities
 */
class TemplateEntitiesUninstallTest extends BrowserTestBase {

  use TemplateTypeCreationTrait;
  use TemplateCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['template_entities'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests deleting template entities and uninstalling Template entities module.
   */
  public function testUninstallingEmptyTemplates() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/modules/uninstall');
    $session = $this->assertSession();
    $session->pageTextContains('Template entities');
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[template_entities]' => TRUE], 'Uninstall');
    $this->drupalPostForm(NULL, [], 'Uninstall');
    $session->pageTextContains('The selected modules have been uninstalled.');
    $session->pageTextNotContains('Template entities');
  }

  /**
   * Tests deleting template entities and uninstalling Template Entities module.
   */
  public function testUninstallingTemplates() {
    $this->drupalLogin($this->rootUser);

    $this->createTemplateType(['id' => 'page_template']);

    $node1 = $this->drupalCreateNode();
    $node3 = $this->drupalCreateNode();
    $this->drupalCreateNode();

    $this->createTemplate([
      'type' => 'page_template',
      'template_entity_id' => $node1->id(),
    ]);
    $this->createTemplate([
      'type' => 'page_template',
      'template_entity_id' => $node3->id(),
    ]);

    $this->drupalGet('/admin/modules/uninstall');
    $session = $this->assertSession();
    $session->pageTextContains('Template entities');
    $session->linkExists('Remove templates');
    $this->clickLink('Remove templates');
    $session->pageTextContains('Are you sure you want to delete all templates?');
    $this->drupalPostForm('/admin/modules/uninstall/entity/template', [], 'Delete all templates');
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[template_entities]' => TRUE], 'Uninstall');
    $this->drupalPostForm(NULL, [], 'Uninstall');
    $session->pageTextContains('The selected modules have been uninstalled.');
    $session->pageTextNotContains('Template entities');
  }

}
