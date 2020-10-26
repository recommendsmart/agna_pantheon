// Global javascript (loaded on all pages in Pattern Lab and Drupal)
// Should be used sparingly because javascript files can be used in components
// See https://github.com/fourkitchens/rocketship_fix_base_8/wiki/Drupal-Components#javascript-in-drupal for more details on using component javascript in Drupal.

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it with an "anonymous closure". See:
// - https://drupal.org/node/1446420
// - http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth

/**
 * Rocketship UI JS
 *
 * contains: triggers for functions
 * Functions themselves are split off and grouped below each behavior
 *
 * Drupal behaviors:
 *
 * Means the JS is loaded when page is first loaded
 * + during AJAX requests (for newly added content)
 * use jQuery's "once" to avoid processing the same element multiple times
 * http: *api.jquery.com/one/
 * use the "context" param to limit scope, by default this will return document
 * use the "settings" param to get stuff set via the theme hooks and such.
 *
 *
 * Avoid multiple triggers by using jQuery Once
 *
 * EXAMPLE 1:
 *
 * $('.some-link', context).once('js-once-my-behavior').click(function () {
 *   // Code here will only be applied once
 * });
 *
 * EXAMPLE 2:
 *
 * $('.some-element', context).once('js-once-my-behavior').each(function () {
 *   // The following click-binding will only be applied once
 * * });
 */

(function ($, Drupal, window, document) {

  "use strict";

  // set namespace for frontend UI javascript
  if (typeof window.rocketshipUI == 'undefined') { window.rocketshipUI = {}; }

  var self = window.rocketshipUI;

  ///////////////////////////////////////////////////////////////////////
  // Cache variables available across the namespace
  ///////////////////////////////////////////////////////////////////////


  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: triggers
  ///////////////////////////////////////////////////////////////////////

  Drupal.behaviors.rocketshipUI_f012 = {
    attach: function (context, settings) {

      var filters = $('.panel-layout__sidebar--left .block-facets', context);
      if (filters.length) self.filtersCollapsible(filters);

    }
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: functions
  ///////////////////////////////////////////////////////////////////////

  /*
   *
   * Open/close Facet filters
   * Closed by default
   */
  self.filtersCollapsible = function (filters) {

    filters.once('js-once-filters-collapsible').each(function () {

      var filter = $(this),
        trigger = filter.find('.h2'),
        target = filter.find('[class*="facets-widget-job_links"], .facets-widget-dropdown');

      // alternative field names for faq title
      if (typeof trigger == 'undefined' || trigger.length < 1) {
        trigger = filter.find('.h3:first-child');
      }

      trigger.on('click', function () {

        // close item
        if (filter.hasClass('js-open')) {
          filter.removeClass('js-open');
          // target.stop( true, true ).slideUp(250, function () {
          //   //callback
          // });
          // open item
        } else {
          filter.addClass('js-open');
          // target.stop( true, true ).slideDown(250, function () {
          //   //callback
          // });

          // close all siblings
          filter.siblings().each(function () {
            var sibling = $(this);

            if (sibling.hasClass('js-open')) {
              sibling.removeClass('js-open');
              // sibling.find('[class*="facets-widget-job_links"]').stop( true, true ).slideUp(250, function () {
              //   //callback
              // });
            }
          });
        }

      });

    });

  };

})(jQuery, Drupal, window, document);
