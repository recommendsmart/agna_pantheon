(function ($) {
  Drupal.behaviors.googlecharts = {
    attach: function (context, settings) {
      $.each(settings.googleCharts, function (selector) {
        $(selector, context).once('googleCharts').each(function () {
          // Check if table contains expandable hidden rows.
          var options = drupalSettings.googleCharts[selector]['options'];
          var type = drupalSettings.googleCharts[selector]['type'];
          var url = drupalSettings.googleCharts[selector]['options']['url'];
          if (!url) {
            google.charts.load("current", {packages: ["corechart"]});
            var dataTable = drupalSettings.googleCharts[selector]['data'];
            google.charts.setOnLoadCallback(drawChart);

            function drawChart() {
              var data = google.visualization.arrayToDataTable(dataTable);
              var view = new google.visualization.DataView(data);
              var chart = new google.visualization[type](document.getElementById(selector.replace(/#/i, "")));
              chart.draw(view, options);
            }
          } else {
            $.ajax({
              url: 'https://www.google.com/jsapi?callback',
              cache: true,
              dataType: 'script',
              success: function () {
                google.load('visualization', '1', {
                  packages: ['corechart'],
                  callback: function () {
                    $.ajax({
                      type: "GET",
                      dataType: "json",
                      data: {id: selector},
                      url: url,
                      success: function (jsonData) {
                        var data = google.visualization.arrayToDataTable(jsonData);
                        var chart = new google.visualization[type](document.getElementById(selector.replace(/#/i, "")));
                        chart.draw(data, options);
                      }
                    });
                  }
                });
                return true;
              }
            });
          }
        });
      });
    }
  };

})(jQuery);
