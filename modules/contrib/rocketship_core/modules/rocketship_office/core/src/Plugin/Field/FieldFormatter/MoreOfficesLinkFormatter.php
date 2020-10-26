<?php

namespace Drupal\rocketship_office_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'rs_office_more_offices' formatter.
 *
 * @FieldFormatter(
 *   id = "rs_office_more_offices",
 *   label = @Translation("More contact offices link"),
 *   field_types = {
 *     "rs_display_field"
 *   }
 * )
 */
class MoreOfficesLinkFormatter extends FormatterBase {

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
      'uuid' => '69352ecf-1e01-49fb-a7b6-a8490803a9da',
      'link_text' => 'More contact offices',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $config = $this->getSettings();

    $summary = [];
    $summary[] = 'UUID: ' . $config['uuid'];
    $summary[] = 'Link text: ' . $config['link_text'];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['uuid'] = [
      '#title' => t('Node UUID'),
      '#description' => t('Enter the UUID of the node to link to. Defaults to the migrated node.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('uuid'),
      '#required' => TRUE,
    ];
    $element['link_text'] = [
      '#title' => t('Link text'),
      '#description' => t('Text for the link to the node.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('link_text'),
      '#required' => TRUE,
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function build(FieldableEntityInterface $entity) {
    $config = $this->getSettings();
    $uuid = $config['uuid'];

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['uuid' => $uuid]);
    if (!$nodes) {
      return [];
    }
    /** @var \Drupal\node\NodeInterface $node */
    $node = reset($nodes);
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($node->hasTranslation($language)) {
      $node = $node->getTranslation($language);
    }
    $url = $node->toUrl();
    $text = $this->t($config['link_text']);

    $content = Link::fromTextAndUrl($text, $url)->toRenderable();

    $template = <<<TWIG
<div {{ attributes }}>
    {{ content }}
</div>
TWIG;

    // Build the attributes.
    $attributes = new Attribute();
    $attributes->addClass('rs-more-offices-formatter');

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
      if (empty($build)) {
        return $elements;
      }

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
    return $field_definition->getTargetEntityTypeId() === 'node' && $field_definition->getTargetBundle() === 'office';
  }

}
