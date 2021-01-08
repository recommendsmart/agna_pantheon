<?php

namespace Drupal\entity_inherit\EntityInheritDev;

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_inherit\EntityInherit;
use Drupal\entity_inherit\Utilities\FriendTrait;

/**
 * Development tools.
 */
class EntityInheritDev {

  use FriendTrait;

  /**
   * The EntityInherit singleton (service).
   *
   * @var \Drupal\entity_inherit\EntityInherit
   */
  protected $app;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_inherit\EntityInherit $app
   *   The application singleton.
   */
  public function __construct(EntityInherit $app) {
    $this->friendAccess([EntityInherit::class]);
    $this->app = $app;
  }

  /**
   * Make an assertion. Die on failure.
   *
   * @param mixed $actual
   *   An artibrary value which should equal $expected.
   * @param mixed $expected
   *   An artibrary value which should equal $actual.
   * @param string $message
   *   A message.
   */
  public function assert($actual, $expected, string $message) {
    if ($actual == $expected) {
      $this->print('Assertion passed: ' . $message);
    }
    else {
      $this->print('Assertion failed, dying: ' . $message);
      $this->print('* * * * * * * actual ===>');
      $this->print($actual);
      $this->print('* * * * * * * expected ===>');
      $this->print($expected);
      $this->print('* * * * * * *');
      die(1);
    }
  }

  /**
   * Make sure a node's body value is as expected.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   A Drupal node.
   * @param string $value
   *   An expected value.
   * @param string $message
   *   An assertion message.
   */
  public function assertBodyValue(EntityInterface $node, string $value, string $message) {
    $expected = $value ? [
      [
        'value' => $value,
        'summary' => '',
        'format' => 'full_html',
      ],
    ] : [];

    // See https://github.com/mglaman/phpstan-drupal/issues/159.
    // @phpstan-ignore-next-line
    $this->assert($node->get('body')->getValue(), $expected, 'body value of node ' . $node->id() . ' is ' . serialize($expected) . ': ' . $message);
  }

  /**
   * Create some starter data.
   */
  public function liveTest() {
    $app = $this->app;

    $this->print('Setting, unsetting parent fields.');
    $app->setParentEntityFields([]);
    $this->assert($app->parentFieldFeedback()['severity'], 1, 'Severity is 1 because we have no fields.');
    $app->setParentEntityFields(['field_bla.']);
    $this->assert($app->parentFieldFeedback()['severity'], 2, 'Severity is 2 because the parent field does not exist.');
    $app->setParentEntityFields(['field_bla', 'field_parents']);
    $this->assert($app->parentFieldFeedback()['severity'], 2, 'Severity is 2 because one of the parent fields does not exist.');
    $app->setParentEntityFields(['field_parents']);
    $this->assert($app->parentFieldFeedback()['severity'], 0, 'Severity is 0 because the parent field exists.');
    $first = $this->createNode('First Node', 'page');
    $second = $this->createNode('Second Node', 'page', [$first->id()]);
    $this->assert(array_key_exists('body', $app->wrap($second)->inheritableFields()), TRUE, 'The body field is inheritable.');
    $this->assert(1, count($app->wrap($second)->inheritableFields()), 'The body field is the only inheritable field.');
    $this->happyPath();
  }

  /**
   * Test a use case where a new child is created for an existing parent.
   *
   * The body field should be inherited because it's empty in the child.
   */
  public function happyPath() {
    $this->print('New child of existing parent');
    $parent = $this->createNode('Existing parent', 'page', [], [
      'body' => [
        'value' => 'Hello',
        'format' => 'full_html',
      ],
    ]);
    $child = $this->createNode('New child of existing parent, empty body', 'page', [$parent->id()]);
    $this->assertBodyValue($child, 'Hello', 'Body is inherited from parent to child.');
    $child2 = $this->createNode('New child of existing parent, non-empty body', 'page', [$parent->id()], [
      'body' => [
        'value' => 'Hi',
        'format' => 'full_html',
      ],
    ]);
    $this->assertBodyValue($child2, 'Hi', 'Body is not inherited from parent to child because child defines its own body.');

    $this->print('Existing child gets new parent');
    $child3 = $this->createNode('Child saved once, then resaved with parent', 'page');
    $this->assertBodyValue($child3, '', 'Body is empty, child was just saved with no parent.');
    // See https://github.com/mglaman/phpstan-drupal/issues/159.
    // @phpstan-ignore-next-line
    $child3->set('field_parents', $parent->id());
    $child3->save();
    $this->assertBodyValue($child3, 'Hello', 'Body is set when existing node is saved with a new parent.');

    $this->print('Existing child gets new parent which should not override its body field');
    $child4 = $this->createNode('Child saved once, then resaved with parent', 'page');
    $this->assertBodyValue($child4, '', 'Body is empty, child was just saved with no parent.');
    // See https://github.com/mglaman/phpstan-drupal/issues/159.
    // @phpstan-ignore-next-line
    $child4->set('field_parents', $parent->id());
    // See https://github.com/mglaman/phpstan-drupal/issues/159.
    // @phpstan-ignore-next-line
    $child4->set('body', [
      'value' => 'Hi',
      'format' => 'full_html',
    ]);
    $child4->save();
    $this->assertBodyValue($child4, 'Hi', 'Body is not inherited from new parent because it already contains a value.');

    $this->print('Parent changes; child should change as well.');
    // See https://github.com/mglaman/phpstan-drupal/issues/159.
    // @phpstan-ignore-next-line
    $parent->set('body', [
      'value' => 'Changed in parent, should propagate to child.',
      'format' => 'full_html',
    ]);
    $parent->save();

    $child = Node::load($child->id());
    $this->assertBodyValue($child, 'Changed in parent, should propagate to child.', 'Body of child is updated when parent is updated.');

    $child->set('body', [
      'value' => 'Hi there!',
      'format' => 'full_html',
    ]);
    // See https://github.com/mglaman/phpstan-drupal/issues/159.
    // @phpstan-ignore-next-line
    $parent->set('body', [
      'value' => "Whats up?",
      'format' => 'full_html',
    ]);
    $parent->save();
    $child = Node::load($child->id());
    $this->assertBodyValue($child, 'Hi there!', 'Body of child is not updated by parent unless it is already the same as parent.');
  }

  /**
   * Create a starter node if it does not exist.
   *
   * @param string $title
   *   A title.
   * @param string $type
   *   A type.
   * @param array $parents
   *   Parent nodes.
   * @param array $other
   *   Other information to add to the new node.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A resulting entity.
   */
  public function createNode(string $title, string $type, array $parents = [], array $other = []) {
    $this->print('Creating node ' . $title);
    $node = Node::create([
      'type' => 'page',
      'title' => $title,
      'field_parents' => $this->formatParents($parents),
    ] + $other);
    $node->save();
    return $node;
  }

  /**
   * Format parents to add to a node.
   *
   * @param array $nodes
   *   Nodes in the format [1, 2].
   *
   * @return array
   *   Nodes in the format [
   *     [
   *       'target_id' => 1,
   *     ],
   *     [
   *       'target_id' => 2,
   *     ],
   *   ].
   */
  public function formatParents(array $nodes) : array {
    $return = [];
    array_walk($nodes, function ($item, $key) use (&$return) {
      $return[] = [
        'target_id' => $item,
      ];
    });
    return $return;
  }

  /**
   * Print an arbitrary variable.
   *
   * @param mixed $var
   *   Anything printable.
   */
  public function print($var) {
    if (is_string($var)) {
      print($var . PHP_EOL);
    }
    else {
      print_r($var);
    }
  }

}
