<?php

namespace Drupal\Tests\template_entities\Kernel;

use Drupal;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;

/**
 * Tests behaviour of templates with taxonomy terms.
 *
 * @group #slow
 * @group workspaces
 */
class TemplateEntitiesTaxonomyIntegrationTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use TaxonomyTestTrait;
  use UserCreationTrait;
  use ViewResultAssertionTrait;
  use TemplateEntitiesTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'taxonomy',
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
   * Vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * An array of terms created before installing the template entities module.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * Tests various scenarios for creating and deploying content in workspaces.
   */
  public function testTemplateTerms() {
    $this->initializeTemplateEntitiesModule();

    // Create a term template type.
    $template_type = $this->createTemplateType([
      'type' => 'canonical_entities:taxonomy_term',
      'label' => 'Term template type',
      'description' => 'Test term template type.',
      'bundles' => [$this->vocabulary->id()],
    ]);

    $template_type_id = $template_type->id();

    // Create a template.
    $template = $this->createTemplate([
      'template_entity_id' => $this->terms[1]->id(),
      'type' => $template_type_id,
    ]);

    $this->assertEqual($template->getSourceEntity()
      ->id(), $this->terms[1]->id(), 'Source entity of template is expected.');

    $tid = $this->terms[1]->id();

    $this->entityTypeManager->getStorage('taxonomy_term')->resetCache([$tid]);
    $expected_terms = [
      $this->terms[0]->id() => $this->terms[0],
      $this->terms[2]->id() => $this->terms[2],
    ];

    $this->assertEntityQuery($expected_terms, 'taxonomy_term');

    $loaded_term = Term::load($tid);
    $this->assertEqual($this->terms[1]->id(), $loaded_term->id(), 'Term load is not subject to entity query decoration.');

    /** @var \Drupal\Core\Entity\EntityListBuilder $node_list_builder */
    $term_list_builder = $this->entityTypeManager->getHandler('taxonomy_term', 'list_builder');
    $term_list = $term_list_builder->load();
    $this->assertEqual(array_keys($term_list), array_keys($expected_terms), 'List builder terms');

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $term_tree = $term_storage->loadTree($this->vocabulary->id());
    $term_tree_ids = [];
    $id_key = $this->entityTypeManager->getDefinition('taxonomy_term')
      ->getKey('id');
    array_walk($term_tree, function ($v, $k) use (&$term_tree_ids, $id_key) {
      $term_tree_ids[$v->{$id_key}] = $v->{$id_key};
    });
    $term_tree_ids = array_keys($term_tree_ids);
    $expected_terms = array_keys($expected_terms);
    sort($term_tree_ids);
    sort($expected_terms);
    $this->assertEqual($term_tree_ids, $expected_terms, 'Term tree terms');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = Drupal::entityTypeManager();

    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    $this->installConfig(['filter', 'taxonomy', 'system']);

    $this->installSchema('system', ['key_value_expire', 'sequences']);

    $this->vocabulary = $this->createVocabulary();

    $this->setCurrentUser($this->createUser(['administer taxonomy']));

    // Create three terms.
    $this->createdTimestamp = Drupal::time()->getRequestTime();
    $this->terms[] = $this->createTerm($this->vocabulary);
    $this->terms[] = $this->createTerm($this->vocabulary);
    $this->terms[] = $this->createTerm($this->vocabulary);
  }

}
