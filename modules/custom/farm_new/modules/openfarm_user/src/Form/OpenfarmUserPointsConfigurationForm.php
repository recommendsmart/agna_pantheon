<?php

namespace Drupal\openfarm_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpenfarmUserPointsConfigurationForm.
 */
class OpenfarmUserPointsConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openfarm_user.user_points_configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openfarm_user_points_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openfarm_user.user_points_configuration');

    $form['score_configurations'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User score configurations'),
      '#description' => $this->t('Set score weights for user points translations.'),
    ];
    $form['score_configurations']['vote'] = [
      '#type' => 'number',
      '#title' => $this->t('Votes points'),
      '#min' => 0,
      '#step' => 0.1,
      '#default_value' => $config->get('vote') ?? 1,
    ];
    $form['score_configurations']['record'] = [
      '#type' => 'number',
      '#title' => $this->t('Records points'),
      '#min' => 0,
      '#default_value' => $config->get('record') ?? 10,
    ];

    $form['score_configurations']['comment'] = [
      '#type' => 'number',
      '#title' => $this->t('Comments points'),
      '#min' => 0,
      '#default_value' => $config->get('comment') ?? 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('openfarm_user.user_points_configuration')
      ->set('comment', $form_state->getValue('comment'))
      ->set('vote', $form_state->getValue('vote'))
      ->set('record', $form_state->getValue('record'))
      ->save();
  }

}
