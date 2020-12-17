<?php

namespace Drupal\media_entity_lottie\Plugin\Validation\Constraint;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\media_entity_lottie\Plugin\media\Source\LottieFile;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates Lottie files.
 */
class LottieFileConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Creates a new LottieFileConstraintValidator instance.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $value->getEntity();
    /** @var \Drupal\media_entity_lottie\Plugin\media\Source\LottieFile $source */
    $source = $media->getSource();

    if (!($source instanceof LottieFile)) {
      throw new LogicException('Media source must implement ' . LottieFile::class);
    }

    $fid = $source->getSourceFieldValue($media);

    /** @var \Drupal\file\Entity\File $file_entity */
    $file_entity = File::load($fid);
    $fileName = $file_entity->getFilename();
    $file = file_get_contents($file_entity->getFileUri());

    // Ensure that the file is not empty.
    if (empty($file)) {
      $this->context->addViolation($constraint->emptyFile, ['%value' => $fileName]);
      return;
    }

    // Ensure that the file contains json.
    @ $lottie = Json::decode($file);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->addViolation($constraint->notValid);
      $this->context->addViolation($constraint->notValid, ['%value' => $fileName]);
      return;
    }

    // Ensure that the file is Lottie
    if (!$this->isValidLottie($lottie)) {
      $this->context->addViolation($constraint->notLottie, ['%value' => $fileName]);
    }
  }

  /**
   * Checks if the provided array contains the properties of the Lottie format.
   *
   * @param array $file_content
   *   An associative array containing the file lottie properties.
   *
   * @return bool
   *   TRUE if file_content is an array with valid lottie properties.
   */
  private function isValidLottie(array $file_content): bool {
    $animation_properties = [
      'v' => FALSE,
      'fr' => TRUE,
      'ip' => TRUE,
      'op' => TRUE,
      'w' => TRUE,
      'h' => TRUE,
      'nm' => FALSE,
      'ddd' => TRUE,
    ];
    foreach ($animation_properties as $property => $required) {
      if ($required) {
        if (!array_key_exists($property, $file_content)) {
          return FALSE;
        }
      }
      else {
        if (!array_key_exists($property, $file_content)) {
          $this->messenger->addMessage(
            $this->t('The animation property %property is missing.',
              ['%property' => $property]
            ),
            'warning'
          );
        }
      }
    }
    return TRUE;
  }

}
