<?php

namespace Drupal\rocketship_event_basic\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'FrontpageEventView' block.
 *
 * @Block(
 *  id = "frontpage_event_view",
 *  admin_label = @Translation("Frontpage Event listing"),
 * )
 */
class FrontpageEventView extends BlockBase implements ContainerFactoryPluginInterface {

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
   * FrontpageEventView constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\views\ViewExecutableFactory $executableFactory
   *   Views executable factorty service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager service.
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
    $contexts[] = 'languages:language_interface';
    $contexts[] = 'user.permissions';
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
    return strtotime('tomorrow') - time();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Load the view, load the page via UUID (locked in migrate)
    $uuid = 'cfc4edce-b6b1-435c-96e0-3d935273a391';
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
        '#attributes' => ['class' => ['events-front-header-wrapper']],
      ];
      $build['header']['title']['#markup'] = '<h2>' . $node->label() . '</h2>';
      $link = $node->toLink($this->t('View all events'))->toRenderable();
      $build['header']['link'] = $link;
    }

    $view_id = 'event_overview';
    $view_display = 'event_overview_front';
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);
    if ($view) {
      /** @var \Drupal\views\ViewEntityInterface $view */
      $executable = $this->executableFactory->get($view);
      if (is_object($executable)) {
        $executable->setDisplay($view_display);
        $executable->preExecute();
        $executable->execute();
        $build['view'] = $executable->buildRenderable($view_display);
        $build['view']['#cache']['max-age'] = strtotime('tomorrow') - time();
        $build['view']['#cache']['tags'][] = 'node_list';
        $build['view']['#cache']['contexts'][] = 'timezone';
      }
    }

    return $build;
  }

}
