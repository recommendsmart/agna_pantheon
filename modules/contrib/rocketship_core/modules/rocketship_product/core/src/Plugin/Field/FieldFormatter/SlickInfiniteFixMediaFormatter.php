<?php

namespace Drupal\rocketship_product_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\slick\Entity\Slick;
use Drupal\slick\Plugin\Field\FieldFormatter\SlickMediaFormatter;

/**
 * Plugin implementation of the 'slick media' formatter.
 *
 * @FieldFormatter(
 *   id = "slick_infinite_media",
 *   label = @Translation("Slick Media (Infinite Fix)"),
 *   description = @Translation("Display the referenced entities as a Slick
 *   carousel."), field_types = {
 *     "entity_reference",
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SlickInfiniteFixMediaFormatter extends SlickMediaFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entities = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    // Collects specific settings to this formatter.
    $settings = $this->buildSettings();
    $build = ['settings' => $settings];


    // This is our override. Clone the files until we have more files
    // than slidesToShow so infinite works. Not using infinite is also
    // broken btw. Damned either way.
    // Infinite bug: https://github.com/kenwheeler/slick/issues/1504
    // Non-Infinite bug: https://github.com/kenwheeler/slick/issues/2244
    if (!empty($build['settings']['optionset_thumbnail'])) {
      $thumb = Slick::load($build['settings']['optionset_thumbnail']);
      $options = $thumb->getOptions();
      if (count($items) > 1) {
        $slidesToShow = $options['settings']['slidesToShow'];
        if (!empty($options['responsives']['responsive'])) {
          foreach ($options['responsives']['responsive'] as &$set) {
            if ($slidesToShow < $set['settings']['slidesToShow']) {
              $slidesToShow = $set['settings']['slidesToShow'];
            }
          }
        }
        $padder = $entities;
        while (count($entities) <= $slidesToShow) {
          $entities = array_merge($entities, $padder);
        }
      }
    }

    // Modifies settings before building elements.
    $this->formatter->preBuildElements($build, $items, $entities);

    // Build the elements.
    $this->buildElements($build, $entities, $langcode);

    // Modifies settings post building elements.
    $this->formatter->postBuildElements($build, $items, $entities);

    return $this->manager()->build($build);
  }

}
