<?php
/**
 * @file
 * Contains Drupal\rocketship_realisation_core\Form\SettingsForm.
 */

namespace Drupal\rocketship_realisation_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\rocketship_realisation_core\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
        'rocketship_realisation_core.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rocketship_realisation_core_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rocketship_realisation_core.settings');

    $form['rs_p_styling_settings'] = [
        '#type' => 'fieldset',
        '#title' => t('Styling settings'),
        '#open' => TRUE,
    ];

    $form['rs_p_styling_settings']['css_structural'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable default CSS'),
        '#default_value' => $config->get('css_structural'),
        '#description' => t('Loads a CSS file with some very basic CSS included in the module. If disabled, make sure you have your own theming in place.'),
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save configuration'),
        '#button_type' => 'primary',
    ];

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';


    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    //
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Save the values.
    $config = $this->config('rocketship_realisation_core.settings');
    $config->set('css_structural', $form_state->getValue('css_structural'))
        ->save();

    parent::submitForm($form, $form_state);
  }

}
