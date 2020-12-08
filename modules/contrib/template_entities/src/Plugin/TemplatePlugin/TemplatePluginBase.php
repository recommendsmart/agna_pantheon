<?php

namespace Drupal\template_entities\Plugin\TemplatePlugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Routing\Router;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\template_entities\Entity\Template;
use Drupal\template_entities\Entity\TemplateType;
use Drupal\template_entities\Plugin\TemplatePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Base class for Template plugin plugins.
 *
 * Plugins needing configuration should override:
 *  - defaultConfiguration()
 *  - templateForm($form, $form_state)
 *  - templateValidate($form, $form_state)
 *  - templateSubmit($form, $form_state)
 *
 * Plugins needing to alter the result of entity->duplicateEntity should
 * override one or more of:
 *  - duplicateEntity(EntityInterface $entity)
 *  - alterDuplicateEntity(EntityInterface $entity)
 *  - alterDuplicateTranslation(ContentEntityInterface $translation, $translations_are_moderated = FALSE)
 *  - duplicateEntityPresave(EntityInterface $entity)
 *  - alterDuplicateEntityPresave(EntityInterface $entity)
 *  - alterDuplicateTranslationPresave(ContentEntityInterface $translation, $translations_are_moderated = FALSE)
 *  - duplicateEntityInsert(EntityInterface $entity)
 *
 * @TemplatePlugin(
 *   id = "canonical_entities",
 *   deriver =
 *   "\Drupal\template_entities\Plugin\Deriver\EntityTemplatePluginDeriver"
 * )
 */
class TemplatePluginBase extends PluginBase implements TemplatePluginInterface, ContainerFactoryPluginInterface {

  use PluginWithFormsTrait;
  use StringTranslationTrait;

  /**
   * The plugin entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The router.
   *
   * @var \Drupal\Core\Routing\Router
   */
  protected Router $router;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * The content translation manager service.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  protected const TRANSLATION_POLICY_NO_COPY = 0;
  protected const TRANSLATION_POLICY_COPY = 1;
  protected const TRANSLATION_POLICY_COPY_OUTDATED = 2;


  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, Router $router, EntityTypeBundleInfoInterface $entity_type_bundle_info, $content_translation_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityType = $entity_type;
    $this->entityTypeManager = $entity_type_manager;
    $this->router = $router;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->contentTranslationManager = $content_translation_manager;

