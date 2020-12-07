<?php

namespace Drupal\template_entities\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\template_entities\Entity\TemplateInterface;
use Drupal\template_entities\Entity\TemplateTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TemplateController.
 */
class TemplateController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * TemplateController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(RequestStack $request_stack, RendererInterface $renderer, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->requestStack = $request_stack;
    $this->renderer = $renderer;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('renderer'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Redirect to entity type specific route.
   *
   * @param \Drupal\template_entities\Entity\TemplateInterface $template
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|false
   */
  public function newFromTemplateRedirect(TemplateInterface $template) {
    /** @var \Drupal\Core\Entity\EntityInterface $source */
    $source = $template->get('template_entity_id')->entity;

    if (!$source) {
      $this->messenger()
        ->addWarning($this->t('No template source entity set.'));
      return FALSE;
    }
    $new_from_template_route_name = 'template.' . $template->bundle() . '.new_from_template';

    $query = [];

    // Add in a destination on the redirect.
    // Clear current destination.
    // @see https://www.drupal.org/project/drupal/issues/2950883.
    $destination = $this->requestStack->getCurrentRequest()->query->get('destination');
    if ($destination) {
      $this->requestStack->getCurrentRequest()->query->remove('destination');
      $query['destination'] = $destination;
      // Get any query parameters from the destination.
      // @todo - added to pass parent query param for add child to books but needs more needs attention.
      $query += Drupal\Component\Utility\UrlHelper::parse($destination)['query'];
    }
    elseif ($destination = $template->getDestinationAfterNewFromTemplate()) {
      $query['destination'] = $destination;
    }

    return $this->redirect(
      $new_from_template_route_name,
      [
        'template' => $template->id(),
        $source->getEntityType()->getBundleEntityType() => $source->bundle(),
      ],
      ['query' => $query]
    );
  }

  /**
   * New from template page route controller.
   *
   * Return a list to select a template of a particular type to use.
   *
   * @param \Drupal\template_entities\Entity\TemplateTypeInterface $template_type
   *
   * @return array
   *   Entity form.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function newFromTemplatePage(TemplateTypeInterface $template_type) {
    return $this->addPage($template_type);
  }

  /**
   * Displays add content links for available templates.
   *
   * Redirects to node/add/[type] if only one content type is available.
   *
   * @param \Drupal\template_entities\Entity\TemplateTypeInterface $templateType
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the node types that can be added; however,
   *   if there is only one node type defined for the site, the function
   *   will return a RedirectResponse to the node add page for that one node
   *   type.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addPage(TemplateTypeInterface $templateType) {
    $build = [
      '#theme' => 'template_content_add_list',
    ];

    $bundle = FALSE;

    if (Drupal::routeMatch()->getRawParameters()->count() == 1) {
      // Bundle in the path.
      $bundle = Drupal::routeMatch()
        ->getRawParameters()
        ->getIterator()
        ->current();
    }

    $content = [];

    // Only use node types the user has access to.
    foreach ($this->entityTypeManager()
               ->getStorage('template')
               ->loadByProperties(['type' => $templateType->id()]) as $template) {
      $sourceEntity = $template->getSourceEntity();
      if (!$bundle || $bundle === $sourceEntity->bundle()) {
        $access = $this->entityTypeManager()
          ->getAccessControlHandler('template')
          ->access($template, 'new_from_template', NULL, TRUE);

        if ($access->isAllowed()) {
          $content[$template->id()] = $template;
        }
      }
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * New from template route controller.
   *
   * Return an entity form with a cloned (but not-yet persisted) entity.
   *
   * @param \Drupal\template_entities\Entity\TemplateInterface $template
   *
   * @return array
   *   Entity form.
   */
  public function newFromTemplate(TemplateInterface $template) {
    $source = $template->getSourceEntity();
    $duplicate = $template->getTemplatePlugin()->duplicateEntity($source, $template);

    $context = [
      'template' => $template,
      'template_type' => $template->bundle(),
    ];

    Drupal::moduleHandler()->alter(
      ['template_entities_new', 'template_entities_new_' . $template->bundle()]
      , $duplicate, $context
    );

    return $this->entityFormBuilder()->getForm($duplicate);
  }

  /**
   * @param \Drupal\template_entities\Entity\TemplateInterface $template
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function newFromTemplateTitle(TemplateInterface $template) {
    /** @var \Drupal\Core\Entity\EntityInterface $source */
    $source = $template->get('template_entity_id')->entity;

    $label = $source->getEntityType()->getLabel();
    return $this->t('Create @entity_type_label from template @template_label', [
      '@entity_type_label' => $label,
      '@template_label' => $template->label(),
    ]);
  }

  /**
   * Render a page with a list templates that use the route entity as a source.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *
   * @return array
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function templates(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getParameter('entity_type_id');
    /** @var EntityInterface $entity */
    $entity = $route_match->getParameter($parameter_name);
    $label = $entity->getEntityType()->getLabel();

    $output = [];

    // @todo - add exclusive functionality.
    $output['message'] = [
      '#markup' => $this->t('This @entity_type_label is used as a template @entity_type_label by the following templates:', ['@entity_type_label' => $label]),
    ];

    /** @var \Drupal\template_entities\TemplateManagerInterface $template_manager */
    $template_manager = Drupal::service('template_entities.manager');

    $templates = $template_manager->getTemplatesForEntity($entity);

    $output['templates'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Template'),
      ],
      '#rows' => [],
    ];

    /** @var \Drupal\template_entities\Entity\Template $template */
    foreach ($templates as $template) {
      $output['templates']['#rows'][] = [
        'template' => $template->toLink(),
      ];
    }

    return $output;
  }

  /**
   * Provides an add title callback templates.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_parameter
   *   The name of the route parameter that holds the bundle.
   *
   * @return string
   *   The title for the entity add page, if the bundle was found.
   */
  public function addBundleTitle(RouteMatchInterface $route_match, $entity_type_id, $bundle_parameter) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    // If the entity has bundle entities, the parameter might have been upcasted
    // so fetch the raw parameter.
    $bundle = $route_match->getRawParameter($bundle_parameter);
    return $this->t('Add @bundle template', ['@bundle' => $bundles[$bundle]['label']]);
  }

}
