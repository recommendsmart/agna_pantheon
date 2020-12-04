<?php

namespace Drupal\commerce_ticketing\Form;

use Drupal\commerce\EntityHelper;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\state_machine\WorkflowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for commerce ticket type forms.
 */
class CommerceTicketTypeForm extends BundleEntityFormBase {

  /**
   * The workflow manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Constructs a new OrderTypeForm object.
   *
   * @param \Drupal\commerce\EntityTraitManagerInterface $trait_manager
   *   The entity trait manager.
   * @param \Drupal\state_machine\WorkflowManagerInterface $workflow_manager
   *   The workflow manager.
   */
  public function __construct(WorkflowManagerInterface $workflow_manager) {
    $this->workflowManager = $workflow_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workflow')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $number_pattern_storage = $this->entityTypeManager->getStorage('commerce_number_pattern');
    $number_patterns = $number_pattern_storage->loadByProperties(['targetEntityType' => 'commerce_ticket']);
    $workflows = $this->workflowManager->getGroupedLabels('commerce_ticket');

    $entity_type = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add commerce ticket type');
    }
    else {
      $form['#title'] = $this->t(
        'Edit %label commerce ticket type',
        ['%label' => $entity_type->label()]
      );
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $entity_type->label(),
      '#description' => $this->t('The human-readable name of this commerce ticket type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\commerce_ticketing\Entity\CommerceTicketType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this commerce ticket type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['numberPattern'] = [
      '#type' => 'select',
      '#title' => $this->t('Number pattern'),
      '#default_value' => $entity_type->getNumberPatternId(),
      '#options' => EntityHelper::extractLabels($number_patterns),
      '#states' => [
        'visible' => [
          ':input[name="generate_number"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#options' => $workflows,
      '#default_value' => $entity_type->getWorkflowId(),
      '#description' => $this->t('Used by all orders of this type.'),
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save commerce ticket type');
    $actions['delete']['#value'] = $this->t('Delete commerce ticket type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entity;

    $entity_type->set('id', trim($entity_type->id()));
    $entity_type->set('label', trim($entity_type->label()));

    $status = $entity_type->save();

    $t_args = ['%name' => $entity_type->label()];
    if ($status == SAVED_UPDATED) {
      $message = $this->t('The commerce ticket type %name has been updated.', $t_args);
    }
    elseif ($status == SAVED_NEW) {
      $message = $this->t('The commerce ticket type %name has been added.', $t_args);
    }
    $this->messenger()->addStatus($message);

    $form_state->setRedirectUrl($entity_type->toUrl('collection'));
  }

}
