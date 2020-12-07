<?php

namespace Drupal\template_entities\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to create an entity from a template.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_link_new_from_template")
 */
class EntityLinkNewFromTemplate extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'new-from-template';
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $this->options['alter']['query'] = $this->getDestinationArray();
    return parent::renderLink($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('New');
  }

}
