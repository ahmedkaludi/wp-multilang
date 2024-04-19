jQuery(document).ready(function($){
	let proBtn = '<a class="button wpm-upgrade-pro-btn wpm-pro-free-btn" style="background: #0099E7;color: #fff; border: none; font-weight: 500; margin-left: 20px;" href="https://wp-multilang.com/pricing/" target="_blank">Upgrade to PRO</a>';

	$(document).on('change', '.wpm_free_compatibilities', function(e){
		e.preventDefault();
		if($(this).is(':checked')){
			$(this).after(proBtn);
		}else{
			$('.wpm-pro-free-btn').remove();
		}
	});
});