/**
 * Auto Translation related script
 * @since 2.4.11
 * */
jQuery(document).ready(function($){
	
	let proBtn = '<span class="wpm-upgrade-to-pro-note" style="margin-left: 50px;"> This Compatibility requires the <a href="https://wp-multilang.com/pricing/" target="__blank">Premium Version</span>';

	$(document).on('change', '.wpm-free-translation-cb', function(e){
		e.preventDefault();
		if($(this).is(':checked')){
			$("label[for='" + $(this).attr('id') + "']").after(proBtn);
		}else{
			$('.wpm-upgrade-to-pro-note').remove();
		}
	});

});