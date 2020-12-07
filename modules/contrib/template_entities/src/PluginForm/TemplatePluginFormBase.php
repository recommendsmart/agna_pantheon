<?php

namespace Drupal\template_entities\PluginForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormBase;

/**
 * Provides a base class for template plugin forms.
 *
 * @see \Drupal\Core\Plugin\PluginFormBase
 */
abstract class TemplatePluginFormBase extends PluginFormBase implements TemplatePluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function getErrorElement(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
