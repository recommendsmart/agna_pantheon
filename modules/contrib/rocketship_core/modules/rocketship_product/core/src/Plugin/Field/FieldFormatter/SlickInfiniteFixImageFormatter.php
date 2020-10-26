<?php

namespace Drupal\rocketship_product_core\Plugin\Field\FieldFormatter;

use Drupal\slick\Plugin\Field\FieldFormatter\SlickImageFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\slick\Entity\Slick;

/**
 * Plugin implementation of the 'Slick Image' formatter.
 *
 * @FieldFormatter(
 *   id = "slick_infinite_image",
 *   label = @Translation("Slick Image (Infinite Fix)"),
 *   description = @Translation("Display the images as a Slick carousel."),
 *   field_types = {"image"},
 *   quickedit = {"editor" = "disabled"}
 * )
 */
class SlickInfiniteFixImageFormatter extends SlickImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return [];
    }

    // Collects specific settings to this formatter.
    $build = ['settings' => $this->buildSettings()];

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
        $padder = $files;
        while (count($files) <= $slidesToShow) {
          $files = array_merge($files, $padder);
        }
      }
    }

    $this->formatter->buildSettings($build, $items);

    // Build the elements.
    $this->buildElements($build, $files);

    // Supports Blazy multi-breakpoint images if provided.
    $this->formatter->isBlazy($build['settings'], $build['items'][0]);

    $return = $this->manager()->build($build);

    return $return;
  }

}
