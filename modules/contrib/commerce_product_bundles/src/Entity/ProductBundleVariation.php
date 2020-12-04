<?php

namespace Drupal\commerce_product_bundles\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce\EntityOwnerTrait;
use Drupal\commerce_price\Price;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;

/**
 * Defines the product bundle variation entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_bundle_variation",
 *   label = @Translation("Product bundle variation"),
 *   label_collection = @Translation("Product bundle variations"),
 *   label_singular = @Translation("product bundle variation"),
 *   label_plural = @Translation("product bundle variations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product bundle variation",
 *     plural = "@count product bundle variations",
 *   ),
 *   bundle_label = @Translation("Product bundle variation type"),
 *   handlers = {
 *     "storage" = "Drupal\commerce_product_bundles\ProductBundleVariationStorage",
 *     "access" = "Drupal\commerce_product_bundles\Access\ProductBundleVariationAccessControlHandler",
 *     "permission_provider" = "Drupal\commerce_product_bundles\ProductBundleVariationPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_product_bundles\ProductBundleVariationListBuilder",
 *     "views_data" = "Drupal\commerce_product_bundles\CommerceBundleEntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\commerce_product_bundles\Form\ProductBundleVariationForm",
 *       "edit" = "Drupal\commerce_product_bundles\Form\ProductBundleVariationForm",
 *       "duplicate" = "Drupal\commerce_product_bundles\Form\ProductBundleVariationForm",
 *       "delete" = "Drupal\commerce_product_bundles\Form\ProductBundleVariationDeleteForm",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_product_bundles\ProductBundleVariationRouteProvider",
 *     },
 *     "inline_form" = "Drupal\commerce_product_bundles\Form\ProductBundleVariationInlineForm",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   admin_permission = "administer commerce_product_bundles",
 *   translatable = TRUE,
 *   translation = {
 *     "content_translation" = {
 *       "access_callback" = "content_translation_translate_access"
 *     },
 *   },
 *   base_table = "commerce_bundle_variation",
 *   data_table = "commerce_bundle_variation_field_data",
 *   entity_keys = {
 *     "id" = "bundle_variation_id",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "published" = "status",
 *     "owner" = "uid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "add-form" = "/product-bundles/{commerce_product_bundles}/bundle-variations/add",
 *     "edit-form" = "/product-bundles/{commerce_product_bundles}/bundle-variations/{commerce_bundle_variation}/edit",
 *     "duplicate-form" = "/product-bundles/{commerce_product_bundles}/bundle-variations/{commerce_bundle_variation}/duplicate",
 *     "delete-form" = "/product-bundles/{commerce_product_bundles}/bundle-variations/{commerce_bundle_variation}/delete",
 *     "collection" = "/product-bundles/{commerce_product_bundles}/bundle-variations",
 *     "drupal:content-translation-overview" = "/product-bundles/{commerce_product_bundles}/bundle-variations/{commerce_bundle_variation}/translations",
 *     "drupal:content-translation-add" = "/produc-bundle/{commerce_product_bundles}/bundle-variations/{commerce_bundle_variation}/translations/add/{source}/{target}",
 *     "drupal:content-translation-edit" = "/product-bundles/{commerce_product_bundles}/bundle-variations/{commerce_bundle_variation}/translations/edit/{language}",
 *     "drupal:content-translation-delete" = "/product-bundles/{commerce_product_bundles}/bundle-variations/{commerce_bundle_variation}/translations/delete/{language}",
 *   },
 *   bundle_entity_type = "commerce_bundle_variation_type",
 *   field_ui_base_route = "entity.commerce_bundle_variation_type.edit_form",
 * )
 */
