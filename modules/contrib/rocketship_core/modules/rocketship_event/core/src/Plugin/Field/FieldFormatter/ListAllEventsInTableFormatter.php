<?php

namespace Drupal\rocketship_event_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'rs_first_event_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "rs_event_listing_table",
 *   label = @Translation("List all events in table"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ListAllEventsInTableFormatter extends EntityReferenceFormatterBase {

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $class = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $class->renderer = $container->get('renderer');
    $class->dateFormatter = $container->get('date.formatter');
    return $class;
  }

  /**
   * Build render array.
   *
   * @param \Drupal\rocketship_event_core\Entity\EventInterface[] $list
   *   The events.
   *
   * @return array
   *   Render array.
   */
  protected function build(array $list) {
    $build = [];

    foreach ($list as $event) {
      $time = $event->buildDateComponent(NULL, 'H:i', 'time-table-element');
      if ($event->get('field_event_closed')->value) {
        $time = ['#markup' => '/'];
      }

      $build['#rows'][] = [
        'weekday' =>
          [
            'data' =>
              [
                '#markup' => $this->dateFormatter->format($event->getStartDate(), 'custom', 'D') . ':',
              ],
          ],
        'date' =>
          [
            'data' =>
              [
                $event->buildDateComponent('d/m/Y', NULL, 'date-table-element'),
              ],
          ],
        'time' =>
          [
            'data' => [
              $time,
            ],
          ],
      ];
      $this->renderer->addCacheableDependency($build, $event);
    }

    if (!empty($build)) {
      $build['#type'] = 'table';
      $build['#responsive'] = FALSE;
      $build['#cache']['contexts'][] = 'timezone';
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $list = $this->getEntitiesToView($items, $langcode);
    $build = $this->build($list);
    $entity = $items->getEntity();
    $this->renderer->addCacheableDependency($build, $entity);
    $elements[0] = $build;
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_type = $field_definition->getFieldStorageDefinition()
      ->getSetting('target_type');
    return $field_definition->getTargetEntityTypeId() === 'node' && $field_definition->getTargetBundle() === 'event' && $target_type === 'event';
  }

}
