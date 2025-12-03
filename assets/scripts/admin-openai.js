/**
 * Openai scripts
 * @since 2.4.23
 * */

jQuery(document).ready(function($){
	
	let wpmpProBtn = '<span class="wpm-upgrade-to-pro-note" style="font-weight: 500;"> This Feature requires the <a href="https://wp-multilang.com/pricing/#pricings" target="__blank">Premium Version</span>';
	let wpmOpenAINote = '<span class="wpm-upgrade-to-pro-note" style="font-weight: 500;"> Please configure OpenAI settings</span>';
	let wpmLicenseKeyError = '<span class="wpm-upgrade-to-pro-note" style="font-weight: 500;"> Your license key is inactive or expired</span>';

	// Validate openai key
	$(document).on('click', '#wpm-validate-openai-key', function(e) {
		e.preventDefault();
		const rawSecretKey = $('#wpm-openai-secretkey').val();
		const secretKey = rawSecretKey.trim();
		if ( secretKey.length === 0 ) {
			$('#wpm-secret-key-error').show();
			return;
		}
		provider = 'openai';

		$('#wpm-secret-key-error').hide();
		$('.wpm-openai-api-success-note').hide();
		$('.wpm-openai-api-error-note').hide();
		$('.wpm-openai-provider-note').hide();
		$(this).addClass('updating-message');
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {action: 'wpm_validate_secret_key', provider: provider, secret_key: secretKey, security: wpm_openai_params.wpmpro_openai_nonce},
			success: function(response) {
				$('#wpm-validate-openai-key').removeClass('updating-message');
				if ( response.success ) {
					const models 	=	response.data.models;

					let optionsHtml = '';
					$.each(models, function(index, value) {
						optionsHtml += `<option value="${value}">${value}</option>`;
					});
					$('#wpm-hide-openai-models-wrapper').show();
					$('#wpm-openai-models').html(optionsHtml);
					$('.wpm-openai-api-success-note').show();
					$('.wpm-openai-api-success-note').text( response.data.message );
				} else {
					if ( response.data && response.data.message ) {
						$('.wpm-openai-api-error-note').show();
						$('.wpm-openai-api-error-note').text( response.data.message );
					}	
				}
			}
		});
	}); 

	$(document).on('click', '#wpm-save-openai-settings', function(e) {
		e.preventDefault();

		$('#wpm-secret-key-error').hide();
		const provider = 'openai';
		let model = '';
		if ( $('#wpm-openai-models').length > 0 ) {
			model = $('#wpm-openai-models').val();
			if ( model ) {
				model = model.trim(); 
			}
		}
		let enabled = '0';
		if ( $('#wpm_openai_integration').is(':checked') ) {
			enabled = '1';
		}
		console.log('provider ', provider);
		console.log('model ', model);
		let $button = $('#wpm-save-openai-settings');

		if ( provider === 'openai' ) {
			// if ( model.length === 0 ) {
			// 	alert('Please validate api key and select model');
			// 	return;
			// }

			$($button).prop('disabled', true).text('Saving Changes...');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {action: 'wpm_save_openai_settings', provider: provider, model: model, wpm_openai_integration: enabled, security: wpm_openai_params.wpmpro_openai_nonce},
				success: function(response) {
					window.location.reload(true);
				}
			});


		}else{
			alert('Please select provider');
			return;
		}
	})

	$(document).on('click', '#wpm_openai_integration', function(e) {
		if($(this).is(':checked')) {
			const provider = 'openai';
			$('.wpm-openai-children').show();
			if( wpm_openai_params.ai_settings.model.length === 0) {
				$('#wpm-hide-openai-models-wrapper').hide();
			}
		}else{
			$('.wpm-openai-children').hide();
		}
	});

});