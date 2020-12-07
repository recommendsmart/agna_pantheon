<?php

/**
 * @file
 * Hooks provided by the Template Entities module.
 */

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Alters entities created from templates before form creation.
 *
 * @param \Drupal\Core\Entity\ContentEntityInterface $new_entity
 *   New entity created from template before used to populate form.
 *
 * @param array $context
 *   Context array with template (entity) object and type id:
 *      [
 *        'template' => $template,
 *        'template_type_id' => 'landing_pages',
 *      ]
 *   Use template->getSourceEntity() to get the original entity.
 *
 * @see hook_template_entities_new_TEMPLATE_TYPE_ID_alter()
 *
 * @ingroup template_entities_api
 */
function hook_template_entities_new_alter(ContentEntityInterface $new_entity, array $context) {
  if ($context['template_type_id'] === 'landing_pages') {
    /** @var \Drupal\template_entities\Entity\TemplateInterface $template */
    $template = $context['template'];

    /** @var \Drupal\node\Entity\Node $node */
    $node = $new_entity;
    $node->setRevisionLogMessage(t('New landing page created from "@template".', ['@template' => $template->label()]));
  }
}

/**
 * Alters entities created from templates before form creation.
 *
 * @param \Drupal\Core\Entity\ContentEntityInterface $new_entity
 *  New entity created from template before used to populate form.
 *
 * @param array $context
 *   Context array with template (entity) object and type id:
 *      [
 *        'template' => $template,
 *        'template_type_id' => 'landing_pages',
 *      ]
 *   Use template->getSourceEntity() to get the original entity.
 *
 * @see hook_template_entities_new_alter()
 *
 * @ingroup template_entities_api
 */
function hook_template_entities_new_TEMPLATE_TYPE_ID_alter(ContentEntityInterface $new_entity, array $context) {
  /** @var \Drupal\template_entities\Entity\TemplateInterface $template */
  $template = $context['template'];

  /** @var \Drupal\node\Entity\Node $node */
  $node = $new_entity;
  $node->setRevisionLogMessage(t('New landing page created from "@template".', ['@template' => $template->label()]));
}

/**
 * Alter template plugin info.
 *
 * @param $definitions
 */
function hook_template_entities_template_plugin_info(&$definitions) {
}
