<?php

namespace Drupal\template_entities\Plugin\TemplatePlugin;

use Drupal;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Template plugin for nodes.
 *
 * This overrides the derived one but gets derived definition merged in
 * automatically.
 *
 * @TemplatePlugin(
 *   id = "canonical_entities:node",
 *   forms = {
 *     "create-template" =
 *   "Drupal\template_entities\PluginForm\NodeTemplate\CreateTemplateForm",
 *   }
 * )
 */
class NodeTemplate extends TemplatePluginBase implements DependentPluginInterface, PluginWithFormsInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [
      'module' => ['node'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDuplicateEntity(EntityInterface $entity) {
    parent::alterDuplicateEntity($entity);

    /** @var \Drupal\node\NodeInterface $node */
    $node = $entity;

    $node->setCreatedTime(NULL);

    // Make current user the owner.
    $node->setOwnerId(Drupal::currentUser()->id());
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDuplicateTranslation(ContentEntityInterface $translation, $language, $translations_are_moderated = FALSE) {
    /** @var \Drupal\node\NodeInterface $translation_node */
    $translation_node = $translation;

    // Clear to allow to be set on form submit.
    $translation_node->setCreatedTime(NULL);

    $untranslated_node = $translation_node->getUntranslated();

    // Clear to allow value entered on form to be used.
    $translation_node->setOwnerId($untranslated_node->getOwnerId());
  }

  /**
   * {@inheritdoc}
   */
  public function alterDuplicateTranslationPresave(ContentEntityInterface $translation, $translations_are_moderated = FALSE) {
    parent::alterDuplicateTranslationPresave($translation);

    /** @var \Drupal\node\Entity\Node $translation_node */
    $translation_node = $translation;

    /** @var \Drupal\node\Entity\Node $untranslated */
    $untranslated = $translation_node->getUntranslated();

    $translation_node->setCreatedTime($untranslated->getCreatedTime());
  }

}

