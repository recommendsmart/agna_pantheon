<?php

namespace Drupal\template_entities\Entity;

use Drupal;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\template_entities\BundleFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Template entity.
 *
 * @ingroup template_entities
 *
 * @ContentEntityType(
 *   id = "template",
 *   label = @Translation("Template"),
 *   label_collection = @Translation("Templates"),
 *   label_singular = @Translation("template"),
 *   label_plural = @Translation("templates"),
 *   label_count = @PluralTranslation(
 *     singular = "@count template",
 *     plural = "@count templates",
 *   ),
 *   bundle_label = @Translation("Template type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\template_entities\TemplateListBuilder",
 *     "views_data" = "Drupal\template_entities\Entity\TemplateViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\template_entities\Form\TemplateForm",
 *       "add" = "Drupal\template_entities\Form\TemplateForm",
 *       "edit" = "Drupal\template_entities\Form\TemplateForm",
 *       "delete" = "Drupal\template_entities\Form\TemplateDeleteForm",
 *     },
 *     "access" = "Drupal\template_entities\TemplateAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\template_entities\TemplateHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "template",
 *   admin_permission = "administer template entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/templates/template/{template}",
 *     "add-page" = "/admin/structure/templates/template/add",
 *     "add-form" = "/admin/structure/templates/template/add/{template_type}",
 *     "edit-form" = "/admin/structure/templates/template/{template}/edit",
 *     "delete-form" = "/admin/structure/templates/template/{template}/delete",
 *     "collection" = "/admin/structure/templates",
 *     "new-from-template" = "/template/template/{template}/new",
 *     "preview" = "/template/template/{template}/preview",
 *   },
 *   bundle_entity_type = "template_type",
 *   field_ui_base_route = "entity.template_type.edit_form"
 * )
 */
