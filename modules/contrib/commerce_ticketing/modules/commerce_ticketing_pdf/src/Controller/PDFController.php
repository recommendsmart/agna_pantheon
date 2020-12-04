<?php

namespace Drupal\commerce_ticketing_pdf\Controller;

use Drupal\commerce_ticketing\CommerceTicketInterface;
use Drupal\commerce_ticketing\Controller\TicketController;
use Drupal\commerce_ticketing_pdf\PDF\BasePdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Provides the custom action for PDF generation.
 */
class PDFController extends TicketController {

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function renderPdf(CommerceTicketInterface $ticket) {

    // Check available view_modes.
    $view_modes = $this->entityDisplayRepository->getViewModeOptions('commerce_ticket');
    $view_mode = !empty($view_modes['pdf']) ? 'pdf' : 'full';

    $build = $this->buildTicket($ticket, $view_mode);
    $output = $html = $this->renderer->renderRoot($build);

    $pdf = new BasePdf();
    $pdf->AddPage('P', 'A4');
    $pdf->setPrintHeader(FALSE);
    $pdf->setPrintFooter(FALSE);
    $pdf->SetMargins(10, 10, 10, TRUE);

    $html = '<style>' . file_get_contents(drupal_get_path('module', 'commerce_ticketing_pdf') . '/css/ticket.css') . '</style>' . $html;

    // Insert the HTML.
    $pdf->writeHTML($html);

    // set style for barcode
    $style = [
      'border' => 0,
      'vpadding' => 'auto',
      'hpadding' => 'auto',
      'fgcolor' => [0, 0, 0],
      'bgcolor' => FALSE, //array(255,255,255)
      'module_width' => 1, // width of a single module in points
      'module_height' => 1, // height of a single module in points
    ];

    // QRCODE,H : QR-CODE Best error correction.
    $qrcode_size = 42.8;
    $pdf->write2DBarcode($ticket->uuid(), 'QRCODE,H', 14.5, 40.2, $qrcode_size, $qrcode_size, $style, 'N');

    $module_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'commerce_ticketing');
    $pdf->ImageSVG($module_path . '/images/ticket.svg', 10, 8, 190, 100, '', '', 'N');
    $pdf->ImageSVG($module_path . '/images/logo.svg', 167, 21, 22, 10, '', '', 'N');

    $context = [
      'ticket' => $ticket,
      'changed_html' => $html,
      'default_output' => $output,
    ];
    // Let other modules alter the the PDF.
    $this->moduleHandler->alter('commerce_ticketing_pdf', $pdf, $context);

    $file_name = $ticket->uuid() . '.pdf';
    $pdf_file = $pdf->Output($file_name, 'S');
    $response = new Response($pdf_file);
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $file_name
    );
    $response->headers->set('Content-Disposition', $disposition);
    $response->headers->set('Content-type', 'application/pdf');

    return $response;

  }

  /**
   * Redirects to the URL with uuid.
   *
   * @param \Drupal\commerce_ticketing\CommerceTicketInterface $commerce_ticket
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function redirectPdf(CommerceTicketInterface $commerce_ticket) {
    return $this->redirect(
      'commerce_ticketing_pdf.download_pdf',
      [
        'ticket' => $commerce_ticket->uuid(),
      ]
    );
  }

}
