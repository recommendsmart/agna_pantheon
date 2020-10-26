<?php

namespace Drupal\rocketship_blog_advanced\Plugin\facets\widget;

use Drupal\facets\Plugin\facets\widget\LinksWidget;
use Drupal\facets\Result\ResultInterface;

/**
 * The links widget. Just a clone, but here we can use our own theme function.
 *
 * @FacetsWidget(
 *   id = "blog_links",
 *   label = @Translation("List of links for the Blog feature"),
 *   description = @Translation("A simple widget that shows a list of links"),
 * )
 */
class BlogLinksWidget extends LinksWidget {

  /**
   * Builds a facet result item.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   The result item.
   *
   * @return array
   *   The facet result item as a render array.
   */
  protected function buildResultItem(ResultInterface $result) {
    $return = parent::buildResultItem($result);
    $return['#theme'] = 'blog_facets_result_item';
    return $return;
  }

}