class ProductBundleVariation extends CommerceContentEntityBase implements ProductBundleVariationInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['commerce_product_bundles'] = $this->getBundleProductId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    // StringFormatter assumes 'revision' is always a valid link template.
    if (in_array($rel, ['canonical', 'revision'])) {
      $route_name = 'entity.commerce_product_bundles.canonical';
      $route_parameters = [
        'commerce_product_bundles' => $this->getBundleProductId(),
      ];
      $options += [
        'query' => [
          'v' => $this->id(),
        ],
        'entity_type' => 'commerce_product_bundles',
        'entity' => $this->getBundleProduct(),
        // Display links by default based on the current language.
        'language' => $this->language(),
      ];
      return new Url($route_name, $route_parameters, $options);
    }
    else {
      return parent::toUrl($rel, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleProduct() {
    return $this->getTranslatedReferencedEntity('product_bundle_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleProductId() {
    return $this->get('product_bundle_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductVariationsIds() {
    return $this->get('product_variation_id')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getProductVariations() {
    return $this->get('product_variation_id')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setProductVariations(array $variations) {
    $this->set('product_variation_id', $variations);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    if (!$this->get('price')->isEmpty()) {
      $price = $this->get('price')->get(0)->toPrices();
      $resolved_currency = \Drupal::service('commerce_currency_resolver.current_currency')->getCurrency();

      $default_fallback = isset($price['USD']) ? $price['USD'] : new Price(0, 'USD');

      return isset($price[$resolved_currency]) ? $price[$resolved_currency] : $default_fallback;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setPrice(Price $price) {
    $this->set('price', $price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->getEntityKey('published');
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active) {
    $this->set('status', (bool) $active);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStores() {
    $product = $this->getBundleProduct();
    return $product ? $product->getStores() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTypeId() {
    // The order item type is a bundle-level setting.
    $type_storage = $this->entityTypeManager()->getStorage('commerce_bundle_variation_type');
    $type_entity = $type_storage->load($this->bundle());

    return $type_entity->getOrderItemTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderItemTitle() {
    $label = $this->label();
    if (!$label) {
      $label = $this->generateTitle();
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['store']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $tags = parent::getCacheTagsToInvalidate();
    // Invalidate the variations view builder and product caches.
    return Cache::mergeTags($tags, [
      'commerce_product_bundles:' . $this->getBundleProductId(),
      'commerce_bundle_variation_view',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $variation_bundle_type */
    $variation_bundle_type = $this->entityTypeManager()
      ->getStorage('commerce_bundle_variation_type')
      ->load($this->bundle());

    if ($variation_bundle_type->shouldGenerateTitle()) {
      $title = $this->generateTitle();
      $this->setTitle($title);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a reference on the parent product.
    $product_bundle = $this->getBundleProduct();
    if ($product_bundle && !$product_bundle->hasVariation($this)) {
      $product_bundle->addVariation($this);
      $product_bundle->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface[] $entities */
    foreach ($entities as $bundle_variation) {
      // Remove the reference from the parent product.
      $product_bundle = $bundle_variation->getBundleProduct();
      if ($product_bundle && $product_bundle->hasVariation($bundle_variation)) {
        $product_bundle->removeVariation($bundle_variation);
        $product_bundle->save();
      }
    }
  }

  /**
   * Generates the bundle variation title based.
   *
   * @return string
   *   The generated value.
   */
  protected function generateTitle() {
    if (!$this->getBundleProductId()) {
      // Title generation is not possible before the parent product is known.
      return '';
    }

    return $this->getBundleProduct()->getTitle();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    // Add owner base fields.
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['uid']->setLabel(t('Author'))
      ->setDescription(t('The bundle variation author.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['product_bundle_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product bundle'))
      ->setDescription(t('The parent product.'))
      ->setSetting('target_type', 'commerce_product_bundles')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The variation title.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('commerce_currencies_price')
      ->setLabel(t('Price'))
      ->setDescription(t('The price'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_bundle_price_calculated',
        'settings' => [
          'strip_trailing_zeroes' => TRUE,
          'currency_display' => 'symbol'
        ],
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_currencies_price_default',
        'settings' => [
          'required_prices' => TRUE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The referenced product variations ids.
    $fields['product_variation_id'] = BaseFieldDefinition::create('product_bundle_field')
      ->setLabel(t('Product variations'))
      ->setDescription(t('The products variations.'))
      ->setDisplayOptions('form', [
        'type' => 'product_bundle_field_default',
        'weight' => 80,
      ])
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status']
      ->setLabel(t('Published'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 90,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the variation was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the variation was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = [];
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationType $bundle_variation_type */
    $bundle_variation_type = ProductBundleVariationType::load($bundle);
    // $bundle_variation_type could be NULL if the method is invoked during uninstall.
    if ($bundle_variation_type && $bundle_variation_type->shouldGenerateTitle()) {
      // All bundle variations have title field.
      $fields['title'] = clone $base_field_definitions['title'];
      $fields['title']->setRequired(FALSE);
      $fields['title']->setDisplayConfigurable('form', FALSE);
    }

    return $fields;
  }

}
