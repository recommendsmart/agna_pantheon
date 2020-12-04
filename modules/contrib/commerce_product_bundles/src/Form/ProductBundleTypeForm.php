<?php

namespace Drupal\commerce_product_bundles\Form;

use Drupal\commerce\EntityHelper;
use Drupal\commerce\EntityTraitManagerInterface;
use Drupal\commerce\Form\CommerceBundleEntityFormBase;
use Drupal\commerce_order\Entity\OrderItemTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ProductBundleTypeForm
 *
 * @package Drupal\commerce_product_bundles\Form
 *
 * Code was taken from and modified:
 * @see \Drupal\commerce_product\Form\ProductTypeForm
 */
class ProductBundleTypeForm extends CommerceBundleEntityFormBase {

  use EntityDuplicateFormTrait;

  /**
   * The bundle variation type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $bundleVariationTypeStorage;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * ProductBundleTypeForm constructor.
   *
   * @param \Drupal\commerce\EntityTraitManagerInterface $trait_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTraitManagerInterface $trait_manager, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($trait_manager);

    $this->bundleVariationTypeStorage = $entity_type_manager->getStorage('commerce_bundle_variation_type');
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_entity_trait'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\commerce_product\Form\ProductTypeForm::form()
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleTypeInterface $product_bundle_type */
    $product_bundle_type = $this->entity;
    $bundle_variation_types = $this->bundleVariationTypeStorage->loadMultiple();

