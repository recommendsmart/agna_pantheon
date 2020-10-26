<?php

namespace Drupal\rocketship_event_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'rs_event_dates' formatter.
 *
 * @FieldFormatter(
 *   id = "rs_event_dates",
 *   label = @Translation("Format the event dates"),
 *   field_types = {
 *     "rs_display_field"
 *   }
 * )
 */
class EventDatesFormatter extends FormatterBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $class = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $class->renderer = $container->get('renderer');
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'date_format' => 'd/m/Y',
      'time_format' => 'H:i',
      'combine_duplicates' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $config = $this->getSettings();

    $summary = [];
    $summary[] = 'date format: ' . $config['date_format'];
    $summary[] = 'time format: ' . $config['time_format'];
    $summary[] = 'Combine duplicates: ' . ($config['combine_duplicates'] ? 'Yes' : 'No');

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

    $element['time_format'] = [
      '#title' => t('Time format'),
      '#description' => t('Enter a PHP date format to use as the fallback for when javascript is not available. ONLY time components.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('time_format'),
    ];

    $element['combine_duplicates'] = [
      '#title' => t('Combine duplicates'),
      '#description' => t('When the start and end dates are the same, only show it once. When the start and end times are the same, only show it once.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('combine_duplicates'),
    ];

    return $element;
  }

  /**
   * Build a link.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  protected function build(FieldableEntityInterface $entity) {
    /** @var \Drupal\rocketship_event_core\Entity\EventInterface $entity */
    $config = $this->getSettings();
    $content = $entity->buildDateComponent(
      $config['date_format'],
      $config['time_format'],
      'ds-field-range__event',
      $config['combine_duplicates']
    );

    $template = <<<TWIG
<div {{ attributes }}>
    {{ content }}
</div>
TWIG;

    // Build the attributes.
    $attributes = new Attribute();
    $attributes->addClass('rs-event-dates-formatter');

    return [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => [
        'attributes' => $attributes,
        'content' => $content,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $entity = $items->getEntity();
      $build = $this->build($entity);

      $this->renderer->addCacheableDependency($build, $entity);

      $elements[0] = $build;
      return $elements;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'event';
  }

}
