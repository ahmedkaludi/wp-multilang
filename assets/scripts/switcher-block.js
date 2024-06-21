jQuery(document).ready(function($){
	if($('.wp-block-wpm-language-switcher').length > 0 && $('.wp-block-wpm-language-switcher .wpm-language-switcher').length > 0){
		let switcherDiv = $('.wp-block-wpm-language-switcher')
		let findUl = $(switcherDiv).find('ul');

		let selectSwitcher = switcherDiv.find('.switcher-select');

		if(findUl.length > 0 || selectSwitcher.length > 0){

			let nonce = wpm_localize_data.wpm_block_switch_nonce;

			$.ajax({
                type: 'POST',
                url: wpm_localize_data.ajax_url,
                dataType: "json",
                data: {
                    action: 'wpm_block_lang_switcher',
                    current_url: wpm_localize_data.current_url,
                    security:wpm_localize_data.wpm_block_switch_nonce
                },
                success:function(response){ 
                	if(findUl.length > 0){
			            $('.wp-block-wpm-language-switcher .wpm-language-switcher a').each(function(i, e){
							var lang = $(this).data('lang');

							let langUrl = '';
							
							$.each(response, function(bi, be){
								if(lang == bi){
									langUrl = be;
								}
							});

							$(this).attr('href', langUrl);
						});
					}

					if(selectSwitcher.length > 0){
						$('.wp-block-wpm-language-switcher .wpm-language-switcher option').each(function(i, e){
							var lang = $(this).data('lang');

							let langUrl = '';
							
							$.each(response, function(bi, be){
								if(lang == bi){
									langUrl = be;
								}
							});

							$(this).attr('value', langUrl);

						});
					}     
                }
            });
		}
	}

	$(document).on('change', '.wp-block-wpm-language-switcher .switcher-select', function(e){
		let selectedOpt = $(this).val();
		window.location.href = selectedOpt;
	});
});