    // Create an empty product bundle to get the default status value.
    if (in_array($this->operation, ['add', 'duplicate'])) {
      $product_bundle = $this->entityTypeManager->getStorage('commerce_product_bundles')->create(['type' => $product_bundle_type->uuid()]);
      $bundle_products_exist = FALSE;
    }
    else {
      $storage = $this->entityTypeManager->getStorage('commerce_product_bundles');
      $product_bundle = $storage->create(['type' => $product_bundle_type->id()]);
      $bundle_products_exist = $storage->getQuery()->condition('type', $product_bundle_type->id())->execute();
    }
    $form_state->set('original_entity', $this->entity->createDuplicate());

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $product_bundle_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $product_bundle_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_product_bundles\Entity\ProductBundleType::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$product_bundle_type->isNew(),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('This text will be displayed on the <em>Add bundle product</em> page.'),
      '#default_value' => $product_bundle_type->getDescription(),
    ];
    $form['bundleVariationType'] = [
      '#type' => 'select',
      '#title' => $this->t('Product bundle variation type'),
      '#default_value' => $product_bundle_type->getBundleVariationTypeId(),
      '#options' => EntityHelper::extractLabels($bundle_variation_types),
      '#disabled' => $bundle_products_exist,
    ];

    if ($product_bundle_type->isNew()) {
      $form['bundleVariationType']['#empty_option'] = $this->t('- Create new -');
      $form['bundleVariationType']['#description'] = $this->t('If an existing product bundle variation type is not selected, a new one will be created.');
    }

    // Alwais set this to true
    $form['multipleBundleVariations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow each product bundle to have multiple variations.'),
      '#default_value' => TRUE,
      '#disabled' => TRUE
    ];
    $form['injectBundleVariationFields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inject product bundle variation fields into the rendered product.'),
      '#default_value' => $product_bundle_type->shouldInjectBundleVariationFields(),
    ];
    $form['product_bundle_status'] = [
      '#type' => 'checkbox',
      '#title' => t('Publish new products bundle of this type by default.'),
      '#default_value' => $product_bundle->isPublished(),
    ];

    $form = $this->buildTraitForm($form, $form_state);

    if ($this->moduleHandler->moduleExists('language')) {
      $form['language'] = [
        '#type' => 'details',
        '#title' => $this->t('Language settings'),
        '#group' => 'additional_settings',
      ];
      $form['language']['language_configuration'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'commerce_product_bundles',
          'bundle' => $product_bundle_type->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('commerce_product_bundles', $product_bundle_type->id()),
      ];
      $form['#submit'][] = 'language_configuration_element_submit';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\commerce_product\Form\ProductTypeForm::validateForm()
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateTraitForm($form, $form_state);

    if (empty($form_state->getValue('bundleVariationType'))) {
      $id = $form_state->getValue('id');
      if (!empty($this->entityTypeManager->getStorage('commerce_bundle_variation_type')->load($id))) {
        $form_state->setError($form['bundleVariationType'], $this->t('A product bundle variation type with the machine name @id already exists.
        Select an existing product bundle variation type or change the machine name for this product bundle type.', [
          '@id' => $id,
        ]));
      }

      if ($this->moduleHandler->moduleExists('commerce_order')) {
        $order_item_type_ids = $this->getOrderItemTypeIds();
        if (empty($order_item_type_ids)) {
          $form_state->setError($form['bundleVariationType'], $this->t('A new product bundle variation type cannot be created, because no order item types were found.
          Select an existing product bundle variation type or retry after creating a new order item type.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\commerce_product\Form\ProductTypeForm::submitForm()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleTypeInterface $bundle_variation_type */
    $product_bundle_type = $this->entity;
    // Create a new product variation type.
    if (empty($form_state->getValue('bundleVariationType'))) {
      /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationTypeInterface $bundle_variation_type */
      $bundle_variation_type = $this->entityTypeManager->getStorage('commerce_bundle_variation_type')->create([
        'id' => $form_state->getValue('id'),
        'label' => $form_state->getValue('label'),
      ]);
      if ($this->moduleHandler->moduleExists('commerce_order')) {
        $order_item_type_ids = $this->getOrderItemTypeIds();
        $order_item_type_id = isset($types['default']) ? 'default' : reset($order_item_type_ids);
        $bundle_variation_type->setOrderItemTypeId($order_item_type_id);
      }
      $bundle_variation_type->save();
      $product_bundle_type->setBundleVariationTypeId($form_state->getValue('id'));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\commerce_product\Form\ProductTypeForm::save()
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleTypeInterface $product_bundle_type */
    $product_bundle_type = $this->entity;
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleTypeInterface $original_product_bundle_type */
    $original_product_bundle_type = $form_state->get('original_entity');

    $product_bundle_type->save();
    $this->postSave($product_bundle_type, $this->operation);
    $this->submitTraitForm($form, $form_state);

    // Update the widget for the variations field.
    $form_display = commerce_get_entity_display('commerce_product_bundles', $product_bundle_type->id(), 'form');
    if ($product_bundle_type->allowsMultipleBundleVariations() && !$original_product_bundle_type->allowsMultipleBundleVariations()) {
      // When multiple bundle variations are allowed, the variations tab is used
      // to manage them, no widget is needed.
      $form_display->removeComponent('bundleVariations');
      $form_display->save();
    }
    // Update the default value of the status field.
    $product_bundle_type_id = $product_bundle_type->id();
    $product_bundle = $this->entityTypeManager->getStorage('commerce_product_bundles')->create(['type' => $product_bundle_type_id]);
    $value = (bool) $form_state->getValue('product_bundle_status');
    if ($product_bundle->status->value != $value) {
      $fields = $this->entityFieldManager->getFieldDefinitions('commerce_product_bundles', $product_bundle_type_id);
      $fields['status']->getConfig($product_bundle_type_id)->setDefaultValue($value)->save();
      $this->entityFieldManager->clearCachedFieldDefinitions();
    }

    $this->messenger()->addMessage($this->t('The product bundle type %label has been successfully saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.commerce_product_bundles_type.collection');
  }

  /**
   * Gets the available order item type IDs.
   *
   * Only order item types that can be used to purchase product bundle variations
   * are included.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see \Drupal\commerce_product\Form\ProductTypeForm::save()
   */
  protected function getOrderItemTypeIds() {
    $order_item_type_storage = $this->entityTypeManager->getStorage('commerce_order_item_type');
    $order_item_types = $order_item_type_storage->loadMultiple();
    $order_item_types = array_filter($order_item_types, function (OrderItemTypeInterface $type) {
      return $type->getPurchasableEntityTypeId() == 'commerce_bundle_variation';
    });

    return array_keys($order_item_types);
  }

}
