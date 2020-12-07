<?php

namespace Drupal\template_entities\Plugin\TemplatePlugin;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\template_entities\Entity\TemplateType;
use Symfony\Component\Routing\RouteCollection;

/**
 * Template plugin for nodes.
 *
 * This overrides the derived one but gets derived definition merged in
 * automatically.
 *
 * The main admin term listing is served via the OverviewTerms form which uses
 * the TermStorage->loadTree() method (plus ->getTermIdsWithPendingRevisions()).
 * TermSelection (for entity reference) uses loadTree by default. loadTree uses
 * a query tag with "taxonomy_term_access".
 *
 * @TemplatePlugin(
 *   id = "canonical_entities:taxonomy_term"
 * )
 */
class TermTemplate extends TemplatePluginBase implements DependentPluginInterface {

  /**
   * @inheritDoc
   */
  public function calculateDependencies() {
    return [
      'module' => ['taxonomy'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function selectAlter(SelectInterface $query, TemplateType $template_type) {
    if ($query->hasTag('taxonomy_term_access') && !$this->isReferenceAllowTemplates()) {
      $id_field = $this->entityType->getKey('id');

      $term_table = FALSE;

      foreach ($query->getTables() as $table) {
        if (in_array($table['table'], [
          'taxonomy_term_data',
          'taxonomy_term_field_data',
        ])) {
          $term_table = $table['alias'] ?: $table['table'];
          break;
        }
      }

      if ($term_table) {
        $this->selectAddTemplateFilter($query, $template_type, $term_table, $id_field);
      }
    }
    else {
      parent::selectAlter($query, $template_type);
    }
  }

  /**
   * Not clever but currently the only way to determine if a treeLoad or similar
   * call has come from a reference selection which should allow templates. See
   * TermSelection->getReferenceableEntities().
   *
   * @return bool
   */
  protected function isReferenceAllowTemplates() {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);
    foreach ($trace as $call) {
      if (isset($call['object']) && $call['object'] instanceof SelectionInterface) {
        $configuration = $call['object']->getConfiguration();
        return !empty($configuration['allow_templates']);
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionRoute(RouteCollection $route_collection) {
    $collection_route_collection = new RouteCollection();
    if ($route = $route_collection->get('entity.taxonomy_vocabulary.overview_form')) {
      $collection_route_collection->add('entity.taxonomy_vocabulary.overview_form', $route);
    }
    return $collection_route_collection;
  }

}
