<?php

namespace Drupal\template_entities\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityDeleteFormTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a template entity type.
 *
 * @ingroup template_entities
 */
class TemplateTypeDeleteForm extends EntityConfirmFormBase {

  use EntityDeleteFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_template = $this->entityTypeManager->getStorage('template')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($num_template) {
      $caption = '<p>' . $this->formatPlural($num_template, '%type is used by 1 template on your site. You can not remove this template type until you have removed all of the %type templates.', '%type is used by @count templates on your site. You may not remove %type until you have removed all of the %type templates.', ['%type' => $this->entity->label()]) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
