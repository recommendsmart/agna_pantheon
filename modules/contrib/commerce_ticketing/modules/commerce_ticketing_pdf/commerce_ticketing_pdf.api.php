<?php

use Drupal\commerce_ticketing\CommerceTicketInterface;
use Drupal\commerce_ticketing_pdf\PDF\BasePdf;

/**
 * Alter PDF content file.
 *
 * @param \Drupal\commerce_ticketing_pdf\PDF\BasePdf $pdf
 */
function hook_commerce_ticketing_pdf_alter(BasePdf &$pdf, CommerceTicketInterface $ticket) {
  // set style for barcode
  $style = [
    'border' => 2,
    'vpadding' => 'auto',
    'hpadding' => 'auto',
    'fgcolor' => [0, 0, 0],
    'bgcolor' => FALSE, //array(255,255,255)
    'module_width' => 1, // width of a single module in points
    'module_height' => 1, // height of a single module in points
  ];

  // QRCODE,H : QR-CODE Best error correction
  $pdf->write2DBarcode('test-code', 'QRCODE,H', 120, 20, 70, 70, $style, 'N');
}

/**
 * Alter PDF file settings.
 *
 * @param \Drupal\commerce_ticketing_pdf\PDF\BasePdf $pdf
 */
function hook_commerce_ticketing_pdf_settings_alter(BasePdf &$pdf, CommerceTicketInterface $ticket) {
  // For mor examples please consult the TCPDF documentation.
  $pdf->SetMargins(10, 10, 10, TRUE);
}

/**
 * Alter PDF html.
 *
 * @param \Drupal\commerce_ticketing_pdf\PDF\BasePdf $pdf
 */
function hook_commerce_ticketing_pdf_html_alter(&$html, CommerceTicketInterface $ticket) {
  // Add styles as this is the only way TCPDF will use inline styles.
  $html = '<style>' . file_get_contents(drupal_get_path('theme', 'mytheme') . '/dist/assets/ticket.css') . '</style>' . $html;
}
