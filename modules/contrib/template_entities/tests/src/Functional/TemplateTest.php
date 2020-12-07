<?php

namespace Drupal\Tests\template_entities\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the template entity.
 *
 * @group template_entities
 */
class TemplateTest extends BrowserTestBase {

  use TemplateEntitiesTestUtilities;

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
   * A test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $content_manager;

  /**
   * A test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $content_editor;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->createTemplateType(['id' => 'page_template']);

    $this->content_manager = $this->drupalCreateUser([
      'manage page_template template',
    ]);

  }

  /**
   * Test creating a template with special characters.
   */
  public function testCreateTemplateThroughUI() {
    $this->drupalCreateNode();
    $node2 = $this->drupalCreateNode();

    // Content manager creates template via UI.
    $this->drupalLogin($this->content_manager);
    $template = $this->createTemplateThroughUi('page_template', 'a0_$()+-/', $node2);
    $this->drupalLogout();

    $this->content_editor = $this->drupalCreateUser([
      'new from page_template template',
    ]);

    // Content editor creates a new node from the template.
    $this->drupalLogin($this->content_editor);
    $this->createNodeFromTemplateThroughUi($template->id(), 'New node from template ' . $template->id());
  }

}
