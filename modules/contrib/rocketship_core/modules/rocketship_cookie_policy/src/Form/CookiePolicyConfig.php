<?php

namespace Drupal\rocketship_cookie_policy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class CookiePolicyConfig.
 *
 * @package Drupal\rocketship_cookie_policy\Form
 */
class CookiePolicyConfig extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * CookiePolicyConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManager $entity_type_manager
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'rocketship_cookie_policy.cookiepolicyconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cookie_policy_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rocketship_cookie_policy.cookiepolicyconfig');
    $form['more_info_page'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('More info page'),
      '#description' => $this->t('Choose the page containing the cookie disclaimer information. There will be a link to this page on the cookie popup'),
      '#default_value' => !empty($config->get('more_info_page')) ? $this->entityTypeManager->getStorage('node')
        ->load($config->get('more_info_page')) : NULL,
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
    ];
    $form['company_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company name'),
      '#description' => $this->t('"Example: [company_name] uses cookies to improve your online experience"'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => !empty($config->get('company_name')) ? $config->get('company_name') : $this->config('system.site')
        ->get('name'),
    ];
    $form['privacy_policy'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Privacy policy'),
      '#description' => $this->t('Choose the page containing the Privacy Policy information. A token with this URL will be made available. Token: [site:privacy-policy-url]'),
      '#default_value' => !empty($config->get('privacy_policy')) ? $this->entityTypeManager->getStorage('node')
        ->load($config->get('privacy_policy')) : NULL,
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
    ];
    $form['legal_disclaimer'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Legal disclaimer'),
      '#description' => $this->t('Choose the page containing the legal disclaimer information. A token with this URL will be made available. Token: [site:legal-disclaimer-url]'),
      '#default_value' => !empty($config->get('legal_disclaimer')) ? $this->entityTypeManager->getStorage('node')
        ->load($config->get('legal_disclaimer')) : NULL,
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
    ];
    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#description' => $this->t('"Example: I understand"'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => !empty($config->get('link_text')) ? $config->get('link_text') : t('I understand'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('rocketship_cookie_policy.cookiepolicyconfig')
      ->set('more_info_page', $form_state->getValue('more_info_page'))
      ->set('privacy_policy', $form_state->getValue('privacy_policy'))
      ->set('legal_disclaimer', $form_state->getValue('legal_disclaimer'))
      ->set('company_name', $form_state->getValue('company_name'))
      ->set('link_text', $form_state->getValue('link_text'))
      ->save();
  }

}
