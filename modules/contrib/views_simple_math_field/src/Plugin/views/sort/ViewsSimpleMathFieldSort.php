<?php

namespace Drupal\views_simple_math_field\Plugin\views\sort;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views\ResultRow;

/**
 * Handler which sort by the similarity.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("views_simple_math_field_sort")
 */
class ViewsSimpleMathFieldSort extends SortPluginBase {

  /**
   * Define default sorting order.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    return $options;
  }

  public function postExecute(&$values) {
    // @todo: make this work for more than one simple math field at a time.
    $view = $this->view;
    $order = $this->options['order'];
    foreach ($this->view->result as $result) {
      $sm_field = $view->field['field_views_simple_math_field']->getValue($result);
      $this->view->result[$result->index]->field_views_simple_math_field = $sm_field;
    }
    if ($order === 'ASC') {
      usort($this->view->result, function ($item1, $item2) {
        return $item1->field_views_simple_math_field <=> $item2->field_views_simple_math_field;
      });
    }
    else {
      usort($this->view->result, function ($item1, $item2) {
        return $item2->field_views_simple_math_field <=> $item1->field_views_simple_math_field;
      });
    }

  }

  /**
   * Add orderBy.
   */
  public function query() {}

}