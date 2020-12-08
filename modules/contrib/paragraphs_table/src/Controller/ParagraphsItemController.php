<?php


namespace Drupal\paragraphs_table\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Returns responses for paragraphs item routes.
 */
class ParagraphsItemController extends ControllerBase {

  /**
   * Provides the paragraphs item submission form.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraphs entity for the paragraph item.
   *
   * @param $host_type
   *   The type of the entity hosting the paragraph item.
   *
   * @param $host_id
   *   The id of the entity hosting the paragraph item.
   *
   * @return array
   *   A paragraph item submission form.
   *
   * TODO: additional fields
   */
  public function add(Paragraph $paragraph, $host_type, $host_id) {
    $host = $this->entityTypeManager()->getStorage($host_type)->load($host_id);
    $paragraphs_item = $this->entityTypeManager()
      ->getStorage('paragraphs_item')
      ->create([
        'field_name' => $paragraph->id(),
        'type' => $host_type,
        'revision_id' => 0,
      ]);

    $form = $this->entityFormBuilder()->getForm($paragraph);
    return $form;
  }

  /**
   * The _title_callback for the paragraphs_item.add route.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The current paragraphs_item
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(Paragraph $paragraph) {
    return $this->t('Create @label', ['@label' => $paragraph->label()]);
  }

  /**
   * Displays a paragraphs item.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The Paragraph item we are displaying.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(Paragraph $paragraph) {
    $build = $this->buildPage($paragraph);
    return $build;
  }
  /**
   * The _title_callback for the paragraphs_item.view route.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The current paragraphs_item.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(Paragraph $paragraph) {
    return \Drupal::service('entity.repository')->getTranslationFromContext($paragraph)->label();
  }

}
