<?php

namespace Drupal\arch_cart\Cart;

/**
 * Interface CartHandlerInterface.
 *
 * @package Drupal\arch_cart\Cart
 */
interface CartHandlerInterface {

  /**
   * Get cart instance.
   *
   * @param bool $force_read
   *   Force read.
   *
   * @return \Drupal\arch_cart\Cart\CartInterface
   *   Cart instance.
   */
  public function getCart($force_read = FALSE);

}
