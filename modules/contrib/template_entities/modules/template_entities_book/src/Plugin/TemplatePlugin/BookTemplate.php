<?php

namespace Drupal\template_entities_book\Plugin\TemplatePlugin;

use Drupal\book\BookManager;
use Drupal\book\BookManagerInterface;
use Drupal\book\BookOutlineStorageInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\node\Entity\Node;
use Drupal\template_entities\Entity\Template;
use Drupal\template_entities\Entity\TemplateType;
use Drupal\template_entities\Plugin\TemplatePlugin\NodeTemplate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Template plugin for books.
 *
 *
 * This does not override any derived plugin.
 *
 * @TemplatePlugin(
 *   id = "book",
 *   label = @Translation("Book"),
 *   entity_type_id = "node"
 * )
 */
class BookTemplate extends NodeTemplate implements DependentPluginInterface, PluginWithFormsInterface {

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected BookManagerInterface $bookManager;

  /**
   * Book outline storage.
   *
   * @var \Drupal\book\BookOutlineStorageInterface
   */
  protected BookOutlineStorageInterface $bookOutlineStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $book_template_plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $book_template_plugin->bookManager = \Drupal::service('book.manager');
    $book_template_plugin->bookOutlineStorage = \Drupal::service('book.outline_storage');

    return $book_template_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function duplicateEntity(EntityInterface $entity, Template $template) {
    /** @var \Drupal\node\Entity\Node $duplicate_node */
    $duplicate_node = parent::duplicateEntity($entity, $template);

    // Mark book as new and save template book to allow copying after the
    // new book has been created.
    $duplicate_node->original_template_book = $duplicate_node->book;

    // Prepare defaults for the add/edit form.
    $duplicate_node->book = ['bid' => 'new'];

    $account = \Drupal::currentUser();
    if (($account->hasPermission('add content to books') || $account->hasPermission('administer book outlines'))) {

      $query = \Drupal::request()->query;
      if (!is_null($query->get('parent')) && is_numeric($query->get('parent'))) {
        // Handle "Add child page" links:
        $parent = $this->bookManager->loadBookLink($query->get('parent'), TRUE);

        if ($parent && $parent['access']) {
          $duplicate_node->book['bid'] = $parent['bid'];
          $duplicate_node->book['pid'] = $parent['nid'];
        }
      }
    }

    return $duplicate_node;
  }

  /**
   * @inheritDoc
   */
  public function duplicateEntityInsert(EntityInterface $entity) {
    parent::duplicateEntityInsert($entity);

    /** @var \Drupal\node\Entity\Node $duplicate_book_node */
    $duplicate_book_node = $entity;

    /** @var Template $template */
    $template = $duplicate_book_node->template;

    /** @var \Drupal\node\Entity\Node $original_book_node */
    $original_book_node = $template->getSourceEntity();

    if (!empty($original_book_node->book['bid'])) {
      $result = $this->bookOutlineStorage->getBookMenuTree($original_book_node->book['bid'], [], 1, BookManager::BOOK_MAX_DEPTH);

      // Duplicate all the book pages.
      // Reverse the array so we can use the more efficient array_pop() function.
      $links = array_reverse($result->fetchAll());

      // Remove the old book node from the links.
      array_pop($links);

      $this->duplicateBookOutlineRecursive($links, 2, $duplicate_book_node, $duplicate_book_node->id());
    }
  }

  /**
   * Builds the data representing a book tree.
   *
   * The function is a bit complex because the rendering of a link depends on
   * the next book link.
   *
   * @param array $links
   *   A flat array of book links that are part of the book. Each array element
   *   is an associative array of information about the book link, containing
   *   the fields from the {book} table. This array must be ordered depth-first.
   * @param int $depth
   *   The minimum depth to include in the returned book tree.
   * @param \Drupal\node\Entity\Node $new_book_node
   *   The new book node.
   * @param int $pid
   *   The current new parent node id.
   */
  protected function duplicateBookOutlineRecursive(&$links, $depth, Node $new_book_node, $pid) {
    while ($item = array_pop($links)) {

      // Create and save duplicate book page.
      $duplicate_book_page = $this->duplicateBookPage($item->nid, $new_book_node, $pid);

      // Look ahead to the next link, but leave it on the array so it's
      // available to other recursive function calls if we return or build a
      // sub-tree.
      $next = end($links);
      // Check whether the next link is the first in a new sub-tree.
      if ($next && $next->depth > $depth) {
        // Recursively call buildBookOutlineRecursive to build the sub-tree.
        $this->duplicateBookOutlineRecursive($links, $next->depth, $new_book_node, $duplicate_book_page->id());

        // Fetch next link after filling the sub-tree.
        $next = end($links);
      }
      // Determine if we should exit the loop and $request = return.
      if (!$next || $next->depth < $depth) {
        break;
      }
    }
  }

