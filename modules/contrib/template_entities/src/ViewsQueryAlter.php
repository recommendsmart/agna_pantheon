<?php

namespace Drupal\template_entities;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for altering views queries.
 */
class ViewsQueryAlter implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The template manager service.
   *
   * @var \Drupal\template_entities\TemplateManagerInterface
   */
  protected $templateManager;

  /**
   * The views data.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * A plugin manager which handles instances of views join plugins.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $viewsJoinPluginManager;

  /**
   * Constructs a new ViewsQueryAlter instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\template_entities\TemplateManagerInterface $template_manager
   *   The workspace manager service.
   * @param \Drupal\views\ViewsData $views_data
   *   The views data.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $views_join_plugin_manager
   *   The views join plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, TemplateManagerInterface $template_manager, ViewsData $views_data, ViewsHandlerManager $views_join_plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->templateManager = $template_manager;
    $this->viewsData = $views_data;
    $this->viewsJoinPluginManager = $views_join_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('template_entities.manager'),
      $container->get('views.views_data'),
      $container->get('plugin.manager.views.join')
    );
  }

  /**
   * Implements a hook bridge for hook_views_query_alter().
   *
   * @param \Drupal\views\ViewExecutable $view
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $query
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @see hook_views_query_alter()
   */
  public function alterQuery(ViewExecutable $view, QueryPluginBase $query) {
    // Don't alter any non-sql views queries.
    if (!$query instanceof Sql) {
      return;
    }

    $template_types_by_entity_type = $this->templateManager->getTemplateTypesForEntityType();

    // Find out what entity types are represented in this query.
    $entity_type_ids = [];
    foreach ($query->relationships as $info) {
      $table_data = $this->viewsData->get($info['base']);
      if (empty($table_data['table']['entity type'])) {
        continue;
      }
      $entity_type_id = $table_data['table']['entity type'];
      // This construct ensures each entity type exists only once.
      $entity_type_ids[$entity_type_id] = $entity_type_id;
    }

    $entity_type_definitions = $this->entityTypeManager->getDefinitions();
    foreach ($entity_type_ids as $entity_type_id) {
      if (isset($template_types_by_entity_type[$entity_type_id])) {
        $this->alterQueryForEntityType($query, $entity_type_definitions[$entity_type_id], $template_types_by_entity_type[$entity_type_id]);
      }
    }
  }

  /**
   * Alters the entity type tables for a Views query.
   *
   * This should only be called after determining that this entity type is
   * involved in the query, and that the entity type is templatable.
   *
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The query plugin object for the query.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param array $template_types
   *   The template types used for this entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function alterQueryForEntityType(Sql $query, EntityTypeInterface $entity_type, array $template_types) {
    $table_queue =& $query->getTableQueue();

    $base_entity_table = $entity_type->isTranslatable() ? $entity_type->getDataTable() : $entity_type->getBaseTable();

    foreach ($table_queue as $alias => $table_info) {

      // Any dedicated field table is a candidate.
      if ($table_info['table'] === $base_entity_table) {
        $relationship = $table_info['relationship'];

        // Now add the template association table.
        $this->ensureTemplateAssociationTable($entity_type->id(), $query, $relationship, $template_types);
      }
    }
  }

  /**
   * Adds the 'template__template_entity_id' table to a views query.
   *
   * @param string $entity_type_id
   *   The ID of the entity type to join.
   * @param \Drupal\views\Plugin\views\query\Sql $query
   *   The query plugin object for the query.
   * @param string $relationship
   *   The primary table alias this table is related to.
   * @param array $template_types
   *
   * @return string
   *   The alias of the 'template__template_entity_id' table.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function ensureTemplateAssociationTable($entity_type_id, Sql $query, $relationship, array $template_types) {
    if (isset($query->tables[$relationship]['template__template_entity_id'])) {
      return $query->tables[$relationship]['template__template_entity_id']['alias'];
    }

    $table_data = $this->viewsData->get($query->relationships[$relationship]['base']);

    $query->addTag('template_entities_filtered');

    // Construct the join.
    $definition = [
      'table' => 'template__template_entity_id',
      'field' => 'template_entity_id_target_id',
      'left_table' => $relationship,
      'left_field' => $table_data['table']['base']['field'],
      'extra' => [
        [
          'field' => 'bundle',
          'value' => array_keys($template_types),
        ],
      ],
      'type' => 'LEFT',
    ];

    $join = $this->viewsJoinPluginManager->createInstance('standard', $definition);
    $join->adjusted = TRUE;
    $query->addTable('template__template_entity_id', $relationship, $join);
    $query->addWhere('hide_template_entities', 'template__template_entity_id.template_entity_id_target_id', NULL, 'IS NULL');
  }

}
