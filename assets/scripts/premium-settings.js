jQuery(document).ready(function($){
	let proBtn = '<span class="wpm-pro-free-btn"> This Compatibility requires the <a href="https://wp-multilang.com/pricing/" target="__blank">Premium Version</span>';

	$(document).on('change', '.wpm_free_compatibilities', function(e){
		e.preventDefault();
		if($(this).is(':checked')){
			$(this).after(proBtn);
		}else{
			$('.wpm-pro-free-btn').remove();
		}
	});
});