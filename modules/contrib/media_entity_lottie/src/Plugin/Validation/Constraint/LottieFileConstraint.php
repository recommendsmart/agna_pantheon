<?php

namespace Drupal\media_entity_lottie\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted file is a valid json lottie file.
 *
 * @Constraint(
 *   id = "lottie_file",
 *   label = @Translation("Lottie file", context = "Validation"),
 *   type = "file"
 * )
 */
class LottieFileConstraint extends Constraint {

  /**
   * The error message if the file is empty.
   *
   * @var string
   */
  public $emptyFile = '%value is empty!';

  /**
   * The error message if the file is not JSON.
   *
   * @var string
   */
  public $notValid = '%value is not a valid JSON file!';

  /**
   * The error message if the file is not a Lottie File.
   *
   * @var string
   */
  public $notLottie = '%value is not a valid Lottie file!';

}
