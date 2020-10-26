// Global javascript (loaded on all pages in Pattern Lab and Drupal)
// Should be used sparingly because javascript files can be used in components
// See https://github.com/fourkitchens/rocketship_fix_base_8/wiki/Drupal-Components#javascript-in-drupal for more details on using component javascript in Drupal.

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it with an "anonymous closure". See:
// - https://drupal.org/node/1446420
// - http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth

/**
 * Cooldrops UI JS
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

  Drupal.behaviors.rocketshipUIf014 = {
    attach: function (context, settings) {

      var stickyBar = $('.event__bar--full', context);

      if (stickyBar.length) {
        self.stickyBar(stickyBar, context);
      }

    }
  };

  ///////////////////////////////////////////////////////////////////////
  // Behavior for Tabs: functions
  ///////////////////////////////////////////////////////////////////////

  /**
   * Sticky events meta info bar (under photo)
   *
   */
  self.stickyBar = function(el, context) {

    var toolBar = $('#toolbar-bar', context),
        toolBarTray = $('#toolbar-item-administration-tray', context);

    var screenCheck = function() {
      if (self.screen !== 'xs') {
        self.handleStickyBar(el, toolBar, toolBarTray);
      } else {
        if (el.hasClass('js-sticky')) {
          el.removeClass('js-sticky');
        }
      }
    };

    screenCheck();

    // on window resize, check if not on mobile
    self.optimizedResize().add(function() {
      screenCheck();
    });

  };

  self.handleStickyBar = function(el, toolBar, toolBarTray) {

    var offset = 0,
      toolBarHeight = 0,
      toolBarTrayHeight = 0;

    // if there is an admin toolbar
    // there will need to be an offset
    if (toolBar.length) {
      toolBarHeight = toolBar.outerHeight();
      toolBarTrayHeight = toolBarTray.outerHeight();
      offset = toolBarHeight + toolBarTrayHeight;
    }

    var waypoints = el.waypoint({
      handler: function (direction) {

        if (!el.hasClass('js-sticky') && direction === 'down') {
          el.addClass('js-sticky');
        }

        if (el.hasClass('js-sticky') && direction === 'up') {
          el.removeClass('js-sticky');
        }

      }
    }, {
      offset: offset
    });


  };

  })(jQuery, Drupal, window, document);
