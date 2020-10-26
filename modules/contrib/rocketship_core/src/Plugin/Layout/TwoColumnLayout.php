<?php

namespace Drupal\rocketship_core\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class TwoColumnLayout.
 *
 * @package Drupal\rocketship_core\Plugin\Layout
 */
class TwoColumnLayout extends RocketshipCoreBaseLayout {

  /**
   * {@inheritdoc}
   */
  protected function calculateRegions(array &$regions) {
    // Do we need anything here? Don't think so.
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['layout_reversed'] = FALSE;
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['layout_reversed'] = [
      '#type' => 'checkbox',
      '#title' => 'Reverse this layout',
      '#description' => 'Reversing this layout will render the sidebar underneath the content on mobile devices.',
      '#default_value' => $this->configuration['layout_reversed'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['layout_reversed'] = $form_state->getValue('layout_reversed');
    parent::submitConfigurationForm($form, $form_state);
  }

}
