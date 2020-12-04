<?php

namespace Drupal\commerce_ticketing\Mail;

use Drupal\commerce\MailHandlerInterface;
use Drupal\commerce_ticketing\CommerceTicketInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

class TicketReceiptMail {

  use StringTranslationTrait;

  /**
   * The mail handler.
   *
   * @var \Drupal\commerce\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * TicketReceiptMail constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\commerce\MailHandlerInterface $mail_handler
   *   Mail handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger.
   */
  public function __construct(
    MailHandlerInterface $mail_handler,
    ModuleHandlerInterface $module_handler,
    LoggerChannelInterface $logger
  ) {
    $this->mailHandler = $mail_handler;
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function send(CommerceTicketInterface $ticket, $to = NULL, $bcc = NULL) {

    $order = $ticket->getOrder();

    $to = isset($to) ? $to : $order->getEmail();
    if (!$to) {
      // The email should not be empty.
      return FALSE;
    }

    $subject = $this->t('Ticket for your order #@number', ['@number' => $order->getOrderNumber()]);
    $body = [
      '#theme' => 'commerce_ticket_receipt',
      '#order_entity' => $order,
      '#ticket' => $ticket,

    ];

    $params = [
      'id' => 'ticket_receipt',
      'from' => $order->getStore()->getEmail(),
      'bcc' => $bcc,
      'order' => $order,
    ];

    if ($this->moduleHandler->moduleExists('commerce_ticketing_pdf')) {
      $pdf_url = Url::fromRoute('commerce_ticketing_pdf.download_pdf', ['ticket' => $ticket->uuid()], ['absolute' => TRUE]);
      $params['files'][] = (object) [
        'filename' => "ticket.pdf",
        'uri' => $pdf_url->toString(),
        'filemime' => 'application/pdf',
      ];
    }

    $customer = $order->getCustomer();
    if ($customer->isAuthenticated()) {
      $params['langcode'] = $customer->getPreferredLangcode();
    }

    // Allow other modules to alter the ticket receipt mail.
    $mail_data = ['body' => $body, 'params' => $params];
    $this->moduleHandler->alter('ticket_receipt_mail', $mail_data, $ticket);
    $body = $mail_data['body'];
    $params = $mail_data['params'];

    $this->logger->debug('Sending mail "@subject"', ['@subject' => (string) $subject]);
    return $this->mailHandler->sendMail($to, $subject, $body, $params);
  }

}
