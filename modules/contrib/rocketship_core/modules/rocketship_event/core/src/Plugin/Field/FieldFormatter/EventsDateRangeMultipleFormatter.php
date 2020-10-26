<?php

namespace Drupal\rocketship_event_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rocketship_event_core\Entity\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'rs_events_date_range' formatter.
 *
 * @FieldFormatter(
 *   id = "rs_events_date_range",
 *   label = @Translation("Date range for events: start & end"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EventsDateRangeMultipleFormatter extends EntityReferenceFormatterBase {

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
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'date_format' => 'd M',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $config = $this->getSettings();

    $summary = [];
    $summary[] = 'date format: ' . $config['date_format'];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['date_format'] = [
      '#title' => t('Date format'),
      '#description' => t('Enter a PHP date format to use as the fallback for when javascript is not available. Exclude time components.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('date_format'),
    ];

    return $element;
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
    $first = reset($list);
    $last = end($list);

    if ($first && $last) {
      $first_date_formatter = $this->dateFormatter->format(
        $first->getStartDate(),
        'custom',
        $this->getSetting('date_format')
      );
      $last_date_formatter = $this->dateFormatter->format(
        $last->getStartDate(),
        'custom',
        $this->getSetting('date_format')
      );

      $build['date']['#markup'] =
        '<div class="date-wrapper first-date">' . $first_date_formatter . '</div>
        <div class="date-wrapper last-date">' . $last_date_formatter . '</div>';

      if ($first_date_formatter === $last_date_formatter) {
        $build['date']['#markup'] =
          '<div class="date-wrapper single-date">' . $first_date_formatter . '</div>';
      }
      $this->renderer->addCacheableDependency($build, $first);
      $this->renderer->addCacheableDependency($build, $last);
    }

    $build['#cache']['contexts'][] = 'timezone';
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
