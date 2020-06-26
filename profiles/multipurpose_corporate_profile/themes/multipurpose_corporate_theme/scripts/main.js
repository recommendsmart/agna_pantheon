(function ($) {
  // main slider
  Drupal.behaviors.mainOwlCarousel = {
    attach : function(context, settings) {
      var callbacks = {
        afterInit: mainSliderInit, 
        beforeMove: mainSliderBMove, 
        afterMove: mainSliderAMove, 
        addClassActive: true
      };
     	for (var carousel in settings.owlcarousel) {
          if( carousel.indexOf('owl-carousel-page') >= 0){
          $.extend(true, settings.owlcarousel[carousel].settings, callbacks);
	}
      }

      $sliderImg = $('.view-main-slider .views-field-field-background-image');

      $sliderImg.each(function() {
        if($(this).find('img').length == 0) {
          $(this).find('.field-content').css('background', '#1d374d');
        }
      });
    }
  };

  function mainSliderInit() {
    if(window.innerWidth >= 768) {
      $('.active .main-slider-text-wrapper').addClass('animated fadeInLeft');
      $('.active .main-slider-image img').addClass('animated zoomIn');
    } else {
      $('.active .main-slider-text-wrapper').addClass('animated fadeInUp');
    }
  }

  function mainSliderBMove() {
    if(window.innerWidth >= 768) {
      $('.active .main-slider-text-wrapper').removeClass('animated fadeInLeft');
      $('.active .main-slider-image img').removeClass('animated zoomIn');
    } else {
      $('.active .main-slider-text-wrapper').removeClass('animated fadeInUp');
    }
  }

  function mainSliderAMove() {
    if(window.innerWidth >= 768) {
      $('.active .main-slider-text-wrapper').addClass('animated fadeInLeft');
      $('.active .main-slider-image img').addClass('animated zoomIn');
    } else {
      $('.active .main-slider-text-wrapper').addClass('animated fadeInUp');
    }
  }


  $( document ).ready(function() {
    if (window.location.pathname == '/portfolio' || window.location.pathname == '/portfolio/column_three' || window.location.pathname == '/portfolio/column_four') {
      $('.view-portfolio-terms .view-header a').addClass('active');
    }
    else {
      $('.view-portfolio-terms .view-header a').removeClass('active');
    }
  });

  // Add 'active' class to superfish menu link
  var path = window.location.pathname.split('/');
  path = path[path.length-1];
  if (path !== undefined) {
    $("ul.sf-menu")
        .find("a[href$='" + path + "']") // gets all links that match the href
        .parents('li')  // gets all list items that are ancestors of the link
        .children('a')  // walks down one level from all selected li's
        .addClass('active');
  }

})(jQuery);
