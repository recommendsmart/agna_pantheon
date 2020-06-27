<?php

/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_alter().
 *
 * Creates additional install settings.
 */
function brainstorm_profile_form_alter(&$form, FormStateInterface &$form_state, $form_id) {
  if ($form_id == 'install_configure_form') {
    // Add new option at configure site form. If checkbox was selected, we
    // enable custom module, which sends usage statistics.
    $form['additional_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Additional settings'),
      '#collapsible' => FALSE,
    );

    $form['additional_settings']['send_message'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send info to developers team'),
      '#description' => t('You can send us the anonymous data about your site (URL and site-name). If you have any problems it can help us fix them.'),
      '#default_value' => TRUE,
    );

    $form['#submit'][] = 'brainstorm_profile_install_configure_form_custom_submit';
  }
}

/**
 * Submit callback.
 *
 * @see system_form_install_configure_form_alter()
 */
function brainstorm_profile_install_configure_form_custom_submit($form, FormStateInterface &$form_state) {
  if ($form_state->getValue('send_message')) {
    \Drupal::service('module_installer')->install(['profile_stat_sender']);
  }
}

/**
 * Implements brainstorm_profile_install_tasks_alter().
 */
function brainstorm_profile_install_tasks_alter(&$tasks, $install_state) {
  foreach ($install_state as $state) {
    if ($state === 'install_bootstrap_full') {
      $source = 'profiles/brainstorm_profile/libraries/';
      $res = 'libraries/';
      brainstorm_profile_recurse_copy($source, $res);
      drupal_get_messages();
    };
  }
}

/**
 * Recursive copy.
 *
 * @param string $src
 *   - Source folder with files.
 * @param string $dst
 *   - Destination folder.
 */
function brainstorm_profile_recurse_copy($src, $dst) {
  $dir = opendir($src);
  @mkdir($dst);
  while (FALSE !== ($file = readdir($dir))) {
    if (($file != '.') && ($file != '..')) {
      if (is_dir($src . '/' . $file)) {
        brainstorm_profile_recurse_copy($src . '/' . $file, $dst . '/' . $file);
      }
      else {
        copy($src . '/' . $file, $dst . '/' . $file);
      }
    }
  }
  closedir($dir);
}

/**
 * Brainstorm_profile clean alias.
 *
 * @param string $text
 *   String that be changing.
 *
 * @return string
 *   Return machine name for text.
 */
function _brainstorm_profile_clean_alias($text) {
  return preg_replace('/\-+/', '-', strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '', str_replace(' ', '-', $text))));
}
