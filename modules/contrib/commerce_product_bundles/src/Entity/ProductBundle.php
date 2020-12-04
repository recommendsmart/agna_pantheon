<?php

namespace Drupal\commerce_product_bundles\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce\EntityOwnerTrait;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Product Bundle entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_product_bundles",
 *   label = @Translation("Product Bundle"),
 *   label_collection = @Translation("Products Bundle"),
 *   label_singular = @Translation("product bundle"),
 *   label_plural = @Translation("products bundles"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product bundle",
 *     plural = "@count products bundles",
 *   ),
 *   bundle_label = @Translation("Product bundle type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_product_bundles\Event\ProductBundleEvent",
 *     "storage" = "Drupal\commerce\CommerceContentEntityStorage",
 *     "access" = "Drupal\commerce_product_bundles\Access\ProductBundleAccessControlHandler",
 *     "query_access" = "Drupal\entity\QueryAccess\QueryAccessHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "view_builder" = "Drupal\commerce_product_bundles\ProductBundleViewBuilder",
 *     "list_builder" = "Drupal\commerce_product_bundles\ProductBundleListBuilder",
 *     "views_data" = "Drupal\commerce_product_bundles\CommerceBundleEntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_product_bundles\Form\ProductBundleForm",
 *       "add" = "Drupal\commerce_product_bundles\Form\ProductBundleForm",
 *       "edit" = "Drupal\commerce_product_bundles\Form\ProductBundleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\entity\Routing\AdminHtmlRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *     "translation" = "Drupal\commerce_product_bundles\ProductBundleTranslationHandler"
 *   },
 *   admin_permission = "administer commerce_product_bundles",
 *   permission_granularity = "bundle",
 *   translatable = TRUE,
 *   base_table = "commerce_product_bundles",
 *   data_table = "commerce_product_bundles_field_data",
 *   entity_keys = {
 *     "id" = "product_bundle_id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *     "owner" = "uid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/product-bundles/{commerce_product_bundles}",
 *     "add-page" = "/product-bundles/add",
 *     "add-form" = "/product-bundles/add/{commerce_product_bundles_type}",
 *     "edit-form" = "/product-bundles/{commerce_product_bundles}/edit",
 *     "delete-form" = "/product-bundles/{commerce_product_bundles}/delete",
 *     "delete-multiple-form" = "/admin/commerce/products-bundles/delete",
 *     "collection" = "/admin/commerce/product-bundles"
 *   },
 *   bundle_entity_type = "commerce_product_bundles_type",
 *   field_ui_base_route = "entity.commerce_product_bundles_type.edit_form",
 * )
 */
class ProductBundle extends CommerceContentEntityBase implements ProductBundleInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use EntityPublishedTrait;

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
    return $this->getTranslatedReferencedEntities('stores');
  }

  /**
   * {@inheritdoc}
   */
  public function setStores(array $stores) {
    $this->set('stores', $stores);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreIds() {
    $store_ids = [];
    foreach ($this->get('stores') as $store_item) {
      $store_ids[] = $store_item->target_id;
    }
    return $store_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreIds(array $store_ids) {
    $this->set('stores', $store_ids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariationIds() {
    $variation_ids = [];
    foreach ($this->get('bundle_variations') as $field_item) {
      $variation_ids[] = $field_item->target_id;
    }
    return $variation_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariations() {
    return $this->getTranslatedReferencedEntities('bundle_variations');
  }

  /**
   * {@inheritdoc}
   */
  public function setVariations(array $bundle_variations) {
    $this->set('bundle_variations', $bundle_variations);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasVariations() {
    return !$this->get('bundle_variations')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function addVariation(ProductBundleVariationInterface $bundle_variation) {
    if (!$this->hasVariation($bundle_variation)) {
      $this->get('bundle_variations')->appendItem($bundle_variation);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeVariation(ProductBundleVariationInterface $bundle_variation) {
    $index = $this->getBundleVariationIndex($bundle_variation);
    if ($index !== FALSE) {
      $this->get('bundle_variations')->offsetUnset($index);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasVariation(ProductBundleVariationInterface $bundle_variation) {
    return in_array($bundle_variation->id(), $this->getVariationIds());
  }

  /**
   * Gets the index of the given variation.
   *
   * @param \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $bundle_variation
   *   The variation.
   *
   * @return int|bool
   *   The index of the given variation, or FALSE if not found.
   */
  protected function getBundleVariationIndex(ProductBundleVariationInterface $bundle_variation) {
    return array_search($bundle_variation->id(), $this->getVariationIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultVariation() {
    foreach ($this->getVariations() as $bundle_variation) {
      // Return the first active variation.
      if ($bundle_variation->isPublished() && $bundle_variation->access('view')) {
        return $bundle_variation;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // Set the owner ID to 0 if the translation owner is anonymous.
      if ($translation->getOwner()->isAnonymous()) {
        $translation->setOwnerId(0);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Ensure there's a back-reference on each product bundle variation.
    foreach ($this->bundle_variations as $item) {
      $bundle_variation = $item->entity;
      if ($bundle_variation->product_bundle_id->isEmpty()) {
        $bundle_variation->product_bundle_id = $this->id();
        $bundle_variation->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.query_args:v']);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete the product bundle variations of a deleted product.
    $bundle_variations = [];
    foreach ($entities as $entity) {
      if (empty($entity->bundle_variations)) {
        continue;
      }
      foreach ($entity->bundle_variations as $item) {
        $bundle_variations[$item->target_id] = $item->entity;
      }
    }
    $variation_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_bundle_variation');
    $variation_storage->delete($bundle_variations);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    // Add owner base fields.
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['stores'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Stores'))
      ->setDescription(t('The product stores.'))
      ->setRequired(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bundle_variations'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Bundle variations')
      ->setDescription(t('The product bundle variations.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_bundle_variation')
      ->setSetting('handler', 'commerce_bundle_variation')
      ->setDisplayOptions('view', [
        'type' => 'commerce_bundle_add_to_cart',
        'combine' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid']
      ->setLabel(t('Author'))
      ->setDescription(t('The product author.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The product title.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('text_with_summary')
      ->setLabel(t('Body'))
      ->setTranslatable(TRUE)
      ->setSetting('display_summary', FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea_with_summary',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['path'] = BaseFieldDefinition::create('path')
      ->setLabel(t('URL alias'))
      ->setDescription(t('The product URL alias.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'path',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setComputed(TRUE);

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
      ->setDescription(t('The time when the product was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the product was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

}
