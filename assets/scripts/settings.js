( function( $ ) {
  "use strict";

	$( function() {
		var changed = false;

		$( 'input, textarea, select, checkbox' ).change( function() {
			changed = true;
		});

		$( '.wpm-nav-tab-wrapper a' ).click( function() {
			if ( changed ) {
				window.onbeforeunload = function() {
				    return wpm_settings_params.nav_warning;
				};
			} else {
				window.onbeforeunload = '';
			}
		});

		$( '.submit input' ).click( function() {
			window.onbeforeunload = '';
		});

	});
	
	/**
	 * Code to hide newsletter of settings page
	 * */
	$(document).on('click', '.wpm_newsletter_hide', function(e){
		jQuery('.wpm-newsletter-wrapper').css("display", "none");
		var form = jQuery(this);
        jQuery.post(ajaxurl, {action:'wpm_newsletter_hide_form',wpm_admin_settings_nonce:wpm_settings_params.wpm_admin_settings_nonce},
          function(data) {}
        );
        return true;
	});

	/**
	 * Submit newsletter form
	 * */
	$(document).on('submit', '#wpm_settings_newsletter', function(e){
	// $('#wpm_settings_newsletter').submit(function(e){
		var form = jQuery(this);
        var email = form.find('input[name="newsletter-email"]').val();
        jQuery.post(ajaxurl, {action:'wpm_settings_newsletter_submit',email:email,wpm_admin_settings_nonce:wpm_settings_params.wpm_admin_settings_nonce},
          function(data) {}
        );
        return true;
	});

})( jQuery );
