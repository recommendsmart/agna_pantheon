<?php

namespace Drupal\arch_product\Plugin\views\filter;

use Drupal\user\Plugin\views\filter\Name;

/**
 * Filter handler to check for revisions a certain user has created.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("product_uid_revision")
 */
class UidRevision extends Name {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    $placeholder = $this->placeholder() . '[]';

    $args = array_values($this->value);

    $this->query->addWhereExpression(
      $this->options['group'],
      "{$this->tableAlias}.uid IN({$placeholder})
      OR (
        (
          SELECT COUNT(DISTINCT vid)
          FROM {arch_product_revision} nr
          WHERE
            nr.revision_uid IN ({$placeholder})
            AND nr.pid = {$this->tableAlias}.pid
        ) > 0
      )",
      [$placeholder => $args],
      $args
    );
  }

}
