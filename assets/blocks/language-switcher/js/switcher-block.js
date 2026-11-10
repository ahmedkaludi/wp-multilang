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

                	// Handle Dropdown switcher: dynamically update active language and links
                	$('.wpm-language-switcher.wpm-switcher-dropdown, .wpm-language-switcher .wpm-switcher-dropdown, .wpm-switcher-dropdown').each(function(){
                		let $switcher = $(this).is('ul') ? $(this) : $(this).find('ul.wpm-switcher-dropdown');
                		let $mainLi = $switcher.children('li.wpm-item-language-main');
                		if(!$mainLi.length){
                			$mainLi = $switcher.find('li.wpm-item-language-main');
                		}
                		if(!$mainLi.length){
                			return;
                		}

                		let $mainSpan = $mainLi.children('span');
                		let $dropdownUl = $mainLi.children('ul.wpm-language-dropdown');

                		// Extract current main language code
                		let mainLang = $mainSpan.attr('data-lang') || '';
                		if(!mainLang){
                			let classes = ($mainLi.attr('class') || '').split(/\s+/);
                			for(let i = 0; i < classes.length; i++){
                				if(classes[i].indexOf('wpm-item-language-') === 0 && classes[i] !== 'wpm-item-language-main'){
                					mainLang = classes[i].replace('wpm-item-language-', '');
                					break;
                				}
                			}
                		}

                		// If main language does not match the active site language, swap them
                		if(currentLang && mainLang && mainLang !== currentLang){
                			let $targetLi = $dropdownUl.children('li.wpm-item-language-' + currentLang);
                			if(!$targetLi.length){
                				$targetLi = $dropdownUl.children('li').filter(function(){
                					return $(this).find('a').attr('data-lang') === currentLang || $(this).find('a').data('lang') === currentLang;
                				});
                			}

                			if($targetLi.length){
                				let $targetA = $targetLi.find('a');
                				let targetHtml = $targetA.html();
                				let mainHtml = $mainSpan.html();

                				// Update main displayed language
                				$mainSpan.html(targetHtml).attr('data-lang', currentLang);
                				$mainLi.removeClass('wpm-item-language-' + mainLang).addClass('wpm-item-language-' + currentLang);

                				// Move previous main language into the dropdown list
                				$targetLi.removeClass('wpm-item-language-' + currentLang).addClass('wpm-item-language-' + mainLang);
                				$targetA.attr('data-lang', mainLang).data('lang', mainLang).html(mainHtml);
                			}
                		} else if(currentLang && !mainLang){
                			$mainSpan.attr('data-lang', currentLang);
                		}

                		// Update URLs in the dropdown list
                		$dropdownUl.find('a').each(function(){
                			let lang = $(this).attr('data-lang') || $(this).data('lang');
                			let langUrl = (response && response[lang]) ? response[lang] : '';
                			$(this).attr('href', langUrl);
                		});
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

	$(document).on('change', '.wpm-language-switcher.wpm-switcher-select, .wpm-language-switcher .wpm-switcher-select', function(e){
		let selectedOpt = $(this).val();
		window.location.href = selectedOpt;
	});
});