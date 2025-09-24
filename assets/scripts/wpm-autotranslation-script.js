/**
 * Auto Translation related script
 * @since 2.4.11
 * */
jQuery(document).ready(function($){
	
	let proBtn = '<span class="wpm-upgrade-to-pro-note" style="margin-left: 50px; font-weight: 500;"> This Feature requires the <a href="https://wp-multilang.com/pricing/#pricings" target="__blank">Premium Version</span>';

	$(document).on('change', '.wpm-free-translation-cb', function(e){
		e.preventDefault();
		if($(this).is(':checked')){
			$("label[for='" + $(this).attr('id') + "']").after(proBtn);
			
			// Show exclude section if it exists for this checkbox
			var excludeWrapper = $(this).closest('.wpm-auto-translate-item').find('.exclude-wrapper');
			if (excludeWrapper.length > 0) {
				excludeWrapper.show();
			}
		}else{
			$('.wpm-upgrade-to-pro-note').remove();
			
			// Hide exclude section if it exists for this checkbox
			var excludeWrapper = $(this).closest('.wpm-auto-translate-item').find('.exclude-wrapper');
			if (excludeWrapper.length > 0) {
				excludeWrapper.hide();
			}
		}
	});

	$(document).on('click', '#wpmpro-translate', function(e){
		$(this).css('display', 'inline');
		$('.wpm-upgrade-to-pro-note').remove();
		$(this).after(proBtn);	
	});

	$(document).on('click', '#wpm_string_translation, #wpm_base_translation, #wpm_auto_slug_translation', function(e) {
		if($(this).is(':checked')){
			$(this).parent().after(proBtn);	
		}else{
			$('.wpm-upgrade-to-pro-note').remove();
		}
	});

});