<?php

namespace Drupal\template_entities\PluginForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for template plugin forms.
 */
interface TemplatePluginFormInterface extends PluginFormInterface {

  /**
   * Gets the form element to which errors should be assigned.
   *
   * @param array $form
   *   The form, as built by buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form element.
   */
  public function getErrorElement(array $form, FormStateInterface $form_state);

}
