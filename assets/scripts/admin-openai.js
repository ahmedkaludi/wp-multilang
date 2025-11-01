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
		const provider = $('#wpm-openai-provider').val();
		const secretKey = rawSecretKey.trim();
		if ( secretKey.length === 0 ) {
			$('#wpm-secret-key-error').show();
			return;
		}

		$('#wpm-secret-key-error').hide();
		$('.wpm-openai-api-success-note').hide();
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
					$('#wpm-openai-models').html(optionsHtml);
					$('.wpm-openai-api-success-note').show();
					$('.wpm-openai-api-success-note').text( response.data.message );
				} else {
					if ( response.data && response.data.message ) {
						$('.wpm-openai-api-success-note').show();
						$('.wpm-openai-api-success-note').text( response.data.message );
					}	
				}
			}
		});
	});

	$(document).on('change', '#wpm-openai-provider', function(e) {
		e.preventDefault();
		$('#wpm-secret-key-error').hide();
		$('.wpm-openai-api-success-note').hide();
		$('.wpm-openai-provider-note').hide();
		if ( $('#wpm-openai-secretkey').length > 0 ) {
			$('#wpm-openai-secretkey').val('');
		}
		if ( $('#wpm-openai-models').length > 0 ) {
			$('#wpm-openai-models').html('<option value="">Models Not Available</option>');
		}
		const provider = $(this).val();

		if ( provider === 'multilang') {
			$('.wpm-hide-openai-wrapper').hide();
			if ( ! wpm_openai_params.is_pro_active ) {
				$('.wpm-openai-provider-note').show();
				$('.wpm-license-error-note').html(wpmpProBtn);
			}else if(wpm_openai_params.is_pro_active && wpm_openai_params.license_status !== 'active'){
				$('.wpm-openai-provider-note').show();
				$('.wpm-license-error-note').html(wpmLicenseKeyError);
			}else{
				$('.wpm-openai-provider-note').hide();
			}
		}else{
			
			if ( wpm_openai_params.ai_settings.model.length === 0 ) {
				$('.wpm-license-error-note').html(wpmOpenAINote);	
				$('.wpm-license-error-note').show();
			}
			$('.wpm-hide-openai-wrapper').show();
			$('.wpm-openai-provider-note').hide();
		}

		$('#wpmpro-what-all-opt').prop('checked', false).trigger('change');
		$('.wpmpro-language-cb').prop('checked', false).trigger('change');
	
	}); 

	$(document).on('click', '#wpm-save-openai-settings', function(e) {
		e.preventDefault();

		const provider = $('#wpm-openai-provider').val().trim();
		const model = $('#wpm-openai-models').val().trim();
		console.log('provider ', provider);
		console.log('model ', model);

		if ( provider === 'multilang' && ! wpm_openai_params.is_pro_active ) {
			return;
		}

		if ( provider === 'multilang' || provider === 'openai' ) {
			if ( provider === 'openai' &&  model.length === 0 ) {
				alert('Please validate api key and select model');
				return;
			}

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {action: 'wpm_save_openai_settings', provider: provider, model: model, security: wpm_openai_params.wpmpro_openai_nonce},
				success: function(response) {
					window.location.reload(true);
				}
			});

		}else{
			alert('Please select provider');
			return;
		}
	})

});