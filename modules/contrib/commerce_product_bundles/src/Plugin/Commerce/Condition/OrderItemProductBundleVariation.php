<?php

namespace Drupal\commerce_product_bundles\Plugin\Commerce\Condition;

use Drupal\commerce\EntityUuidMapperInterface;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the product bundle condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_product_bundle_variation",
 *   label = @Translation("Product Bundle Variation"),
 *   display_label = @Translation("Specific product bundle variation"),
 *   category = @Translation("Commerce Product Bundles"),
 *   entity_type = "commerce_order_item",
 *   weight = -1,
 * )
 */
class OrderItemProductBundleVariation extends ConditionBase implements ContainerFactoryPluginInterface {

  use ProductBundleVariationTrait;

  /**
   * OrderItemProductBundleVariation constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\commerce\EntityUuidMapperInterface $entity_uuid_mapper
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityUuidMapperInterface $entity_uuid_mapper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->productBundleStorage = $entity_type_manager->getStorage('commerce_product_bundles');
    $this->entityUuidMapper = $entity_uuid_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('commerce.entity_uuid_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $purchased_entity */
    $purchased_entity = $order_item->getPurchasedEntity();
    if (!$purchased_entity || $purchased_entity->getEntityTypeId() !== 'commerce_bundle_variation') {
      return FALSE;
    }
    $product_ids = array_flip($this->getProductBundleVariationIds());

    if ($this->configuration['negate']) {
      return !isset($product_ids[$purchased_entity->id()]);
    }

    return isset($product_ids[$purchased_entity->id()]);
  }

}
