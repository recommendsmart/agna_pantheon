<?php

namespace Drupal\rocketship_core\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class OneColumnWithOptionalSubregionLayout.
 *
 * @package Drupal\rocketship_core\Plugin\Layout
 */
class OneColumnWithOptionalSubregionLayout extends RocketshipCoreBaseLayout {

  /**
   * {@inheritdoc}
   */
  protected function calculateRegions(array &$regions) {
    if (!$this->configuration['with_subregion']) {
      if (isset($regions['subregion'])) {
        unset($regions['subregion']);
      }
    }
    else {
      $regions['subregion'] = ['label' => 'Subregion'];
    }

    if (!$this->configuration['with_subregion_02']) {
      if (isset($regions['subregion_02'])) {
        unset($regions['subregion_02']);
      }
    }
    else {
      $regions['subregion_02'] = ['label' => 'Subregion 2'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['with_subregion'] = FALSE;
    $configuration['with_subregion_02'] = FALSE;
    $configuration['subregion_position'] = 'after';
    $configuration['subregion_02_position'] = 'after';
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['with_subregion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable subregion'),
      '#default_value' => $this->getConfiguration()['with_subregion'],
      '#description' => $this->t('Choose whether or not to use a subregion.'),
    ];
    $form['subregion_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Subregion position'),
      '#description' => $this->t('Choose the position of the subregions.'),
      '#default_value' => $this->getConfiguration()['subregion_position'],
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
      ],
    ];
    $form['with_subregion_02'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable subregion 2'),
      '#default_value' => $this->getConfiguration()['with_subregion'],
      '#description' => $this->t('Choose whether or not to use a subregion.'),
    ];
    $form['subregion_02_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Subregion 2 position'),
      '#description' => $this->t('Choose the position of the subregions.'),
      '#default_value' => $this->getConfiguration()['subregion_02_position'],
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
      ],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['with_subregion'] = $form_state->getValue('with_subregion');
    $this->configuration['with_subregion_02'] = $form_state->getValue('with_subregion_02');
    $this->configuration['subregion_position'] = $form_state->getValue('subregion_position');
    $this->configuration['subregion_02_position'] = $form_state->getValue('subregion_02_position');
    parent::submitConfigurationForm($form, $form_state);
  }

}
