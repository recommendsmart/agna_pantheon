<?php

namespace Drupal\openfarm_footer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpenfarmFooterConfigForm.
 */
class OpenfarmFooterConfigForm extends ConfigFormBase {

  /**
   * Default social links.
   *
   * Todo: Ask for twitter link.
   */
  const TWITTER = '';
  const GITHUB = 'https://github.com/istolar/openfarm_distribution';

  /**
   * The openfarm official site.
   */
  const OPENIDEAL_OFFICIAL_SITE = 'https://www.openfarmapp.com/';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openfarm_footer.openfarm_footer_links_config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openfarm_footer_links_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openfarm_footer.openfarm_footer_links_config');

    $form['twitter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter'),
      '#description' => $this->t('Openfarm twitter official link'),
      '#default_value' => $config->get('twitter') ?? self::TWITTER,
    ];

    $form['github'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Github'),
      '#description' => $this->t('Openfarm github repository official link'),
      '#default_value' => $config->get('github') ?? self::GITHUB,
    ];

    $form['openfarm_official_site'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Openfarm official site'),
      '#default_value' => $config->get('openfarm_official_site') ?? self::OPENIDEAL_OFFICIAL_SITE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('openfarm_footer.openfarm_footer_links_config')
      ->set('twitter', $form_state->getValue('twitter'))
      ->set('github', $form_state->getValue('github'))
      ->set('openfarm_official_site', $form_state->getValue('openfarm_official_site'))
      ->save();
  }

}
