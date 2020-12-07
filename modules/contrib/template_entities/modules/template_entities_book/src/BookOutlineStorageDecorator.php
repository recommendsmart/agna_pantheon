<?php

namespace Drupal\template_entities_book;

use Drupal\book\BookOutlineStorage;

/**
 * Defines a storage class for books outline.
 */
class BookOutlineStorageDecorator extends BookOutlineStorage {

  /**
   * {@inheritdoc}
   */
  public function getBooks() {
    // Replace standard method with one we can hook into.
    return $this->connection->select('book')
      ->distinct()
      ->fields('book', ['bid'])
      ->addTag('template_entities.get_books')
      ->execute()
      ->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function hasBooks() {
    // Replace standard method with one we can hook into.
    return (bool) $this->connection->select('book')
      ->countQuery()
      ->fields('book', ['bid'])
      ->addTag('template_entities.get_books')
      ->execute()
      ->fetchCol();
  }

}
