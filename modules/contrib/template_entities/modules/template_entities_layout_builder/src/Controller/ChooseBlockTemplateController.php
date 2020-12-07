<?php

namespace Drupal\template_entities_layout_builder\Controller;

use Drupal\Core\Url;
use Drupal\layout_builder\Controller\ChooseBlockController;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\template_entities\Entity\TemplateType;
use Drupal\template_entities\TemplatePermissions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to choose a new block.
 *
 * @internal
 *   Controller classes are internal.
 */
class ChooseBlockTemplateController extends ChooseBlockController {

  /**
   * @var \Drupal\template_entities\TemplateManagerInterface
   */
  protected $templateManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $controller = parent::create($container);
    $controller->templateManager = $container->get('template_entities.manager');
    return $controller;
  }

  /**
   * Provides the UI for choosing a new block tem.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section to splice.
   * @param string $region
   *   The region the block is going in.
   *
   * @return array
   *   A render array.
   */
  public function build(SectionStorageInterface $section_storage, int $delta, $region) {
    $build = parent::build($section_storage, $delta, $region);

    $block_content_template_type_ids = array_keys($this->templateManager->getTemplateTypesForEntityType('block_content'));
    $block_content_templates_by_type = [];

    // Find how many of each and whether user has create from permission.
    foreach ($block_content_template_type_ids as $template_type_id) {
      if ($this->currentUser->hasPermission(TemplatePermissions::newFromTemplateId($template_type_id))) {
        $templates = $this->templateManager->getTemplatesOfType($template_type_id);
        if (!empty($templates)) {
          $block_content_templates_by_type[$template_type_id] = $templates;
        }
      }
    }

    if (!empty($block_content_templates_by_type)) {
      $build['add_block_from_template'] = [];
      // Set the render order.
      $build['add_block']['#weight'] = -2;
      $build['add_block_from_template']['#weight'] = -1;

      foreach ($block_content_templates_by_type as $template_type_id => $block_content_templates) {
        $block_content_template_type =  TemplateType::load($template_type_id);

        if (count($block_content_templates) === 1) {
          $template = reset($block_content_templates);

          $url = Url::fromRoute('template_entities_layout_builder.add_block_from_template', [
            'section_storage_type' => $section_storage->getStorageType(),
            'section_storage' => $section_storage->getStorageId(),
            'delta' => $delta,
            'region' => $region,
            'template_id' => $template->id(),
          ]);
        }
        else {
          $url = Url::fromRoute('template_entities_layout_builder.choose_inline_block_template', [
            'section_storage_type' => $section_storage->getStorageType(),
            'section_storage' => $section_storage->getStorageId(),
            'delta' => $delta,
            'region' => $region,
            'template_type_id' => $template_type_id,
          ]);
        }
        if (isset($url)) {
          $build['add_block_from_template'][$template_type_id] = [
            '#type' => 'link',
            '#url' => $url,
            '#title' => $this->t('Create @entity_type from @template_type template', [
              '@entity_type' => $this->entityTypeManager->getDefinition('block_content')->getSingularLabel(),
              '@template_type' => $block_content_template_type->label(),
            ]),
            '#attributes' => $this->getAjaxAttributes(),
            '#access' => $this->currentUser->hasPermission('create and edit custom blocks from templates'),
          ];
          $build['add_block_from_template'][$template_type_id]['#attributes']['class'][] = 'inline-block-create-button';

        }
      }
    }

    return $build;
  }

  /**
   * Provides the UI for choosing a new inline block.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section to splice.
   * @param string $region
   *   The region the block is going in.
   * @param $template_type_id
   *
   * @return array
   *   A render array.
   */
  public function inlineBlockTemplateList(SectionStorageInterface $section_storage, int $delta, $region, $template_type_id) {
    $templates = $this->templateManager->getTemplatesOfType($template_type_id);

    $build['links'] = $this->getBlockTemplateLinks($section_storage, $delta, $region, $templates);
    $build['links']['#attributes']['class'][] = 'inline-block-list';
    foreach ($build['links']['#links'] as &$link) {
      $link['attributes']['class'][] = 'inline-block-list__item';
    }
    $build['back_button'] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('layout_builder.choose_block',
        [
          'section_storage_type' => $section_storage->getStorageType(),
          'section_storage' => $section_storage->getStorageId(),
          'delta' => $delta,
          'region' => $region,
        ]
      ),
      '#title' => $this->t('Back'),
      '#attributes' => $this->getAjaxAttributes(),
    ];

    $build['links']['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockAddHighlightId($delta, $region);
    return $build;
  }

  /**
   * Gets a render array of block template links.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section to splice.
   * @param string $region
   *   The region the block is going in.
   * @param \Drupal\template_entities\Entity\TemplateInterface[]
   *   The information for each block.
   *
   * @return array
   *   The block links render array.
   */
  protected function getBlockTemplateLinks(SectionStorageInterface $section_storage, int $delta, $region, array $templates) {
    $links = [];
    /** @var \Drupal\template_entities\Entity\TemplateInterface $template */
    foreach ($templates as $template_id => $template) {
      $attributes = $this->getAjaxAttributes();
      $attributes['class'][] = 'js-layout-builder-block-link';
      $link = [
        'title' => $template->label(),
        'url' => Url::fromRoute('template_entities_layout_builder.add_block_from_template',
          [
            'section_storage_type' => $section_storage->getStorageType(),
            'section_storage' => $section_storage->getStorageId(),
            'delta' => $delta,
            'region' => $region,
            'template_id' => $template_id,
          ]
        ),
        'attributes' => $attributes,
      ];

      $links[] = $link;
    }
    return [
      '#theme' => 'links',
      '#links' => $links,
      '#access' => $this->currentUser->hasPermission('create and edit custom blocks from templates'),
    ];
  }

}
