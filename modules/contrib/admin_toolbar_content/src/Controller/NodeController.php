<?php

namespace Drupal\admin_toolbar_content\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Returns responses for Node routes.
 */
class NodeController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays add content links for available content types.
   *
   * Redirects to node/add/[type] if only one content type is available.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param $collection
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse A render array for a list of the node types that can be added; however,
   * A render array for a list of the node types that can be added; however,
   * if there is only one node type defined for the site, the function
   * will return a RedirectResponse to the node add page for that one node
   * type.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addPage(Request $request, $collection) {
    $build = [
      '#theme' => 'node_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition('node_type')->getListCacheTags(),
      ],
    ];

    $content = [];
    $content_type_collections = \Drupal::service('module_handler')->invokeAll('content_type_collections');

    // Only use node types the user has access to.
    foreach ($this->entityTypeManager()->getStorage('node_type')->loadMultiple() as $type) {
      $content_type = $type->get('type');
      if (in_array($content_type, $content_type_collections[$collection]['content_types'])) {
        $access = $this->entityTypeManager()->getAccessControlHandler('node')->createAccess($type->id(), NULL, [], TRUE);
        if ($access->isAllowed()) {
          $content[$type->id()] = $type;
        }
        $this->renderer->addCacheableDependency($build, $access);
      }
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('node.add', ['node_type' => $type->id()]);
    }

    $build['#content'] = $content;

    return $build;
  }


  public function addPageTitle($collection) {
    $content_type_collections = \Drupal::service('module_handler')->invokeAll('content_type_collections');
    $label = $content_type_collections[$collection]['label'];
    return t('Add @collection', ['@collection' => t((string)$label)]);
  }


  public function routes() {
    $routes = [];

    $content_type_collections = \Drupal::service('module_handler')->invokeAll('content_type_collections');

    foreach ($content_type_collections as $collection => $content_types) {
      $routes['admin_toolbar_content.admin_add_page.' . $collection] = new Route(
        '/admin/content/add/' . $content_types['label'],
        [
          '_title_callback' => '\Drupal\admin_toolbar_content\Controller\NodeController::addPageTitle',
          '_controller' => '\Drupal\admin_toolbar_content\Controller\NodeController::addPage',
          'collection' => $collection
        ],
        [
          '_node_add_access' => 'node',
        ],
        [
          '_node_operation_route' => TRUE
        ]
      );
    }

    return $routes;
  }
}
