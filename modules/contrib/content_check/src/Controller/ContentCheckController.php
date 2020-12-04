<?php

namespace Drupal\content_check\Controller;

use Drupal\content_check\ContentChecker;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for entity check controllers.
 */
class ContentCheckController extends ControllerBase {

  /**
   * The content checking service.
   *
   * @var \Drupal\content_check\ContentChecker
   */
  protected $contentChecker;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContentChecker $content_checker) {
    $this->contentChecker = $content_checker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_check.checker')
    );
  }

  /**
   * Render the output of the checks applicable to this entity.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity we're checking.
   *
   * @return array
   *   The render array containing the output for the page.
   */
  public function overview($node) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    //$entity = $route_match->getParameter($entity_type_id);
    $results = $this->contentChecker->checkEntity($node);

    return [
      '#type' => 'content_check_report_page',
      '#requirements' => $results,
    ];
  }

}
