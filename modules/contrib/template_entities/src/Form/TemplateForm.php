<?php

namespace Drupal\template_entities\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Template edit forms.
 *
 * @ingroup template_entities
 */
class TemplateForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addMessage($this->t('Created the %label Template.', [
            '%label' => $entity->label(),
          ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Template.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.template.canonical', ['template' => $entity->id()]);
  }

}
