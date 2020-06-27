(function ($) {
  var unchecked = function() {
    $('.js-form-item-tid-raw input:not(:checked) + label').css('display', 'none');
    $('.js-form-item-tid-raw input:checked').click(checked);
  }
  var checked = function() {
    $('.js-form-item-tid-raw label').css('display', 'block');
    $('.js-form-item-tid-raw input:checked').click(unchecked);
  }
  var display_menu = function() {
    $('#block-brainstorm-theme-main-menu ul').addClass('display');
    $('#block-brainstorm-theme-main-menu').click(hidden_menu);
  }
  var hidden_menu = function() {
    $('#block-brainstorm-theme-main-menu ul').removeClass('display');
    $('#block-brainstorm-theme-main-menu').click(display_menu);
  }
  var widthScreen = document.documentElement.clientWidth;
  window.onresize = function() {
    widthScreen = document.documentElement.clientWidth;

    if (widthScreen < 480) {
      unchecked();
    }
    else {
      $('.js-form-item-tid-raw label label').css('display', 'block');
    }
  };
  if ($(".view-portfolio .js-form-item-nid:first-child input").prop('checked')) {
    $(".views-element-container").removeClass('two-columns');
  }
  if ($(".view-portfolio .js-form-item-nid:last-child").prop('checked')) {
    $(".views-element-container").addClass('two-columns');
  }
  $(document).ajaxSend(function(){
    $('.ajax-progress').addClass('ajax-progress-throbber').html('<svg id="load" x="0px" y="0px" viewBox="0 0 150 150"><circle id="loading-inner" cx="75" cy="75" r="60"/></svg>');
  });
  $(document).ajaxStop(function() {
    if (widthScreen <= 480) {
      unchecked();
    }
    if ($("#edit-type-1 .form-item:last-child input").prop("checked")) {
      $("#content .view-portfolio").addClass('two-columns');
    }
    if ($(".view-portfolio .js-form-item-nid:first-child input").prop('checked')) {
      $(".views-element-container").removeClass('two-columns');
    }
    if ($(".view-portfolio .js-form-item-nid:last-child input").prop('checked')) {
      $(".views-element-container").addClass('two-columns');
    }
  });

  $(window).bind('load', function() {
    if ($(".view-blog .item-list ul").length) {
      $(".view-blog .item-list ul").waterfall({
        colMinWidth: 350,
        defaultContainerWidth: 400,
      });
    }
  });
  $(document).ready(function() {
    var el = $('.skills-bar-container');
    if (!$.isEmptyObject(el.offset())) {
      var pageHeight = el.offset().top;
      var viewportHeight = document.documentElement.clientHeight;
      var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
      if ((scrollTop + viewportHeight) > pageHeight) {
        $('.percent').each(function(){
          $(this).next().children().css('width', $(this).html());
          $(this).css('padding-right', 100 - $(this).html().split('%')[0] + '%');
          $(this).css('opacity', 1);
        });
      }
      else {
        $('.percent').each(function(){
          $(this).next().children().css('width', 0);
          $(this).css('padding-right', 0);
          $(this).css('opacity', 0);
        });
      }
    }
    $('.messages + .close').click(function(){
      $(this).parent().css('display', 'none');
    });
    $('#block-brainstorm-theme-main-menu').click(display_menu);
    if (widthScreen < 480) {
      unchecked();
    }

  });
  window.onscroll = function() {
    var el = $('.skills-bar-container');
    if (!$.isEmptyObject(el.offset())) {
      var pageHeight = el.offset().top;
      var viewportHeight = document.documentElement.clientHeight;
      var scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
      if ((scrollTop + viewportHeight) > pageHeight) {
        $('.percent').each(function(){
          $(this).next().children().css('width', $(this).html());
          $(this).css('padding-right', 100 - $(this).html().split('%')[0] + '%');
          $(this).css('opacity', 1);
        });
      }
      else {
        $('.percent').each(function(){
          $(this).next().children().css('width', 0);
          $(this).css('padding-right', 0);
          $(this).css('opacity', 0);
        });
      }
    }
  };
})(jQuery);
