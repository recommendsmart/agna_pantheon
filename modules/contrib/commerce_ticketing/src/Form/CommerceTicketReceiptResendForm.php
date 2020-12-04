<?php

namespace Drupal\commerce_ticketing\Form;

use Drupal\commerce_ticketing\Mail\TicketReceiptMail;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for resending ticket receipts.
 */
class CommerceTicketReceiptResendForm extends ContentEntityConfirmFormBase {

  /**
   * The ticket receipt mail.
   *
   * @var \Drupal\commerce_ticketing\Mail\TicketReceiptMail
   */
  protected $ticketReceiptMail;

  /**
   * CommerceTicketReceiptResendForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Component\Datetime\TimeInterface $time
   * @param \Drupal\commerce_ticketing\Mail\TicketReceiptMail $ticket_receipt_mail
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, TicketReceiptMail $ticket_receipt_mail) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->ticketReceiptMail = $ticket_receipt_mail;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('commerce_ticketing.ticket_receipt_mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to resend the receipt for ticket %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Resend ticket');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_ticketing\CommerceTicketInterface $ticket */
    $ticket = $this->entity;
    $result = $this->ticketReceiptMail->send($ticket);
    // Drupal's MailManager sets an error message itself, if the sending failed.
    if ($result) {
      $this->messenger()->addMessage($this->t('Ticket receipt resent.'));
    }
  }

}
