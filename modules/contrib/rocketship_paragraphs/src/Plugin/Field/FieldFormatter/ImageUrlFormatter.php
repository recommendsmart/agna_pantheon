<?php

namespace Drupal\rocketship_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'rs_image_url_field' formatter.
 *
 * @FieldFormatter(
 *   id = "rs_image_url_field",
 *   label = @Translation("Image and url field"),
 *   field_types = {
 *     "rs_display_field"
 *   }
 * )
 */
class ImageUrlFormatter extends FormatterBase {

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
   * Drupal\Core\Entity\EntityDisplayRepositoryInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $class = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $class->renderer = $container->get('renderer');
    $class->languageManager = $container->get('language_manager');
    $class->displayRepository = $container->get('entity_display.repository');
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'responsive_image_style' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $config = $this->getSettings();

    $summary = [];
    if (!empty($config['responsive_image_style'])) {
      $summary[] = 'Using responsive style: ' . $config['responsive_image_style'];
    }
    elseif (isset($config['image_style'])) {
      $summary[] = 'Using image style: ' . $config['image_style'];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $config = $this->getSettings();

    $image_styles = image_style_options(FALSE);

    $element['image_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $config['image_style'],
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    ];
    /** @var \Drupal\responsive_image\Entity\ResponsiveImageStyle[] $entities */
    $entities = ResponsiveImageStyle::loadMultiple();
    $styles = [];
    foreach ($entities as $entity) {
      $styles[$entity->id()] = $entity->label();
    }

    $element['responsive_image_style'] = [
      '#title' => t('Responsive image style'),
      '#type' => 'select',
      '#default_value' => $config['responsive_image_style'],
      '#empty_option' => t('None (original image)'),
      '#options' => $styles,
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
   */
  protected function build(FieldableEntityInterface $entity) {
    $build = [];
    $cache_tags = $entity->getCacheTags();

    if ($entity->hasField('field_p_media_image')) {
      /** @var \Drupal\media\MediaInterface $media */
      $media = $entity->get('field_p_media_image')->entity;
      if (empty($media)) {
        return $build;
      }
      $image_field = $media->get('field_media_image')->getValue();
      $cache_tags = Cache::mergeTags($cache_tags, $media->getCacheTags());
      if (isset($image_field[0]['target_id'])) {
        $image_file = File::load($image_field[0]['target_id']);
        $image = \Drupal::service('image.factory')
          ->get($image_file->getFileUri());
        $variables = [
          'uri' => $image_file->getFileUri(),
          'alt' => $image_field[0]['alt'],
          'title' => $image_field[0]['title'],
        ];
        if ($image) {
          $variables['width'] = $image->getWidth();
          $variables['height'] = $image->getHeight();
        }
        else {
          $variables['width'] = $variables['height'] = NULL;
        }
        $img = [
          '#theme' => 'image',
          '#uri' => $variables['uri'],
          '#height' => $variables['height'],
          '#width' => $variables['width'],
          '#alt' => $variables['alt'],
          '#title' => $variables['title'],
        ];

        // Add image cache tags.
        $cache_tags = Cache::mergeTags($cache_tags, $image_file->getCacheTags());

        // Check if a responsive image style was configured.
        if (!empty($this->getSetting('responsive_image_style'))) {
          $img['#theme'] = 'responsive_image';
          $img['#responsive_image_style_id'] = $this->getSetting('responsive_image_style');
          $img['#attributes']['alt'] = $variables['alt'];
          $img['#attributes']['title'] = $variables['title'];

          // Add the image style as cache tag.
          $image_style = ResponsiveImageStyle::load($this->getSetting('responsive_image_style'));
          $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
        }
        elseif (!empty($this->getSetting('image_style'))) {
          $img['#theme'] = 'image_style';
          $img['#style_name'] = $this->getSetting('image_style');

          // Add the image style as cache tag.
          $image_style = ImageStyle::load($this->getSetting('image_style'));
          $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
        }

        // Check if we need to embed the image in a link.
        if ($entity->hasField('field_p_link')) {
          $url_field = $entity->get('field_p_link')->getValue();
          if (!empty($url_field) && isset($url_field[0]['uri'])) {
            $options = (isset($url_field[0]['options'])) ? $url_field[0]['options'] : [];
            $url = Url::fromUri($url_field[0]['uri'], $options);
            $output = render($img);
            $link = Link::fromTextAndUrl($output, $url);
            $build = $link->toRenderable();
          }
          else {
            // Fall back on only image.
            $build = $img;
          }
        }
        else {
          // Fall back on only image.
          $build = $img;
        }
      }

    }

    // Add cacheable dependencies.
    $build['#cache']['tags'] = $cache_tags;

    return $build;
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

      $template = <<<TWIG
<div {{ attributes }}>
    {{ content }}
</div>
TWIG;

      // Build the attributes.
      $attributes = new Attribute();
      $attributes->addClass('rs-image-url-formatter');

      $build = [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => [
          'attributes' => $attributes,
          'content' => $build,
        ],
      ];

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
      'p_002',
      'p_007_child',
    ]);
  }

}
