<?php

namespace Drupal\commerce_ticketing\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_ticketing\CommerceTicketInterface;
use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides ticket related controller actions.
 */
class TicketController extends EntityController {

  /**
   * @var \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
   */
  protected $attachmentProcessor;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * TicketController constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityRepositoryInterface $entity_repository, RendererInterface $renderer, TranslationInterface $string_translation, UrlGeneratorInterface $url_generator, AttachmentsResponseProcessorInterface $attachment_processor, EntityDisplayRepositoryInterface $entity_display_repository, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type_manager, $entity_type_bundle_info, $entity_repository, $renderer, $string_translation, $url_generator);
    $this->attachmentProcessor = $attachment_processor;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('renderer'),
      $container->get('string_translation'),
      $container->get('url_generator'),
      $container->get('html_response.attachments_processor'),
      $container->get('entity_display.repository'),
      $container->get('module_handler')
    );
  }

  /**
   * Redirects to the ticket add form.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The commerce order to add a ticket to.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the ticket add page.
   */
  public function addTicketPage(OrderInterface $commerce_order) {
    $order_type = $this->entityTypeManager->getStorage('commerce_order_type')->load($commerce_order->bundle());
    // Find the ticket type associated to this order type.
    $ticket_type = $order_type->getThirdPartySetting('commerce_ticketing', 'ticket_type', 'default');

    return $this->redirect('entity.commerce_ticket.add_form', [
      'commerce_order' => $commerce_order->id(),
      'commerce_ticket_type' => $ticket_type,
    ]);
  }

  public function collectionRedirect(CommerceTicketInterface $commerce_ticket) {
    return $this->redirect('entity.commerce_ticket.collection', [
      'commerce_order' => $commerce_ticket->getOrderId(),
    ]);
  }

  public function renderTicket(CommerceTicketInterface $uuid) {
    $build = $this->buildTicket($uuid);
    return $this->renderBarePage([], 'Ticket', 'commerce_ticket', $build);

  }

  protected function buildTicket(CommerceTicketInterface $ticket, $view_mode = 'full') {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('commerce_ticket');
    $build = $view_builder->view($ticket, $view_mode);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function renderBarePage(array $content, $title, $page_theme_property, array $page_additions = []) {
    $attributes = [
      'class' => [
        str_replace('_', '-', $page_theme_property),
      ],
    ];
    $html = [
      '#type' => 'html',
      '#attributes' => $attributes,
      'page' => [
        '#type' => 'page',
        '#theme' => $page_theme_property,
        '#title' => $title,
        'content' => $content,
      ] + $page_additions,
    ];

    // current maintenance theme.
    $this->renderer->renderRoot($html);

    $response = new HtmlResponse();
    $response->setContent($html);
    // Process attachments, because this does not go via the regular render
    // pipeline, but will be sent directly.
    $response = $this->attachmentProcessor->processAttachments($response);
    return $response;
  }

}
