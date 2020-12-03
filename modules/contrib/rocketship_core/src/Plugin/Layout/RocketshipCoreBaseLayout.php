<?php

namespace Drupal\rocketship_core\Plugin\Layout;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Class RocketshipCoreBaseLayout.
 *
 * @package Drupal\rocketship_core\Plugin\Layout
 */
abstract class RocketshipCoreBaseLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->updateRegions();
  }

  /**
   * Update the regions set on the plugin definition.
   */
  protected function updateRegions() {
    $regions = $this->getPluginDefinition()->getRegions();
    $this->calculateRegions($regions);
    $this->getPluginDefinition()->setRegions($regions);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    // Set default to layout title.
    $configuration['label'] = $this->getPluginDefinition()->getLabel();
    $configuration['classes'] = NULL;
    $configuration['bem-modifier'] = NULL;
    return $configuration;
  }

  /**
   * Alter existing regions.
   *
   * Here you can alter the standard regions array to
   * add or remove regions based on configuration or anything else.
   *
   * @param array $regions
   *   List of regions.
   */
  abstract protected function calculateRegions(array &$regions);

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra classes'),
      '#description' => $this->t('Add extra classes to the outermost section wrapper here, split classes by space.'),
      '#default_value' => $this->configuration['classes'],
    ];
    $form['bem-modifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BEM modifier'),
      '#description' => $this->t('Add a modifier, this will be used to build BEM classes on the nested divs in the layout'),
      '#default_value' => $this->configuration['bem-modifier'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['classes'] = $form_state->getValue('classes', NULL);
    $this->configuration['bem-modifier'] = $form_state->getValue('bem-modifier', NULL);
    parent::submitConfigurationForm($form, $form_state);
    $this->updateRegions();
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $this->updateRegions();
    $build = parent::build($regions);

    // Add extra classes.
    $classes = explode(' ', $this->configuration['classes'] ?? '');
    foreach ($classes as $class) {
      $build['#attributes']['class'][] = $class;
    }

    // Add BEM modifier.

    if (isset($this->configuration['bem-modifier'])) {
      $re = '/(?:\s|_)+/';
      $bem_modifier = preg_replace($re, '-', $this->configuration['bem-modifier']);
      // $bem_modifier = Html::cleanCssIdentifier($this->configuration['bem-modifier']);
      $build['#attributes']['data-bem-modifier'][] = $bem_modifier;
    }

    // Add the plugin definition ID as class as well.
    $build['#attributes']['class'][] =
      'layout--' . Html::cleanCssIdentifier(
        $this->getPluginDefinition()->id()
      );

    return $build;
  }

}
