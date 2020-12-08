(function ($) {
  Drupal.behaviors.paragraphsTable = {
    attach: function (context, settings) {
      $.each(settings.paragraphsTable, function (selector) {
        $(selector, context).once('paragraphsTable').each(function () {
          var settings = drupalSettings.paragraphsTable[selector];
          $.getJSON( settings['url'], function( data ) {
            let row,tbody = [];
            // loop through all the rows, we will deal with tfoot and thead later
            $.each( data, function( rowIndex, valRow ) {
              row = $('<tr />');
              $.each( valRow, function( colIndex, valCol ) {
                row.append($('<td />').html(valCol));
              });
              tbody.push(row);
            });
            $('#' + settings['id'] ).append($('<tbody />').append(tbody));
          });
        });
      });
    }
  };

})(jQuery);
