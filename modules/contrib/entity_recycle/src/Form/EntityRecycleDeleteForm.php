<?php

namespace Drupal\entity_recycle\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_recycle\EntityRecycleManager;
use Drupal\entity_recycle\EntityRecycleManagerInterface;
use Drupal\entity_recycle\EntityRecycleViewManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a form for moving/deleting items.
 */
class EntityRecycleDeleteForm extends FormBase {

  /**
   * Entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Entity Recycle manager service.
   *
   * @var \Drupal\entity_recycle\EntityRecycleManagerInterface
   */
  protected $entityRecycleManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityFormBuilderInterface $entityFormBuilder,
    EntityRecycleManagerInterface $entityRecycleManagerInterface) {
    $this->entityFormBuilder = $entityFormBuilder;
    $this->entityRecycleManager = $entityRecycleManagerInterface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity.form_builder'),
      $container->get('entity_recycle.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getEntityTypeId() . '_entity_recycle_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $entity = $this->getEntity();
    if (!$entity) {
      return $form;
    }

    // Get entity delete form.
    $form = $this->entityFormBuilder->getForm($entity, 'delete');
    $recycleBinField = $entity->get(EntityRecycleManager::RECYCLE_BIN_FIELD)->value;

    // Alter form titles.
    if ($recycleBinField) {
      $deletePermission = $this->currentUser()
        ->hasPermission(EntityRecycleViewManager::RECYCLE_BIN_DELETE_PERMISSION);

      if (!$deletePermission) {
        throw new AccessDeniedHttpException();
      }

      $form['#title'] = $this->t(
        "Are you sure you want to permanently delete the @entity-type %label?", [
          '@entity-type' => $entity->bundle(),
          '%label' => $entity->label(),
        ]
      );
    }
    else {
      $form['#title'] = $this->t(
        "Are you sure you want move @entity-type %label to recycle bin?", [
          '@entity-type' => $entity->bundle(),
          '%label' => $entity->label(),
        ]
      );

      // Alter submit text.
      $form['actions']['submit']['#value'] = $this->t('Move');
    }

    // Loop through all submit handler and remove the original entity
    // delete handler.
    foreach ($form['actions']['submit']['#submit'] as $key => $handler) {
      if (!is_array($handler)) {
        continue;
      }

      foreach ($handler as $type) {
        if ($type instanceof ContentEntityDeleteForm) {
          unset($form['actions']['submit']['#submit'][$key]);
        }
      }
    }

    // Make sure our submit runs first.
    array_unshift($form['actions']['submit']['#submit'], [
      $this,
      'submitForm',
    ]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();

    $inRecycleBin = $this->entityRecycleManager->inRecycleBin(
      $entity,
      $entity->bundle(),
    );

    // If already in recycle bin,
    // delete in permanently.
    if ($inRecycleBin) {
      $entity->delete();

      $this->messenger()->addMessage($this->t(
        'The @entity %label has been deleted successfully.', [
          '@entity' => $entity->bundle(),
          '%label' => $entity->label(),
        ]
      ));

      return;
    }

    // Add item to recycle bin.
    $item = $this->entityRecycleManager->addItem(
      $entity,
    );

    $this->messenger()->addMessage($this->t(
      'The @entity %label has been moved to the recycle bin.', [
        '@entity' => $item->bundle(),
        '%label' => $item->label(),
      ]
    ));
  }

  /**
   * Get the entity type from route.
   */
  public function getEntityTypeId() {
    $entityType = $this->getRouteMatch()
      ->getRouteObject()->getOption('entity_type');

    if (!$entityType) {
      return NULL;
    }

    return $entityType;
  }

  /**
   * Get entity from route.
   */
  public function getEntity() {
    $entityType = $this->getEntityTypeId();
    if (!$entityType) {
      return NULL;
    }

    $entity = $this->getRouteMatch()->getParameter($entityType);
    if (!$entity) {
      return NULL;
    }

    return $entity;
  }

  /**
   * Get operation.
   */
  public function getOperation() {
    return 'delete';
  }

}
