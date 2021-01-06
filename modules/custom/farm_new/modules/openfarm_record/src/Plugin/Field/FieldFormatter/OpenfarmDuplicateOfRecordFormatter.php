<?php

namespace Drupal\openfarm_record\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'openfarm_duplicate_of_record' formatter.
 *
 * @FieldFormatter(
 *   id = "openfarm_duplicate_of_record",
 *   label = @Translation("Openfarm Duplicate Record"),
 *   description = @Translation("Display the link for a duplicate record."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class OpenfarmDuplicateOfRecordFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // @codingStandardsIgnoreLine
    if (\Drupal::routeMatch()->getRouteName() != 'layout_builder.defaults.node.view') {
      foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
        if ($entity) {
          $field_text = $this->t('This record was merged with <a href="@link">@title</a> record.', [
            '@link' => $entity->toUrl()->toString(),
            '@title' => $entity->label(),
          ]);
          // @TODO: add css class to the markup.
          $elements[$delta] = [
            '#type' => 'markup',
            '#markup' => '<div>' . $field_text . '</div>',
          ];

          $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for record content type nodes.
    $target_type = $field_definition->getFieldStorageDefinition()
      ->getSetting('target_type');
    return $target_type == 'node' && $field_definition->get('bundle') == 'record';
  }

}
