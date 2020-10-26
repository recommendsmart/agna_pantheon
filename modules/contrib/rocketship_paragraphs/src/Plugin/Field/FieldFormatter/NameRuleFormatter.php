<?php

namespace Drupal\rocketship_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'rs_name_rule_field' formatter.
 *
 * @FieldFormatter(
 *   id = "rs_name_rule_field",
 *   label = @Translation("Name and rule field"),
 *   field_types = {
 *     "rs_display_field"
 *   }
 * )
 */
class NameRuleFormatter extends FormatterBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $class = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $class->renderer = $container->get('renderer');
    $class->languageManager = $container->get('language_manager');
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'separator' => '-',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $config = $this->getSettings();

    $summary = '';
    if (isset($config['separator'])) {
      $summary = 'Separator: ' . $config['separator'];
    }
    return [$summary];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['separator'] = [
      '#title' => t('Separator'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('separator'),
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
    $build = [];
    $cache_tags = $entity->getCacheTags();

    // Build render array values.
    if ($entity->hasField('field_p_name')) {
      $name_field = $entity->get('field_p_name')->getValue();
      if (isset($name_field[0]['value'])) {
        $name = $name_field[0]['value'];
        $build = [
          '#theme' => 'name_rule_field',
          '#name' => [
            '#markup' => $name,
          ],
        ];
      }

      // Try to fetch the extra rule field.
      if ($entity->hasField('field_p_extra_rule')) {
        $extra_rule_field = $entity->get('field_p_extra_rule')->getValue();
        if (isset($extra_rule_field[0]['value'])) {
          $extra_rule = $extra_rule_field[0]['value'];
          $build['#separator'] = [
            '#markup' => $this->getSetting('separator'),
          ];
          $build['#extra_rule'] = [
            '#markup' => $extra_rule,
          ];
        }
      }
    }

    // Add cacheable dependencies.
    $build['#cache']['tags'] = $cache_tags;

    $template = <<<TWIG
<div {{ attributes }}>
    {{ content }}
</div>
TWIG;

    // Build the attributes.
    $attributes = new Attribute();
    $attributes->addClass('rs-name-rule-formatter');

    return [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => [
        'attributes' => $attributes,
        'content' => $build,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $entity = $items->getEntity();
    if ($entity instanceof TranslatableInterface) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      if ($entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }
    }

    foreach ($items as $delta => $item) {
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
    if ($field_definition->getTargetEntityTypeId() !== 'paragraph') {
      return FALSE;
    }
    return in_array($field_definition->getTargetBundle(), [
      'body_testimonial',
    ]);
  }

}
