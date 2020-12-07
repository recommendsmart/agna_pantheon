<?php

namespace Drupal\template_entities\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for template entities.
 */
class TemplateViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  protected function addEntityLinks(array &$data) {
    parent::addEntityLinks($data);

    $entity_type_id = $this->entityType->id();
    $t_arguments = ['@entity_type_label' => $this->entityType->getLabel()];
    if ($this->entityType->hasLinkTemplate('new-from-template')) {
      $data['new_' . $entity_type_id] = [
        'field' => [
          'title' => $this->t('Link to create from template', $t_arguments),
          'help' => $this->t('Provide a new link to the template.', $t_arguments),
          'id' => 'entity_link_new_from_template',
        ],
      ];
    }
  }

}
