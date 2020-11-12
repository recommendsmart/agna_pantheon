<?php
/**
 * @file
 * Hooks specific to the Arch order module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter mail params before build content.
 *
 * @param array $token_params
 *   Token params passed to Token::replace() method.
 * @param array $context
 *   Mail context data.
 */
function hook_arch_order_mail_params_alter(array &$token_params, array &$context) {
  // @todo Add example.
}

/**
 * @} End of "addtogroup hooks".
 */