    // Set configuration with defaults.
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $content_translation_manager = \Drupal::moduleHandler()->moduleExists('content_translation') ? \Drupal::service('content_translation.manager') : NULL;

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
        ->getDefinition($plugin_definition['entity_type_id']),
      $container->get('entity_type.manager'),
      $container->get('router.no_access_checks'),
      $container->get('entity_type.bundle.info'),
      $content_translation_manager
    );

  }

  /**
   * {@inheritdoc}
   */
  public function duplicateEntity(EntityInterface $entity, Template $template) {
    // Simple use.
    $duplicate = $entity->createDuplicate();

    $duplicate->template_original = $entity;
    $duplicate->template = $template;

    $this->alterDuplicateEntity($duplicate);

    $this->processTranslations($duplicate, 'alterDuplicateTranslationBase');

    return $duplicate;
  }

  /**
   * Helper method to call a member method for each translation of an entity.
   *
   * @param EntityInterface $entity
   *   The entity.
   *
   * @param string $method
   *   The member method to call.
   */
  protected function processTranslations(EntityInterface $entity, string $method) {
    if ($entity instanceof \Drupal\Core\Entity\ContentEntityInterface
      && $this->contentTranslationManager
      && $this->contentTranslationManager->isEnabled($entity->getEntityTypeId(), $entity->bundle())) {

      // Handle translations.
      $translation_languages = $entity->getTranslationLanguages(FALSE);

      if (!empty($translation_languages)) {
        // Translations cannot be flagged as outdated when content is moderated.
        // @see ContentTranslationHandler::entityFormAlter().
        $translations_are_moderated = ContentTranslationManager::isPendingRevisionSupportEnabled($entity->getEntityTypeId(), $entity->bundle());

        foreach ($translation_languages as $language) {
          // Handle a single translation.
          $translation = $entity->getTranslation($language->getId());

          call_user_func([$this, $method], $translation, $language, $translations_are_moderated);
        }
      }
    }
  }

  /**
   * Alter duplicate entity before create form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  protected function alterDuplicateEntity(EntityInterface $entity) {
    if ($entity instanceof EntityChangedInterface) {
      // Clear this rather than set it now.
      $entity->setChangedTime(NULL);
    }

    if ($entity instanceof RevisionLogInterface) {
      // Set for all languages.
      $entity->setRevisionLogMessage($this->t('Created from template.'));
    }
  }

  /**
   * Alter duplicate translation before create form.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $translation
   * @param \Drupal\Core\Language\Language $language
   * @param bool $translations_are_moderated
   */
  protected function alterDuplicateTranslationBase(ContentEntityInterface $translation, Language $language, $translations_are_moderated = FALSE) {
    $translation_policy = (int) $this->getConfiguration()['translation_policy'];
    if ($translation_policy === static::TRANSLATION_POLICY_NO_COPY) {
      // Remove translation if not wanted and return early.
      $translation->getUntranslated()->removeTranslation($language->getId());
      return;
    }
    elseif (!$translations_are_moderated
      && $translation_policy === static::TRANSLATION_POLICY_COPY_OUTDATED) {
      // Form default is used to set outdated flag so can be overridden
      // if necessary. Do nothing here.
    }

    $this->alterDuplicateTranslation($translation, $language, $translations_are_moderated);
  }

  /**
   * Alter duplicate translation before create form.
   *
   * Override this.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $translation
   * @param \Drupal\Core\Language\Language $language
   * @param bool $translations_are_moderated
   */
  protected function alterDuplicateTranslation(ContentEntityInterface $translation, Language $language, $translations_are_moderated = FALSE) {}

    /**
   * {@inheritdoc}
   */
  public function duplicateEntityPresave(EntityInterface $entity) {
    $this->alterDuplicateEntityPresave($entity);

    $this->processTranslations($entity, 'alterDuplicateTranslationPresaveBase');
  }

  /**
   * Alter duplicate entity after form submission before saving.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  protected function alterDuplicateEntityPresave(EntityInterface $entity) {}

  /**
   * Alter duplicate translation after form submission before saving.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $translation
   * @param bool $translations_are_moderated
   */
  protected function alterDuplicateTranslationPresaveBase(ContentEntityInterface $translation, $translations_are_moderated = FALSE) {
    $untranslated = $translation->getUntranslated();

    if ($translation instanceof EntityChangedInterface && $untranslated instanceof EntityChangedInterface) {
      // Copy change time from untranslated entity.
      $translation->setChangedTime($untranslated->getChangedTime());
    }

    $this->alterDuplicateTranslationPresave($translation, $translations_are_moderated);
  }

  /**
   * Alter duplicate translation after form submission before saving.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $translation
   * @param bool $translations_are_moderated
   */
  protected function alterDuplicateTranslationPresave(ContentEntityInterface $translation, $translations_are_moderated = FALSE) {}

  /**
   * {@inheritdoc}
   */
  public function getCollectionLinkTemplate() {
    return $this->entityType->hasLinkTemplate('collection') ? $this->entityType->getLinkTemplate('collection') : Url::fromRoute('system.admin_content')
      ->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionRoute(RouteCollection $route_collection) {
    $collection_route_collection = new RouteCollection();

    if ($route = $route_collection->get("entity.{$this->getEntityTypeId()}.collection")) {
      $collection_route_collection->add("entity.{$this->getEntityTypeId()}.collection", $route);
    }
    elseif ($route = $route_collection->get("{$this->getEntityTypeId()}.collection")) {
      $collection_route_collection->add("{$this->getEntityTypeId()}.collection", $route);
    }
    else {
      $collection_route_collection->add('system.admin_content', $route_collection->get('system.admin_content'));
    }

    return $collection_route_collection;
  }

  /**
   * Returns the entity type ID.
   *
   * @return string
   *   The entity type ID.
   */
  protected function getEntityTypeId() {
    return $this->entityType->id();
  }

  /**
   * Returns the entity type.
   *
   * @return EntityTypeInterface
   *   The entity type ID.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Get an array of bundles that the plugin supports.
   *
   * @return array
   *   Array of bundles that this plugin supports.
   */
  public function getBundleOptions() {
    return $this->entityTypeBundleInfo->getBundleInfo($this->entityType->id());
  }

  /**
   * {@inheritdoc}
   */
  public function selectAlter(SelectInterface $query, TemplateType $template_type) {
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query, TemplateType $template_type) {
    // Add default entity query joins.

    // This method not called for all revision queries so safe to use
    // getBaseTable().
    $base_table = $this->entityType->getBaseTable();
    $id_field = $this->entityType->getKey('id');
    $this->selectAddTemplateFilter($query, $template_type, $base_table, $id_field);

    $query->addTag('template_entities_filtered');
  }


  /**
   * Add template filter.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   * @param \Drupal\template_entities\Entity\TemplateType $template_type
   * @param string|null $base_table
   * @param string|null $id_field
   */
  protected function selectAddTemplateFilter(SelectInterface $query, TemplateType $template_type, string $base_table = NULL, string $id_field = NULL) {
    $base_table = $base_table ?: $this->entityType->getBaseTable();

    // Check for alias.
    if (!isset($query->getTables()[$base_table])) {
      foreach ($query->getTables() as $table) {
        if ($table['table'] === $base_table) {
          $base_table = $table['alias'];
          break;
        }
      }
    }

    $id_field = $id_field ?: $this->entityType->getKey('id');

    // Left join to the template entity reference field table to link entities
    // with references from template entities that have for template types
    // (template bundles) that apply to the entity type.
    $template_table_alias = $query->leftJoin('template__template_entity_id', 'template__template_entity_id', "(%alias.template_entity_id_target_id = $base_table.$id_field AND %alias.bundle = :type)", [':type' => $template_type->id()]);

    // Exclude all rows where there is a valid join to the template entity
    // reference field table.
    $query->isNull($template_table_alias . '.template_entity_id_target_id');
  }

  /**
   * @inheritDoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['translation_policy'] = array(
      '#type' => 'radios',
      '#title' => t('Translation policy'),
      '#options' => [
        static::TRANSLATION_POLICY_NO_COPY => $this->t('Do not include translations'),
        static::TRANSLATION_POLICY_COPY => $this->t('Copy translations'),
        static::TRANSLATION_POLICY_COPY_OUTDATED => $this->t('Copy translations and mark as outdated'),
      ],
      '#description' => t('Choose how to handle translations.'),
      '#default_value' => $this->configuration['translation_policy'],
      '#access' => $this->contentTranslationManager && $this->contentTranslationManager->isEnabled($this->getEntityTypeId())
    );

    // Add plugin-specific settings for this block type.
    $form += $this->templateForm($form, $form_state);
    return $form;
  }

  /**
   * Plugin specific configuration form.
   *
   * @param array $form
   *   The form definition array for the full template configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function templateForm($form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->templateValidate($form, $form_state);
  }

  /**
   * Plugin specific configuration form validation.
   *
   * @param array $form
   *   The form definition array for the full template configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function templateValidate($form, FormStateInterface $form_state) {
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {

      if ($this->contentTranslationManager && $this->contentTranslationManager->isEnabled($this->getEntityTypeId())) {
        $this->configuration['translation_policy']
          = (int) $form_state->getValue('translation_policy');
      }

      $this->templateSubmit($form, $form_state);
    }
  }

  /**
   * Plugin specific configuration form submission.
   *
   * @param array $form
   *   The form definition array for the full template configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function templateSubmit($form, FormStateInterface $form_state) {
  }

  /**
   * @inheritDoc
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * @inheritDoc
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * @inheritDoc
   */
  public function defaultConfiguration() {
    return [
      'translation_policy' => static::TRANSLATION_POLICY_NO_COPY,
    ];
  }

  /**
   * Returns generic default configuration for template plugins.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  protected function baseConfigurationDefaults() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function duplicateEntityInsert(EntityInterface $entity) {
  }

  /**
   * @inheritDoc
   */
  public function alterNewEntityForm(&$form, FormStateInterface $form_state, EntityInterface $entity) {
    $translation_policy = (int) $this->configuration['translation_policy'];
    if ($translation_policy === static::TRANSLATION_POLICY_COPY_OUTDATED) {
      // Set the outdated flag on the form.
      if ($this->contentTranslationManager
        && $this->contentTranslationManager->isEnabled($entity->getEntityTypeId(), $entity->bundle())
        && isset($form['content_translation']['retranslate'])) {
          $form['content_translation']['retranslate']['#default_value'] = true;
      }
    }

    /** @var \Drupal\template_entities\Entity\TemplateInterface $template */
    $template = $entity->template;
    if (!$template->get('description')->isEmpty()) {
      $form['template'] = [
        '#type' => 'details',
        '#title' => $this->t('Template guidelines'),
        '#group' => 'advanced',
        '#weight' => 21,
        '#open' => false,
      ];

      $form['template']['template_guidelines'] = [
        '#type' => 'item',
        '#markup' => $template->get('description')->value,
      ];
    }
  }


}
