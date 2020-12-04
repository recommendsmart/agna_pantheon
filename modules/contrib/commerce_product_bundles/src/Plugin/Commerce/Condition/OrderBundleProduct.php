<?php

namespace Drupal\commerce_product_bundles\Plugin\Commerce\Condition;

use Drupal\commerce\EntityUuidMapperInterface;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the product bundle condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_bundle_product",
 *   label = @Translation("Product Bundle"),
 *   display_label = @Translation("Order contains specific product bundles"),
 *   category = @Translation("Commerce Product Bundles"),
 *   entity_type = "commerce_order",
 *   weight = -1,
 * )
 */
class OrderBundleProduct extends ConditionBase implements ContainerFactoryPluginInterface {

  use ProductBundleTrait;

  /**
   * OrderBundleProduct constructor.
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
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $product_ids = $this->getProductBundleIds();
    foreach ($order->getItems() as $order_item) {
      /** @var \Drupal\commerce_product_bundles\Entity\ProductBundleVariationInterface $purchased_entity */
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity || $purchased_entity->getEntityTypeId() != 'commerce_bundle_variation') {
        continue;
      }
      if (in_array($purchased_entity->getProductVariationsIds(), $product_ids)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
