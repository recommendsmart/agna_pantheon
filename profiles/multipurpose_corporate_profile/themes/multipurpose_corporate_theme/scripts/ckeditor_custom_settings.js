(function ($) {
  $(document).ready(function() {
    if(typeof(CKEDITOR) !== 'undefined') {
      CKEDITOR.config.extraAllowedContent = 'span(*)';
      CKEDITOR.dtd.$removeEmpty.span = 0;
    };  
  });    
}(jQuery));