  /**
   * @param $original_nid
   * @param $new_book_node
   * @param $pid
   *
   * @return \Drupal\node\Entity\Node
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function duplicateBookPage($original_nid, Node $new_book_node, $pid) {
    $original_node = Node::load($original_nid);

    /** @var \Drupal\node\Entity\Node $duplicate */
    $duplicate = $original_node->createDuplicate();

    $duplicate->setChangedTime($new_book_node->getChangedTime());
    $duplicate->setRevisionLogMessage($new_book_node->getRevisionLogMessage());
    $duplicate->setCreatedTime($new_book_node->getCreatedTime());
    $duplicate->setOwnerId($new_book_node->getOwnerId());
    $duplicate->setPublished($new_book_node->isPublished());

    // Set up the new book info which will be processed by
    // BookManager::updateOutline() via the book_node_insert() hook.
    $duplicate->book = [
      'bid' => $new_book_node->book['bid'],
      'pid' => $pid,
    ] + $this->bookManager->getLinkDefaults('new');

    $duplicate->save();

    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // Add book module as a dependency.
    return NestedArray::mergeDeep(
      parent::calculateDependencies(),
      ['module' => ['book']],
    );
  }

  /**
   * @inheritDoc
   */
  public function getBundleOptions() {
    // Only return bundles that can be used for books.
    return array_filter(parent::getBundleOptions(), function($bundle) {
      return book_type_is_allowed($bundle);
    }, ARRAY_FILTER_USE_KEY);

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return NestedArray::mergeDeep(
      parent::defaultConfiguration(),
      ['add_child_from_template' => FALSE],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function templateForm($form, FormStateInterface $form_state) {
    $form['add_child_from_template'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add child from template link'),
      '#description' => t('Check this box if you want to add a link to book pages which permits adding child pages using templates of this type. If the template is itself a book, the whole book will be added as a section.'),
      '#default_value' => $this->configuration['add_child_from_template'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function templateSubmit($form, FormStateInterface $form_state) {
    $this->configuration['add_child_from_template']
      = (boolean) $form_state->getValue('add_child_from_template');
  }

  /**
   * @inheritDoc
   */
  public function entityQueryAlter(SelectInterface $query, TemplateType $template_type) {
    parent::entityQueryAlter($query, $template_type);
  }


  /**
   * {@inheritdoc}
   */
  public function selectAlter(SelectInterface $query, TemplateType $template_type) {
    if ($query->hasTag('entity_reference')) {
      $handler = $query->getMetaData('entity_reference_selection_handler');
      if ($template_type->id() === $handler->getConfiguration()['template_type_id']) {
        // True if this entity reference selection query is for a template
        // for this template type.
        $query->innerJoin('book', 'book', '%alias.bid = base_table.nid');
      }
    }
    elseif ($query->hasTag('template_entities_filtered')) {
      // Exclude children of books used in a template.
      if ($query->hasTag('views') && $query->hasTag('views_content')) {
        $book_alias = $query->leftJoin('book', 'book', '%alias.nid = node_field_data.nid');
        // Do another template left join on  the book table.
        $this->selectAddTemplateFilter($query, $template_type, $book_alias, 'bid');
      }
    }
    elseif ($query->hasTag('template_entities.get_books')) {
      // If user doesn't have permission to administer this template type
      // then don't permit access to the book in book overview or
      // book selection widget.
      if (!\Drupal::currentUser()->hasPermission('manage ' . $template_type->id() . ' template')) {
        $this->selectAddTemplateFilter($query, $template_type, 'book', 'bid');
      }
    }
  }

}

