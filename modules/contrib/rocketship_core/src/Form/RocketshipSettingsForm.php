<?php

namespace Drupal\rocketship_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RocketshipSettingsForm.
 */
class RocketshipSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'rocketship_core.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rocketship_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rocketship_core.settings');

    $form['allow_header_paragraph_on_homepage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow header paragraph on homepage'),
      '#description' => $this->t('When checked, content editors can add a header paragraph to the frontpage node.'),
      '#default_value' => (bool) $config->get('allow_header_paragraph_on_homepage'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('rocketship_core.settings')
      ->set('allow_header_paragraph_on_homepage', $form_state->getValue('allow_header_paragraph_on_homepage', FALSE))
      ->save();
  }

}
