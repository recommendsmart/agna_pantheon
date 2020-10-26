/**
 * @file
 * Behaviors of the background color field.
 */

(function ($, _, Drupal, drupalSettings) {
    "use strict";

    Drupal.behaviors.varbaseBootstrapEventAdmin = {
        attach: function (context) {

          var group = $('.field--name-field-event-background-color.field--widget-options-buttons');

          group.addClass('p-field-bg-color');

          group.find('input:radio').each(function() {
              $(this).next('label').addClass($(this).val());
          });
        }
    };

})(window.jQuery, window._, window.Drupal, window.drupalSettings);