class Template extends ContentEntityBase implements TemplateInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    /** @var \Drupal\template_entities\Entity\TemplateInterface $entity */
    foreach ($entities as $entity) {
      // Invalidate source entities cache tags to update entity lists etc.
      $template_source_entity = $entity->getSourceEntity();
      if ($template_source_entity) {
        // Effect on cache will be similar to saving the entity.
        static::invalidateEntityTagsOnSave($template_source_entity, TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Template entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the template.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('Indicates whether the template is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the template was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the template was last updated.'));

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('A short description to help identify where and when to use the template.'))
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
        'settings' => [
          'size' => 80,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['template_entity_id'] = BundleFieldDefinition::create('entity_reference')
      ->setLabel(t('Template source'))
      ->setDescription(t('The entity to use as the template. Entities used as templates will only be accessible to users with permission to manage or use this template.'))
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 0,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setSetting('handler_settings', ['allow_templates' => TRUE])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    // Borrowed this logic from the Comment module.
    // Warning! May change in the future: https://www.drupal.org/node/2346347
    if ($template_type = TemplateType::load($bundle)) {
      $target_entity_type = $template_type->getTemplatePlugin()->getEntityType();
      $target_entity_type_label = $target_entity_type->getSingularLabel();

      /** @var BundleFieldDefinition $field */
      $field = clone $base_field_definitions['template_entity_id'];
      $field->setSetting('target_type', $template_type->getTargetEntityTypeId());

      $field->setLabel(t('Template source @entity_type_label', ['@entity_type_label' => $target_entity_type_label]));

      $handler_settings = $field->getSetting('handler_settings') ?? [];

      $description = t('Select the @entity_type_label to use as the template source. The @entity_type_label used for this template will only be accessible to users with permission to manage or use this template.', ['@entity_type_label' => $target_entity_type_label]);

      if ($bundles = $template_type->getBundles()) {
        $handler_settings['target_bundles'] = $bundles;

        if ($bundle_entity_type_id = $target_entity_type->getBundleEntityType()) {
          /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info */
          $entity_type_bundle_info = \Drupal::service('entity_type.bundle.info');

          $source_entity_bundles = $entity_type_bundle_info->getBundleInfo($target_entity_type->id());
          $bundle_entity_type = \Drupal::entityTypeManager()->getDefinition($bundle_entity_type_id);
          $bundle_labels = array_intersect_key(array_map(function($v) {return $v['label'];},$source_entity_bundles), $bundles);
          $description .= ' ' . new PluralTranslatableMarkup(count($bundles), 'This template may use any @entity_type_label_singular with @bundle_label_singular @types.', 'This template may use any @entity_type_label_singular of the following @bundle_label_plural: @types.', [
            '@entity_type_label_singular' => $target_entity_type->getSingularLabel(),
            '@bundle_label_singular' => $bundle_entity_type->getSingularLabel(),
            '@bundle_label_plural' => $bundle_entity_type->getPluralLabel(),
            '@types' => implode(', ', $bundle_labels),
          ]);
        }


      }
      $field->setDescription($description);
      $handler_settings['template_type_id'] = $template_type->id();
      $field->setSetting('handler_settings', $handler_settings);
      $fields['template_entity_id'] = $field;
      return $fields;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $template_source_entity = $this->getSourceEntity();

    if ($update) {
      $original_template_source_entity = $this->original->getSourceEntity();
      if ($original_template_source_entity && $template_source_entity !== $original_template_source_entity) {
        // Same as delete template.
        static::invalidateEntityTagsOnSave($original_template_source_entity, TRUE);
      }
    }

    // Invalidate source entities cache tags to update entity lists etc.
    if ($template_source_entity) {
      // Effect on cache will be similar to deleting the entity.
      static::invalidateTagsOnDelete($template_source_entity->getEntityType(), [$template_source_entity]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity() {
    return $this->get('template_entity_id')->entity;
  }

  /**
   * Invalidates an entity's cache tags upon save.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param bool $update
   *   TRUE if the entity has been updated, or FALSE if it has been inserted.
   */
  protected static function invalidateEntityTagsOnSave(EntityInterface $entity, bool $update) {
    // An entity was created or updated: invalidate its list cache tags. (An
    // updated entity may start to appear in a listing because it now meets that
    // listing's filtering requirements. A newly created entity may start to
    // appear in listings because it did not exist before.)
    $tags = static::getEntityListCacheTagsToInvalidate($entity);
    if ($entity->hasLinkTemplate('canonical')) {
      // Creating or updating an entity may change a cached 403 or 404 response.
      $tags = Cache::mergeTags($tags, ['4xx-response']);
    }

    if ($update) {
      // An existing entity was updated, also invalidate its unique cache tag.
      $tags = Cache::mergeTags($tags, $entity->getCacheTagsToInvalidate());
    }
    Cache::invalidateTags($tags);
  }

  /**
   * Replicate protected method behaviour of getListCacheTagsToInvalidate().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return string[]
   */
  protected static function getEntityListCacheTagsToInvalidate(EntityInterface $entity) {
    $tags = $entity->getEntityType()->getListCacheTags();
    if ($entity->getEntityType()->hasKey('bundle')) {
      $tags[] = $entity->getEntityTypeId() . '_list:' . $entity->bundle();
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    return $this->set('name', $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    return $this->set('description', $description);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    return $this->set('created', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    return $this->set('user_id', $uid);
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    return $this->set('user_id', $account->id());
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplatePlugin() {
    /** @var \Drupal\template_entities\Entity\TemplateType $template_type */
    $template_type = TemplateType::load($this->bundle());
    return $template_type->getTemplatePlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationAfterNewFromTemplate() {
    // If a collection page exists and there is no separate entity view page (
    // i.e. edit is same as canonical) then go to the collection page.
    if ($this->getSourceEntity()->hasLinkTemplate('collection')
      && $this->getSourceEntity()
        ->getEntityType()
        ->getLinkTemplate('edit-form') === $this->getSourceEntity()
        ->getEntityType()
        ->getLinkTemplate('canonical')) {
      return $this->getSourceEntity()->toUrl('collection')->toString();
    }
    else {
      return FALSE;
    }
  }

}
