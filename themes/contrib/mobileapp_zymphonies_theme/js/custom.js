/* --------------------------------------------- 
* Filename:     custom.js
* Version:      1.0.0 (2018-08-06)
* Website:      http://www.zymphonies.com
* Description:  Global Script
* Author:       Zymphonies Team
                info@zymphonies.com
-----------------------------------------------*/

function theme_menu(){

	//Main menu
	jQuery('#main-menu').smartmenus();
	
	//Mobile menu toggle
	jQuery('.navbar-toggle').click(function(){
		jQuery('.region-primary-menu').slideToggle();
	});

	//Mobile dropdown menu
	if ( jQuery(window).width() < 767) {
		jQuery(".region-primary-menu li a:not(.has-submenu)").click(function () {
			jQuery('.region-primary-menu').hide();
	    });
	}

	jQuery('.banner-buttons a').each(function(){

		if ( jQuery(this).attr('href') != '' ) {
			jQuery(this).show();
		}

	});

}

jQuery(document).ready(function($){
	theme_menu();
});