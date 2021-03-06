<?php

namespace Drupal\burndown\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Project edit forms.
 *
 * @ingroup burndown
 */
class ProjectForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\burndown\Entity\Project $entity */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'hidden',
        '#title' => $this->t('Create new revision'),
        '#default_value' => TRUE,
        '#weight' => 10,
      ];
    }

    // Hide miscellaneous items.
    $form['user_id']['#access'] = FALSE;
    $form['revision_log']['#access'] = FALSE;
    $form['ticket_id']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Project.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Project.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.burndown_project.canonical', ['burndown_project' => $entity->id()]);
  }

}
