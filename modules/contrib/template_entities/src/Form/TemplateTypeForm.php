<?php

namespace Drupal\template_entities\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\template_entities\Plugin\TemplatePluginInterface;
use Drupal\template_entities\Plugin\TemplatePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TemplateTypeForm.
 */
class TemplateTypeForm extends EntityForm {

  /**
   * The template plugin manager.
   *
   * @var \Drupal\template_entities\plugin\TemplatePluginManager
   */
  protected $manager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\template_entities\Entity\TemplateTypeInterface
   */
  protected $entity;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * TemplateTypeForm constructor.
   *
   * @param \Drupal\template_entities\Plugin\TemplatePluginManager $manager
   *   The template plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   */
  public function __construct(TemplatePluginManager $manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, PluginFormFactoryInterface $plugin_form_manager) {
    $this->manager = $manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->pluginFormFactory = $plugin_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.template_plugin'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $template_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $template_type->label(),
      '#description' => $this->t("Label for the Template type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $template_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\template_entities\Entity\TemplateType::load',
      ],
      '#disabled' => !$template_type->isNew(),
    ];

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $template_type->getDescription(),
      '#description' => t('This text will be displayed on the <em>Add template</em> page.'),
    ];

    $options = [];
    foreach ($this->manager->getDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];
    }

    // Type is not set when the entity is initially created.
    /** @var \Drupal\template_entities\Plugin\TemplatePluginInterface $type_plugin */
    $type_plugin = $this->entity->get('type') ? $this->entity->getTemplatePlugin() : NULL;

    if (!$this->entity->isNew()) {
      $type_description = $this->t('<em>The template type type cannot be changed after the template type is created.</em>');
    }
    else {
      $type_description = $this->t('Template type type that is responsible for additional logic related to this template type.');
    }

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type/plugin'),
      '#description' => $type_description,
      '#default_value' => $this->entity->get('type'),
      '#disabled' => !$this->entity->isNew(),
      '#options' => $options,
      '#required' => TRUE,
      '#limit_validation_errors' => [['type']],
      '#submit' => ['::submitSelectType'],
      '#executes_submit_callback' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxReplaceTypeOptionsForm',
        'wrapper' => 'type-options',
        'method' => 'replace',
      ],
    ];

    $form['type_options_placeholder'] = [
      '#markup' => '<div id="type-options"></div>',
    ];

    if ($type_plugin) {
      // Expose bundle conditions.
      $form['type_options_container'] = [
        '#type' => 'fieldset',
        '#title' => 'Entity type/plugin options',
        '#prefix' => '<div id="type-options">',
        '#suffix' => '</div>',
      ];

      if ($entity_type = $type_plugin->getEntityType()) {

        if ($entity_type->hasKey('bundle') && $bundles = $type_plugin->getBundleOptions()) {
          $bundle_options = [];
          foreach ($bundles as $bundle_id => $bundle_info) {
            $bundle_options[$bundle_id] = $bundle_info['label'];
          }

          $form['type_options_container']['bundles'] = [
            '#title' => $entity_type->getBundleLabel(),
            '#type' => 'checkboxes',
            '#options' => $bundle_options,
            '#default_value' => $this->entity->get('bundles'),
            '#description' => $this->t('Check which bundles this template configuration type should apply to.'),
          ];
        }
      }

      if ($plugin_form = $this->getPluginForm($type_plugin)) {
        $form['type_options_container']['settings'] = [
          '#tree' => TRUE,
        ];
        $subform_state = $this->getSettingsSubFormState($form, $form_state);
        $form['type_options_container']['settings'] = $plugin_form->buildConfigurationForm($form['type_options_container']['settings'], $subform_state);
      }

      $form['type_options_container']['bundles_options_container'] = [
        '#type' => 'container',
      ];

    }

    $form['ux_options'] = [
      '#type' => 'fieldset',
      '#title' => t('UX Options'),
    ];

    $form['ux_options']['add_action_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add action link to collection pages'),
      '#default_value' => $this->entity->get('add_action_link'),
      '#description' => $this->t("Check to have an action link added to the collection pages."),
    ];

    $form['ux_options']['collection_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Override collection pages'),
      '#default_value' => $this->entity->get('collection_pages'),
      '#description' => $this->t("Add action links will be displayed on default collection pages. Override which pages will have add action links added by entering one path per line. An example path is /admin/content. Typically use this when using a view to list all nodes of a particular type - e.g. \"Pages\"."),
    ];

    $form['ux_options']['listing_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional template listing pages'),
      '#default_value' => $this->entity->get('listing_pages'),
      '#description' => $this->t("An \"Add TYPE template\" action link will be added to each path listed. Enter one path per line."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $template_type = $this->entity;
    $status = $template_type->save();


    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addMessage($this->t('Created the %label Template type.', [
            '%label' => $template_type->label(),
          ]));
        break;

      default:
        $this->messenger()
          ->addMessage($this->t('Saved the %label Template type.', [
            '%label' => $template_type->label(),
          ]));
    }
    $form_state->setRedirectUrl($template_type->toUrl('collection'));
  }

  /**
   * Handles switching the type selector.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function ajaxReplaceTypeOptionsForm($form, FormStateInterface $form_state) {
    return $form['type_options_container'];
  }

  /**
   * Handles submit call when alias type is selected.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitSelectType(array $form, FormStateInterface $form_state) {
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state->setRebuild();
  }

  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);

    // Set bundles to NULL if none selected - @see DefaultSelection.
    $bundles = array_filter((array) $form_state->getValue('bundles'));
    if (empty($bundles)) {
      $entity->set('bundles', []);
    }
    return $entity;
  }

  /**
   * Retrieves the plugin form for a given block and operation.
   *
   * @param \Drupal\template_entities\Plugin\TemplatePluginInterface $template_plugin
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface|false
   *   The plugin form for the block.
   */
  protected function getPluginForm(TemplatePluginInterface $template_plugin) {
    if ($template_plugin && $template_plugin instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($template_plugin, 'configure');
    }
    return $template_plugin;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    if (isset($form['type_options_container']['settings'])) {
      // Call the plugin submit handler.
      $type_plugin = $this->entity->getTemplatePlugin();
      if ($plugin_form = $this->getPluginForm($type_plugin)) {
        $plugin_form->submitConfigurationForm($form['type_options_container']['settings'], $this->getSettingsSubFormState($form, $form_state));
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (isset($form['type_options_container']['settings'])) {
      $type_plugin = $this->entity->getTemplatePlugin();
      if ($type_plugin && $plugin_form = $this->getPluginForm($type_plugin)) {
        $plugin_form->validateConfigurationForm($form['type_options_container']['settings'], $this->getSettingsSubFormState($form, $form_state));
      }
    }
  }

  /**
   * Gets subform state for the template plugin subform.
   *
   * @param array $form
   *   Full form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Parent form state.
   *
   * @return \Drupal\Core\Form\SubformStateInterface
   *   Sub-form state for the template plugin configuration form.
   */
  protected function getSettingsSubFormState(array $form, FormStateInterface $form_state) {
    if (isset($form['type_options_container']['settings'])) {
      return SubformState::createForSubform($form['type_options_container']['settings'], $form, $form_state)
        ->set('operation', $this->operation)
        ->set('type', $this->entity);
    }
  }

}
