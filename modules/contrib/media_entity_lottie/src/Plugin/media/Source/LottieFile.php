<?php

namespace Drupal\media_entity_lottie\Plugin\media\Source;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\Plugin\media\Source\File;

/**
 * Media source wrapping around a lottie file.
 *
 * @see \Drupal\file\FileInterface
 *
 * @MediaSource(
 *   id = "lottie_file",
 *   label = @Translation("Lottie file"),
 *   description = @Translation("Use lottie files for reusable media."),
 *   allowed_field_types = {"file"},
 *   default_thumbnail_filename = "lottie.png"
 * )
 */
class LottieFile extends File implements MediaSourceFieldConstraintsInterface {

  /**
   * Key for "lottie width" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_WIDTH = 'width';

  /**
   * Key for "lottie height" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_HEIGHT = 'height';

  /**
   * Key for "lottie version" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_VERSION = 'version';

  /**
   * Key for "lottie frames" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_FRAMES = 'frames';

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $attributes = parent::getMetadataAttributes();

    $attributes += [
      static::METADATA_ATTRIBUTE_WIDTH => $this->t('Width'),
      static::METADATA_ATTRIBUTE_HEIGHT => $this->t('Height'),
      static::METADATA_ATTRIBUTE_NAME => $this->t('Name'),
      static::METADATA_ATTRIBUTE_VERSION => $this->t('Version'),
      static::METADATA_ATTRIBUTE_FRAMES => $this->t('Frames'),
    ];

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get($this->configuration['source_field'])->entity;
    // If the source field is not required, it may be empty.
    if (!$file) {
      return parent::getMetadata($media, $attribute_name);
    }

    $content = file_get_contents($file->getFileUri());
    $data = Json::decode($content);
    switch ($attribute_name) {
      case self::METADATA_ATTRIBUTE_WIDTH:
        return $data['w'] ?? NULL;

      case self::METADATA_ATTRIBUTE_HEIGHT:
        return $data['h'] ?? NULL;

      case self::METADATA_ATTRIBUTE_NAME:
        return $data['nm'] ?? NULL;

      case self::METADATA_ATTRIBUTE_VERSION:
        return $data['v'] ?? NULL;

      case self::METADATA_ATTRIBUTE_FRAMES:
        return $data['fr'] ?? NULL;
    }

    return parent::getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('settings', ['file_extensions' => 'json']);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return [
      'lottie_file' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), [
      'type' => 'file_lottie_player',
      'label' => 'visually_hidden',
    ]);
  }

}
