<?php

namespace Drupal\openfarm_statistics\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\openfarm_statistics\Form\OpenfarmStatisticsDateSelectForm;
use Drupal\openfarm_statistics\OpenfarmStatisticsFilterTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'DemographicChart' block.
 *
 * @Block(
 *  id = "openfarm_statistics_charts_block",
 *  admin_label = @Translation("Charts block"),
 * )
 *
 * @group openfarm_charts
 */
class DemographicChart extends BlockBase implements ContainerFactoryPluginInterface {

  use OpenfarmStatisticsFilterTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Json serialization service.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $serializer;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entityTypeManager,
    Json $json
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->serializer = $json;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('serialization.json')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = $this->serializer->encode($this->getData());
    $build['#attached']['drupalSettings']['charts']['data'] = $data;
    $build['#attached']['library'][] = 'openfarm_statistics/openfarm_statistics.charts';
    $build['#cache']['contexts'] = ['url.query_args'];

    $build[] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['charts']],
    ];

    return $build;
  }

  /**
   * Get data for chart.
   *
   * @return array
   *   Data.
   */
  private function getData() {
    $storage = $this->entityTypeManager->getStorage('user');
    $query = $storage->getQuery();
    $query->exists('field_gender')
      ->exists('field_age_group')
      ->condition('status', '1');

    $filters = $this->getFilters();
    if (isset($filters[OpenfarmStatisticsDateSelectForm::TO])) {
      $query->condition('created', $filters[OpenfarmStatisticsDateSelectForm::TO], '<=');
    }
    if (isset($filters[OpenfarmStatisticsDateSelectForm::FROM])) {
      $query->condition('created', $filters[OpenfarmStatisticsDateSelectForm::FROM], '>=');
    }

    $ids = $query->execute();
    $users = $storage->loadMultiple($ids);

    $data = [];
    foreach ($users as $user) {
      $data[] = [
        'gender' => $user->field_gender->value,
        'age' => $user->field_age_group->value,
      ];
    }

    return $data;
  }

}
