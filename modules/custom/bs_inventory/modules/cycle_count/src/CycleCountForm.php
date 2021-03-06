<?php

namespace Drupal\cycle_count;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the transaction edit forms.
 */
class CycleCountForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $insert = $entity->isNew();
    $entity->save();
    $entity_link = $entity->link($this->t('View'));
    $context = ['%title' => $entity->label(), 'link' => $entity_link];
    $t_args = ['%title' => $entity->link($entity->label())];

    if ($insert) {
      $this->logger('cycle_count')->notice('CycleCount: added %title.', $context);
      drupal_set_message($this->t('CycleCount %title has been created.', $t_args));
    }
    else {
      $this->logger('cycle_count')->notice('CycleCount: updated %title.', $context);
      drupal_set_message($this->t('CycleCount %title has been updated.', $t_args));
    }
  }

}
