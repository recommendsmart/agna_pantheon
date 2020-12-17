<?php

namespace Drupal\Tests\entity_reference_views\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the admin UI changes introduced by the module.
 *
 * @group entity_reference_views
 */
class AdminUiTest extends BrowserTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = ['entity_reference_views_test'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * The taxonomy terms.
   *
   * @var \Drupal\taxonomy\Entity\Term[]
   */
  protected $terms;

  /**
   * The node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The WebAssert.
   *
   * @var \Drupal\Tests\WebAssert
   */
  private $assert;

  /**
   * The Document Element.
   *
   * @var \Behat\Mink\Element\DocumentElement
   */
  private $page;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setup();

    $this->user = $this->createUser([], NULL, TRUE);

    // Setup the widget with the view in test module.
    $form_display = EntityFormDisplay::load('node.article.default');
    $field_tags = $form_display->getComponent('field_tags');
    $field_tags['type'] = 'entity_reference_views_autocomplete';
    $field_tags['settings']['is_active'] = TRUE;
    $field_tags['settings']['view'] = 'term_list:field_attachment';
    $form_display
      ->setComponent('field_tags', $field_tags)
      ->save();

    // Setup the content.
    $this->terms['fruits'] = Term::create([
      'vid' => 'tags',
      'name' => 'Fruits',
      'description' => 'A seed-bearing structure in flowering plants formed from the ovary after flowering.',
    ]);
    $this->terms['fruits']->save();

    $this->terms['vegetables'] = Term::create([
      'vid' => 'tags',
      'name' => 'Vegetables',
      'description' => 'A plant or part of a plant used as food, such as a cabbage, potato, turnip, or bean.',
    ]);
    $this->terms['vegetables']->save();

    $this->node = Node::create([
      'title' => $this->randomString(),
      'type' => 'article',
      'description' => $this->randomString(200),
      'field_tags' => [
        $this->terms['fruits']->id(),
        $this->terms['vegetables']->id(),
      ],
    ]);
    $this->node->save();

    if (!isset($this->assert) || !isset($this->page)) {
      $this->assert = $this->assertSession();
      $this->page = $this->getSession()->getPage();
    }
  }

  /**
   * Tests the field widget.
   */
  public function testsAdminUi() {
    $this->drupalLogin($this->user);
    // Visit the node edit page.
    $this->drupalGet('node/' . $this->node->id() . '/edit');

    // Check for the view's content.
    $this->assert->fieldValueEquals('field_tags[0][target_id][target_id]', 'fruits (1)');
    $this->assert->pageTextContainsOnce('A seed-bearing structure in flowering plants formed from the ovary after flowering.');
    $this->assert->fieldValueEquals('field_tags[1][target_id][target_id]', 'vegetables (2)');
    $this->assert->pageTextContainsOnce('A plant or part of a plant used as food, such as a cabbage, potato, turnip, or bean.');
  }

}
