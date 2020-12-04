<?php

namespace Drupal\commerce_ticketing\Plugin\Commerce\EntityTrait;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the "purchasable_entity_ticket" trait.
 *
 * @CommerceEntityTrait(
 *   id = "purchasable_entity_ticket",
 *   label = @Translation("Is a ticket"),
 *   entity_types = {"commerce_product_variation"}
 * )
 */
class PurchasableEntityTicket extends EntityTraitBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];

    $fields['is_ticket'] = BundleFieldDefinition::create('boolean')
      ->setLabel($this->t('Is a ticket'))
      ->setDefaultValue(TRUE)
      ->setRequired(TRUE)
      ->setSetting('on_label', 'Is a ticket')
      ->setDisplayOptions(
        'form',
        [
          'type' => 'boolean_checkbox',
          'settings' => [
            'display_label' => FALSE,
          ],
          'weight' => 0,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions(
        'view',
        [
          'type' => 'boolean',
          'label' => 'above',
          'weight' => 0,
          'settings' => [
            'format' => 'enabled-disabled',
          ],
        ]
      )
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
