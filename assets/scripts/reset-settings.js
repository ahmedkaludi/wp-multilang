/**
 * Js file for handling reset setting related operations
 * @since 2.4.15
 * */
jQuery(document).ready(function($){

	$(document).on('click', '#wpm-reset-settings-btn', function(e) {

		if ( confirm(wpm_reset_settings_params.wpm_confirm_reset ) ) {
			
			e.preventDefault();
			let button = $(this);

			let data = {
				action: 'wpm_reset_settings',
				security: wpm_reset_settings_params.wpm_reset_settings_nonce,
			}

			$.ajax({

				url: wpm_reset_settings_params.ajax_url,
				type: 'post',
				data: data,
				beforeSend: function() {
          			button.prop('disabled', true).after('<span class="spinner is-active"></span>');
        		},
				success: function( response ) {
					window.location.reload(true);
				}

			});
		}

	});

});