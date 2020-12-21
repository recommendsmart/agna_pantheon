(function ($, drupalSettings) {

  /**
   * Update tab summaries when the title field changes.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.paragraphs_tabs_widgetVerticalTabs = {
    attach: function attach(context, settings) {
      // If there are vertical tab settings present in the document fragment
      // currently being attached to the page...
      if (settings.hasOwnProperty('paragraphs_tabs_widget_vertical_tabs')) {
        // Find each details element that represents a tab, and add a callback
        // to set its summary.
        $('details[data-paragraph-tabs-widget-tab-group]', context)
          .drupalSetSummary(function (context) {
            var currentTabSummarySelector;
            var tabsGroup = $(context).attr('data-paragraph-tabs-widget-tab-group');
            var summary = '';
            var rawSummary;
            // If the tab group is not empty, and we can find a matching tab
            // group in the paragraphs_tabs_widget_vertical_tabs settings, and
            // that tab group has a summary selector, then set this tab's
            // summary to the value of the first element chosen by that selector
            // (in the context of this tab).
            if (tabsGroup
              && settings.paragraphs_tabs_widget_vertical_tabs.hasOwnProperty(tabsGroup)
              && settings.paragraphs_tabs_widget_vertical_tabs[tabsGroup].hasOwnProperty('summarySelector')
              && settings.paragraphs_tabs_widget_vertical_tabs[tabsGroup].summarySelector
            ) {
              currentTabSummarySelector = settings.paragraphs_tabs_widget_vertical_tabs[tabsGroup].summarySelector;
              rawSummary = $(currentTabSummarySelector, context).val() || '';
              summary = Drupal.checkPlain(rawSummary);
            }

            return summary;
          });
      }

      // If there is a vertical tabs widget present in the document fragment
      // currently being attached to the page, then move the "add more" button
      // to the bottom of its tab menu.
      $('[data-paragraphs-tabs-widget-addmore-group]', context)
        .once('paragraphs_tabs_widget-move-addmore-button')
        .each(function (index, element) {
          var $addmoreButton = $(element);
          var $menu = $addmoreButton
            .closest('.vertical-tabs')
            .find('.vertical-tabs__menu');
          $addmoreButton.appendTo($menu);
        });
    }
  };

})(jQuery, drupalSettings);
