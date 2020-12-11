<?php

namespace Drupal\entity_recycle\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_recycle\EntityRecycleManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a form for restoring entity recycle bin items.
 */
class RestoreEntityForm extends ConfirmFormBase {

  /**
   * Entity recycle manager service.
   *
   * @var \Drupal\entity_recycle\EntityRecycleManagerInterface
   */
  protected $entityRecycleManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Entity id.
   *
   * @var int
   */
  protected $id;

  /**
   * Entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $entity;

  /**
   * Constructs a new RestoreEntityForm.
   */
  public function __construct(EntityRecycleManagerInterface $entityRecycleManagerInterface, EntityTypeManagerInterface $entityTypeManager) {
    $this->entityRecycleManager = $entityRecycleManagerInterface;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityType = $this->getRouteMatch()->getParameter('entity_type');
    $this->id = $this->getRouteMatch()->getParameter('id');
    $this->entity = $this->entityTypeManager->getStorage($this->entityType)->load($this->id);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_recycle.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_recycle_restore_entity_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to restore @entity-type %label?', [
      '@entity-type' => $this->entity->bundle(),
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.' . $this->entityType . '.canonical', [$this->entityType => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Restore');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return 'An item will be removed from the recycle bin and all previous access settings will be applied.';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entityRecycleManager->removeItem($this->entity);

    if ($entity) {
      $this->messenger()->addMessage(
        $this->t(
          'The @entity %label has been restored successfully.', [
            '@entity' => $this->entity->bundle(),
            '%label' => $this->entity->label(),
          ]
        )
      );
    }

    $form_state->setRedirect(
      'entity.' . $this->entityType . '.canonical',
      [$this->entityType => $this->entity->id()]
    );
  }

}
