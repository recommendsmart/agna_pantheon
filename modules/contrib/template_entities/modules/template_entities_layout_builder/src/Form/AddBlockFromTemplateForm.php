<?php

namespace Drupal\template_entities_layout_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Form\ConfigureBlockFormBase;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\template_entities\Entity\Template;

/**
 * Provides a form to add a block.
 *
 * @internal
 *   Form classes are internal.
 */
class AddBlockFromTemplateForm extends ConfigureBlockFormBase {

  use LayoutBuilderHighlightTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'template_entities_layout_builder_add_block_from_template';
  }

  /**
   * {@inheritdoc}
   */
  protected function submitLabel() {
    return $this->t('Add block from template');
  }

  /**
   * Builds the form for the block template.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage being configured.
   * @param int $delta
   *   The delta of the section.
   * @param string $region
   *   The region of the block.
   * @param string|null $template_id
   *   The template ID of the template from which to create the block to add.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $region = NULL, $template_id = NULL) {
    /** @var \Drupal\template_entities\Entity\TemplateInterface $template */
    $template = Template::load($template_id);

    /** @var \Drupal\block_content\BlockContentInterface $original_block_content */
    $original_block_content = $template->getSourceEntity();
    $duplicate_block_content = $template->getTemplatePlugin()->duplicateEntity($original_block_content, $template);
    $duplicate_block_content->setNonReusable();

    // Use inline block subclass plugin that use the block_content passed
    // in the configuration below to set the block plugin's block content.
    $plugin_id = 'inline_template_block:' . $original_block_content->bundle();

    // Only generate a new component once per form submission.
    if (!$component = $form_state->get('layout_builder__component')) {
      $component = new SectionComponent($this->uuidGenerator->generate(), $region, ['id' => $plugin_id, 'block_content' => $duplicate_block_content]);
      $section_storage->getSection($delta)->appendComponent($component);
      $form_state->set('layout_builder__component', $component);
    }
    $form['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockAddHighlightId($delta, $region);
    return $this->doBuildForm($form, $form_state, $section_storage, $delta, $component);
  }

}
