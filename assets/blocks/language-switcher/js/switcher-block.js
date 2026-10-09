jQuery(document).ready(function($){
	if($('.wpm-language-switcher').length > 0){
		let hasLinks = $('.wpm-language-switcher a').length > 0;
		let selectSwitcher = $('.wpm-language-switcher.wpm-switcher-select, .wpm-language-switcher .wpm-switcher-select');

		if(hasLinks || selectSwitcher.length > 0){

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
                	let currentLang = wpm_localize_data.current_lang || '';

                	// Handle List switcher: dynamically update active language and links
                	$('.wpm-language-switcher.wpm-switcher-list, .wpm-language-switcher .wpm-switcher-list').each(function(){
                		let listUl = $(this).is('ul') ? $(this) : $(this).find('ul');
                		listUl.children('li').each(function(){
                			let $li = $(this);
                			let $child = $li.children('a, span');
                			let lang = $child.data('lang');
                			let langUrl = (response && response[lang]) ? response[lang] : '';

                			if(currentLang && lang === currentLang){
                				$li.addClass('active');
                				if($child.is('a')){
                					// Current language should be a span (inactive link)
                					let $span = $('<span/>').attr('data-lang', lang).html($child.html());
                					$child.replaceWith($span);
                				}
                			} else {
                				$li.removeClass('active');
                				if($child.is('span')){
                					// Other languages should be clickable links
                					let $a = $('<a/>').attr('href', langUrl).attr('data-lang', lang).html($child.html());
                					$child.replaceWith($a);
                				} else if($child.is('a')){
                					$child.attr('href', langUrl);
                				}
                			}
                		});
                	});

                	// Handle Dropdown links (for any dropdown links in the switcher)
                	$('.wpm-language-switcher:not(.wpm-switcher-list) a, .wpm-switcher-dropdown a').each(function(i, e){
                		var lang = $(this).data('lang');
                		let langUrl = (response && response[lang]) ? response[lang] : '';
                		$(this).attr('href', langUrl);
                	});

					if(selectSwitcher.length > 0){
						$('.wpm-language-switcher option').each(function(i, e){
							var lang = $(this).data('lang');
							let langUrl = (response && response[lang]) ? response[lang] : '';
							$(this).attr('value', langUrl);
							if(currentLang && lang === currentLang){
								$(this).prop('selected', true);
							}
						});
					}     
                }
            });
		}
	}

	$(document).on('change', '.wpm-language-switcher .wpm-switcher-select', function(e){
		let selectedOpt = $(this).val();
		window.location.href = selectedOpt;
	});
});