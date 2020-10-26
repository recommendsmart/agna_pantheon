<?php

namespace Drupal\rocketship_event_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'rs_first_event_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "rs_first_event_formatter",
 *   label = @Translation("First event formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class FirstEventFormatter extends EntityReferenceFormatterBase {

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
    if (empty($list)) {
      return $build;
    }

    $event = reset($list);
    if ($event) {
      // Only need the first event.
      $build['date_first_teaser']['#markup'] = '<div class="date-first-teaser"><span class="day">'
        . $this->dateFormatter->format($event->getStartDate(), 'custom', 'd') .
        '</span><span class="month">' .
        $this->dateFormatter->format($event->getStartDate(), 'custom', 'M') .
        '</span></div>';
      $build['#cache']['contexts'][] = 'timezone';
      $this->renderer->addCacheableDependency($build, $event);
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
