
/** 
	* Filename:     custom.js
	* Version:      1.0.0 (20 Sep 2019)
	* Website:      https://www.zymphonies.com
	* Description:  Global script
	* Author:		Zymphonies team
					info@zymphonies.com
**/

function themeMenu(){

	// Main menu
	jQuery('#main-menu').smartmenus();

	jQuery('.navbar-toggle').click(function(){
		jQuery('.main-container').toggleClass('expand-menu');
	});

	// Mobile dropdown menu
	if ( jQuery(window).width() < 767) {
		jQuery(".region-primary-menu li a:not(.has-submenu)").click(function () {
			jQuery('.region-primary-menu').hide();
		});
	}

}

function themeHome(){
	jQuery('.flexslider').flexslider({
    	animation: "slide"	
    });
}

jQuery(document).ready(function($){
	themeMenu();
	themeHome();
});