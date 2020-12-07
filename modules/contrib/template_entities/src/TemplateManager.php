<?php

namespace Drupal\template_entities;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\template_entities\Entity\TemplateType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the template manager.
 */
class TemplateManager implements TemplateManagerInterface {

  use StringTranslationTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Constructs a new WorkspaceManager.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, StateInterface $state, LoggerInterface $logger, ClassResolverInterface $class_resolver) {
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->state = $state;
    $this->logger = $logger;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityTypeTemplateable(string $entity_type_id) {
    return $entity_type_id !== 'template' && !empty($this->getTemplateTypesForEntityType($entity_type_id));
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateTypesForEntityType(string $entity_type_id = NULL, string $bundle = NULL) {
    $template_types_by_entity_type = &drupal_static(__FUNCTION__);

    if ($template_types_by_entity_type === NULL) {
      $template_types_by_entity_type = [];
      $template_types = TemplateType::loadMultiple();

      /** @var TemplateType $template_type */
      foreach ($template_types as $template_type_id => $template_type) {
        $target_entity_type_id = $template_type->getTargetEntityTypeId();
        if (!isset($template_types_by_entity_type[$target_entity_type_id])) {
          $template_types_by_entity_type[$target_entity_type_id] = [];
        }

        $template_types_by_entity_type[$target_entity_type_id][$template_type_id] = $template_type;
      }
    }

    if ($entity_type_id === NULL) {
      return $template_types_by_entity_type;
    }
    elseif (isset($template_types_by_entity_type[$entity_type_id])) {
      if ($bundle) {
        // Filter to single bundle.
        return array_filter($template_types_by_entity_type[$entity_type_id], function (TemplateType $v) use ($bundle) {
          return in_array($bundle, $v->getBundles());
        });
      }
      else {
        return $template_types_by_entity_type[$entity_type_id];
      }
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isTemplate($entity_id, $entity_type_id) {
    return !empty($this->getTemplateIdsForEntity($entity_id, $entity_type_id));
  }

  /**
   * Get template ids that use a given entity as a template.
   *
   * @param $entity_id
   *   Entity id to check.
   * @param $entity_type_id
   *   Entity type id of the entity to check.
   *
   * @return array|int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTemplateIdsForEntity($entity_id, $entity_type_id) {
    $template_storage = $this->entityTypeManager->getStorage('template');
    $query = $template_storage->getQuery();
    $query->condition('template_entity_id', $entity_id);

    $template_types = array_keys($this->getTemplateTypesForEntityType($entity_type_id));
    $query->condition('type', $template_types, 'IN');

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTemplatesForEntity(EntityInterface $entity) {
    $template_storage = $this->entityTypeManager->getStorage('template');
    return $template_storage->loadMultiple($this->getTemplateIdsForEntity($entity->id(), $entity->getEntityTypeId()));
  }

  protected function alterEntitySqlQuery(SelectInterface $query, $entity_type_id) {
    // Deal with entity references. By adding the allow_templates settings
    // to the selection handler settings, we ensure that the entity reference
    // field - whatever it's type - will allow selection of entities used as
    // templates. Only needed for the template entity form.
    /** @var \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection $handler */
    if ($handler = $query->getMetadata('entity_reference_selection_handler')) {
      if (!empty($handler->getConfiguration()['allow_templates'])) {
        $query->addTag('template_entities_allow_templates');
        return $this;
      }
    }

    // Instances where the entity query is called but query modification
    // should not occur.
    // @todo - test this.
    if (!$query->hasTag($entity_type_id . '_access')) {
      return $this;
    }

    // Don't alter all revision queries for now to allow access to revisions listings
    // of template content.
    // @todo - test this.
    if ($query->getMetaData('all_revisions')) {
      return $this;
    }

    $template_types = $this->getTemplateTypesForEntityType($entity_type_id);

    foreach ($template_types as $template_type) {
      $template_type->getTemplatePlugin()->entityQueryAlter($query, $template_type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterQuery(AlterableInterface $query) {
    // Allow any template type plugin to alter any query.
    if ($query instanceof \Drupal\Core\Database\Query\SelectInterface) {

      // If it's an entity query, only call plugins for these template types.
      if ($query->hasTag('entity_query')) {
        $entity_type_id = $query->getMetaData('entity_type');

        $this->alterEntitySqlQuery($query, $entity_type_id);
      }

      // Efficiently call alter hooks for the query.
      // For all active template plugins, i.e. those that
      $template_types = TemplateType::loadMultiple();

      foreach ($template_types as $template_type) {
        $template_type->getTemplatePlugin()->selectAlter($query, $template_type);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplatesForEntityType(string $entity_type_id) {
    $template_type_ids = array_keys($this->getTemplateTypesForEntityType($entity_type_id));

    if (!empty($template_type_ids)) {
      $template_storage = $this->entityTypeManager->getStorage('template');
      return $template_storage->loadByProperties(['type' => $template_type_ids]);
    }

    return [];
  }

  /**
   * @inheritDoc
   */
  public function getTemplatesOfType(string $template_type_id) {
    $template_storage = $this->entityTypeManager->getStorage('template');
    return $template_storage->loadByProperties(['type' => $template_type_id]);
  }

  /**
   * @inheritDoc
   */
  public function entityInsert(EntityInterface $entity) {
    if (isset($entity->template)) {
      /** @var \Drupal\template_entities\Entity\Template $template */
      $template = $entity->template;
      $template->getTemplatePlugin()->duplicateEntityInsert($entity);
    }
  }

  /**
   * @inheritDoc
   */
  public function entityPresave(EntityInterface $entity) {
    if (isset($entity->template)) {
      /** @var \Drupal\template_entities\Entity\Template $template */
      $template = $entity->template;
      $template->getTemplatePlugin()->duplicateEntityPresave($entity);
    }
  }

  /**
   * @inheritDoc
   */
  public function alterNewEntityForm(&$form, \Drupal\Core\Form\FormStateInterface $form_state, EntityInterface $entity) {
    if (isset($entity->template)) {
      /** @var \Drupal\template_entities\Entity\Template $template */
      $template = $entity->template;
      $template->getTemplatePlugin()->alterNewEntityForm($form, $form_state, $entity);
    }
  }

}
