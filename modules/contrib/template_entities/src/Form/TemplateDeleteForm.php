<?php

namespace Drupal\template_entities\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityDeleteFormTrait;

/**
 * Provides a form for deleting a template entity.
 *
 * @ingroup template_entities
 */
class TemplateDeleteForm extends ContentEntityConfirmFormBase {

  use EntityDeleteFormTrait;
}
