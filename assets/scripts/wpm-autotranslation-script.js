/**
 * Auto Translation related script
 * @since 2.4.11
 * */
jQuery(document).ready(function($){

	$(document).on('click', '#wpm_string_translation, #wpm_base_translation, #wpm_auto_slug_translation', function(e) {
		if($(this).is(':checked')){
			$(this).parent().after(proBtn);	
		}else{
			$('.wpm-upgrade-to-pro-note').remove();
		}
	});

});