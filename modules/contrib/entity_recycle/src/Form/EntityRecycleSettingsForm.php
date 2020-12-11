<?php

namespace Drupal\entity_recycle\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_recycle\EntityRecycleManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Show entity recycle bin settings.
 */
class EntityRecycleSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity recycle manager service.
   *
   * @var \Drupal\entity_recycle\EntityRecycleManagerInterface
   */
  protected $entityRecycleManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entityTypeManager,
    EntityRecycleManagerInterface $entityRecycleManagerInterface) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRecycleManager = $entityRecycleManagerInterface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_recycle.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_recycle.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_recycle_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $config = $this->config('entity_recycle.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#weight' => 1,
    ];

    $form['general']['purge_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Purge Time (minutes)'),
      '#default_value' => $this->entityRecycleManager->getSetting('purge_time'),
      '#description' => $this->t("Determine how long an entity should stay in the recycle bin. It depends on the updated time of an entity, leave empty to disable this."),
    ];

    $definitions = $this->entityTypeManager->getDefinitions();
    $entity_types = [];
    $selected_entity_types = [];
    foreach ($definitions as $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        $entity_types[$definition->id()] = $definition->getLabel();
        if (!is_null($config->get('types.' . $definition->id()))) {
          $selected_entity_types[] = $definition->id();
        }
      }
    }

    $form['general']['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => 'Enable Entity Recycle Bin for',
      '#options' => $entity_types,
      '#default_value' => $selected_entity_types,
      '#attributes' => [
        'class' => ['entity-recycle-bin-entity-types'],
      ],
    ];

    foreach ($definitions as $definition) {
      if (!$definition instanceof ContentEntityTypeInterface) {
        continue;
      }

      if (!$definition->getBundleEntityType()) {
        continue;
      }

      // Entity details.
      $form['general'][$definition->id()] = [
        '#type' => 'details',
        '#title' => $definition->getLabel(),
        '#open' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="general[entity_types][' . $definition->id() . ']"]' => ['checked' => TRUE],
          ],
        ],
        '#attributes' => [
          'class' => [$definition->id()],
        ],
      ];

      // Get bundle options.
      $options = [];
      $bundles = $this->entityRecycleManager->getBundles($definition->id());
      foreach ($bundles as $bundle) {
        $options[$bundle->id()] = $bundle->label();
      }

      $form['general'][$definition->id()]['bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $definition->getBundleLabel() ?: $definition->getLabel(),
        '#description' => $this->t(
          'Select the bundles on which to enable entity recycle bin, leave empty for all.'
        ),
        '#options' => $options,
        '#default_value' => $config->get('types.' . $definition->id()) ?: [],
        '#attributes' => ['class' => ['entity-recycle-bin-entity-settings']],
      ];

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $entityRecycleSettings = $this->entityRecycleManager->getSettings();

    // Add/remove recycle bin field.
    $types = $this->updateRecycleBinField($form_state);

    // Save settings.
    foreach ($types as $entityTypeId => $bundles) {
      if (!is_array($bundles) || empty($bundles)) {
        $entityRecycleSettings->set('types.' . $entityTypeId, []);
        continue;
      }

      $entityRecycleSettings->set('types.' . $entityTypeId, $bundles);
    }

    // Set purge time.
    $purgeTime = isset($form_state->getValue('general')['purge_time']) ? $form_state->getValue('general')['purge_time'] : 0;

    $entityRecycleSettings->set('purge_time', $purgeTime);

    $entityRecycleSettings->save();

    drupal_flush_all_caches();
  }

  /**
   * Helper function to add/remove field from entity.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Returns array of submitted data.
   */
  public function updateRecycleBinField(FormStateInterface $form_state) {
    $savedTypes = $this->entityRecycleManager->getSetting('types');
    $submittedTypes = $this->getSubmittedTypes($form_state);

    // Remove any existing fields.
    foreach ($submittedTypes as $entityTypeId => $bundles) {
      if (!in_array($entityTypeId, $savedTypes) && !is_array($bundles)) {
        $this->entityRecycleManager->deleteField($entityTypeId);
        continue;
      }

      if (!in_array($entityTypeId, $savedTypes)) {
        $bundleDiff = array_diff($savedTypes[$entityTypeId], $submittedTypes[$entityTypeId]);
        if ($bundleDiff) {
          foreach ($bundleDiff as $bundle) {
            $this->entityRecycleManager->deleteField($entityTypeId, $bundle);
            continue;
          }
        }
      }
    }

    // Create new fields.
    foreach ($submittedTypes as $entityTypeId => $bundles) {
      if (!is_array($bundles)) {
        $this->entityRecycleManager->createField($entityTypeId);
        continue;
      }

      foreach ($bundles as $bundle) {
        $this->entityRecycleManager->createField($entityTypeId, $bundle);
      }
    }

    return $submittedTypes;
  }

  /**
   * Get submitted entity type with bundles.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Returns array of submitted data.
   */
  public function getSubmittedTypes(FormStateInterface $form_state) {
    // Format submitted data.
    $submittedTypes = array_filter($form_state->getValue('general')['entity_types']);
    foreach ($submittedTypes as $entityTypeId) {
      if (!isset($form_state->getValue('general')[$entityTypeId])) {
        continue;
      }

      $bundles = array_filter($form_state->getValue('general')[$entityTypeId]['bundles']);
      $submittedTypes[$entityTypeId] = $bundles;
    }

    return $submittedTypes;
  }

}
