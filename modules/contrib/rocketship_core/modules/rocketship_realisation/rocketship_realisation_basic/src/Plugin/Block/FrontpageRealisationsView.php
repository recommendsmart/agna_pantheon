<?php

namespace Drupal\rocketship_realisation_basic\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'FrontpageRealisationsView' block.
 *
 * @Block(
 *  id = "frontpage_realisation_view",
 *  admin_label = @Translation("Frontpage Realisations listing"),
 * )
 */
class FrontpageRealisationsView extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\views\ViewExecutableFactory definition.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $executableFactory;

  /**
   * Drupal\Core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * FrontpageRealisationsView constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\views\ViewExecutableFactory $executableFactory
   *   Views Executable Factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language Manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ViewExecutableFactory $executableFactory,
    LanguageManagerInterface $languageManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->executableFactory = $executableFactory;
    $this->languageManager = $languageManager;
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
      $container->get('views.executable'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'languages';
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $tags[] = 'node_list';
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Load the view, load the page via UUID (locked in migrate)
    $uuid = 'ab3aa739-180e-4432-9a19-1ea36a916e1c';
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['uuid' => $uuid]);
    if ($nodes) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = reset($nodes);
      $language = $this->languageManager->getCurrentLanguage()->getId();
      if ($node->hasTranslation($language)) {
        $node = $node->getTranslation($language);
      }
      $build['header'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['realisation-front-header-wrapper']],
      ];
      $build['header']['title']['#markup'] = '<h2>' . $node->label() . '</h2>';
      $link = $node->toLink($this->t('View all realisation items'))->toRenderable();
      $build['header']['link'] = $link;
    }

    $view_id = 'realisation_overview';
    $view_display = 'realisation_overview_front';
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);
    if ($view) {
      /** @var \Drupal\views\ViewEntityInterface $view */
      $executable = $this->executableFactory->get($view);
      if (is_object($executable)) {
        $executable->setDisplay($view_display);
        $executable->preExecute();
        $executable->execute();
        $build['view'] = $executable->buildRenderable($view_display);
        // Cache this one, speed up homepage a bit for webadmin.
        $build['view']['#cache']['max-age'] = -1;
        $build['view']['#cache']['tags'][] = 'node_list';
      }
    }

    return $build;
  }

}
