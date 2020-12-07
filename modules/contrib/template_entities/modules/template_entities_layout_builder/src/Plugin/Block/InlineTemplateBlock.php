<?php

namespace Drupal\template_entities_layout_builder\Plugin\Block;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\Plugin\Block\InlineBlock;

/**
 * Extends an inline block plugin type to support use by template entities.
 *
 * @Block(
 *  id = "inline_template_block",
 *  admin_label = @Translation("Inline template block"),
 *  category = @Translation("Inline template blocks"),
 *  deriver = "Drupal\layout_builder\Plugin\Derivative\InlineBlockDeriver",
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class InlineTemplateBlock extends InlineBlock {
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_display_repository, $current_user);

    if ($this->isNew && isset($configuration['block_content'])) {
      // Initialise with pre-built block content.
      $this->blockContent = $configuration['block_content'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Add the entity form display in a process callback so that #parents can
    // be successfully propagated to field widgets.
    $form['block_form']['#access'] = $this->currentUser->hasPermission('create and edit custom blocks from templates');

    return $form;
  }

}
