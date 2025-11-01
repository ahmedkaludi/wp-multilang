<?php
/**
 * WP Multilang OpenAI
 * @since 	2.4.23
 */
namespace WPM\Includes\Admin;
use WPM\Includes\Admin\Settings\WPM_Settings_Auto_Translate_Pro;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
;
class WPM_OpenAI {


	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts_and_styles' ) );
		add_action( 'wpm_openai_settings', array( $this,'render_openai_settings' ) );
		add_action('admin_head', array( $this, 'remove_wp_footer_text') );
	}

	/**
	 * Get API configurations
	 * @since 	2.4.23
	 * */
	public static function get_settings() {

		$defaults 		=	[
								'api_keys' 				=>	[],
								'api_provider'			=>	'',
								'model'					=>	'',
								'api_available_models'	=>	[],
							];
		$ai_settings 	=	get_option( 'wpm_openai_settings', $defaults );

		return $ai_settings;

	}

	/**
	 * Return api privider details
	 * @since 	2.4.23
	 * */
	public static function get_api_providers(){
		
		return [
			'multilang' => [
                'name' => 'Internal',
                'url' => '',
                'endpoint' => ''
            ],
            'openai' => [
                'name' => 'OpenAI',
                'endpoint' => 'https://api.openai.com/v1/',
                'url' => 'https://platform.openai.com/'
            ],
            // 'deepseek' => [
            //     'name' => 'Deepseek',
            //     'endpoint' => 'https://api.deepseek.com/v1/',
            //     'url' => 'https://platform.deepseek.com/'
            // ],
        ];

	}

	/**
	 * Load admin scripts and styles
	 * @since 	2.4.23
	 * */
	public function load_admin_scripts_and_styles( $hook ) {
		
		if ( $hook === 'toplevel_page_wpm-settings' ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_script( 'wpm_openai', wpm_asset_path( 'scripts/admin-openai' . $suffix . '.js' ), array(), WPM_VERSION, true );
			$translator_params = array(
				'languages'                 	=>	array_keys( wpm_get_languages() ),
				'default_language'          	=>	wpm_get_default_language(),
				'language'                  	=>	wpm_get_language(),
				'wpmpro_openai_nonce'			=>	wp_create_nonce( 'wpmpro-openai-nonce' ),
				'is_pro_active'					=>	wpm_is_pro_active(),
			);
			$translator_params 	= 	WPM_Settings_Auto_Translate_Pro::filter_js_params( $translator_params );	
			wp_localize_script( 'wpm_openai', 'wpm_openai_params', $translator_params );
			wp_enqueue_script( 'wpm_openai' );

			wp_enqueue_style( 'wpm-openai-css', wpm_asset_path( 'styles/admin/wpm-openai' . $suffix . '.css' ), array(), WPM_VERSION );

		}

	}

	/**
	 * Generate openai settings html
	 * @since 	2.4.23
	 * */
	public function render_openai_settings() {
		
		$providers 		=	self::get_api_providers();
		$ai_settings 	=	self::get_settings();
		$provider 		=	$ai_settings['api_provider'];
		$secret_key 	=	'';
		if ( ! empty( $ai_settings['api_keys'] ) && ! empty( $ai_settings['api_keys'][$provider] ) ) {
			$secret_key =	$ai_settings['api_keys'][$provider];	
		}
		$models 		=	[];
		if ( ! empty( $ai_settings['api_available_models'] ) && ! empty( $ai_settings['api_available_models'][$provider] ) ) {
			$models 	=	$ai_settings['api_available_models'][$provider];
		}
		$selected_model	=	'';
		if ( ! empty( $ai_settings['model'] ) && ! empty( $ai_settings['model'] ) ) {
			$selected_model =	$ai_settings['model'];
		}

		$hide_class 	=	'';
		if ( $ai_settings['api_provider'] === 'multilang' || empty( $ai_settings['api_provider'] ) ) {
			$hide_class 	=	'wpm-hide';
		}
		
		?>
		<div>
			<h2><?php echo esc_html__('OpenAI Settings'); ?></h2>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="wpm-openai-platform"><?php echo esc_html__( 'Platform', 'wp-multilang' ) ?></label>
						</th>	
						<td>
							<select id="wpm-openai-provider" name="wpm_openai_provider">
								<?php 
								if ( ! empty( $providers ) && is_array( $providers ) ) {
									foreach ( $providers as $provider_key => $provider ) {
										$selected 	=	'';
										if ( ! empty( $ai_settings['api_provider'] ) && $ai_settings['api_provider'] === $provider_key ) {
											$selected 	=	'selected';	
										}
										?>
										<option value="<?php echo esc_attr( $provider_key ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_attr( $provider['name'] ); ?></option>
										<?php	
									}
								}
								?>
							</select>
							<?php 
							if ( ! wpm_is_pro_active() && ( $ai_settings['api_provider'] === 'multilang' || empty( $ai_settings['api_provider'] ) ) ) {
								?>
								<span class="wpm-openai-provider-note"> <?php echo esc_html__( 'This Feature requires the ', 'wp-multilang' ) ?> <a href="https://wp-multilang.com/pricing/#pricings" target="__blank"><?php echo esc_html__( 'Premium Version', 'wp-multilang' ); ?></span>
								<?php
							}
							?>
						</td>	
					</tr>
					<tr valign="top" class="wpm-hide-openai-wrapper <?php echo esc_attr( $hide_class ); ?>">
						<th scope="row" class="titledesc">
							<label for="wpm-openai-secretkey"><?php echo esc_html__( 'Secret key', 'wp-multilang' ) ?></label>
						</th>	
						<td>
							<input class="regular-text" type="password" id="wpm-openai-secretkey" name="wpm_openai_secretkey" value="<?php echo esc_attr( $secret_key ); ?>">
							<button type="button" id="wpm-validate-openai-key" class="button"><?php echo esc_html__( 'Validate API Key', 'wp-multilang' ); ?></button>
							<div id="wpm-secret-key-error"><?php echo esc_html__('Secret key cannot be blank', 'wp-multilang'); ?></div>
							<div class="wpm-openai-api-success-note"></div>
						</td>	
					</tr>
					<tr valign="top" id="wpm-hide-openai-models-wrapper" class="wpm-hide-openai-wrapper <?php echo esc_attr( $hide_class ) ?>">
						<th scope="row" class="titledesc">
							<label for="wpm-openai-models"><?php echo esc_html__( 'Translation Models', 'wp-multilang' ) ?></label>
						</th>			
						<td>
							<select name="wpm_openai_models" id="wpm-openai-models">
								<?php 
								if ( ! empty( $models ) && is_array( $models ) ) {
									foreach ( $models as $model ) {
										$selected = '';
										if ( $model === $selected_model ) {
											$selected 	=	'selected';
										}
									?>
										<option value="<?php echo esc_attr( $model ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $model ); ?></option>
									<?php
									}
								}else{
								?>
									<option value=""><?php echo esc_html__( 'Models Not Available', 'wp-multilang'); ?></option>
								<?php
								}
								?>
							</select>
						</td>
					</tr>
					<tr valign="top">
						<td id="wpm-save-openai-settings-td">
							<button class="button button-primary" id="wpm-save-openai-settings" type="button" ><?php echo esc_html__( 'Save Settings', 'wp-multilang' ); ?></button>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php

	}

	/**
	 * Validate api secret key
	 * @since 	2.4.23
	 * */
	public static function validate_secret_key() {
		
		$return_data 	=	[ "status" => false, "message" => "Please check your secret key" ];

		if ( ! empty( $_POST['secret_key'] ) && ! empty( $_POST['provider'] ) ) {
			$api_key 	=	sanitize_text_field( wp_unslash( $_POST['secret_key'] ) );
			$provider 	=	sanitize_text_field( wp_unslash( $_POST['provider'] ) );

			$api_url 	=	self::get_provider_endpoint( $provider );
			$end_point 	=	$api_url . 'models';

			$response = wp_remote_get( $end_point, [
	            'headers' => [
	                'Authorization' => 'Bearer ' . $api_key,
	            ],
	            'timeout' => 20,
	            'sslverify' => true,
	        ]);

	        if ( is_wp_error( $response ) ) {
	            throw new \Exception( esc_html__( 'API Request WP Error: ', 'wp-multilang' ) . esc_html( $response->get_error_message() ) );
	        }

		    $response_code = wp_remote_retrieve_response_code( $response );
	        $response_body = wp_remote_retrieve_body( $response );
	        $decoded_body = json_decode( $response_body, true );

	        if ( $response_code !== 200 ) {
	            $error_message = 'API Fout (Code: ' . $response_code . '): ';
	            $error_details = isset( $decoded_body['error']['message'] ) ? $decoded_body['error']['message'] : $response_body;
	            // Truncate long error messages from API
	            if ( strlen( $error_details ) > 500 ) {
	                $error_details = substr( $error_details, 0, 500 ) . '... (truncated)';
	            }
	            $error_message .= $error_details;
	            throw new \Exception( esc_html( $error_message ) );
	        }

	        $models_data = null;
	        if ( isset( $decoded_body['data'] ) && is_array( $decoded_body['data'] ) ) {
	            $models_data = $decoded_body['data'];
	        } elseif ( is_array($decoded_body ) && ( empty($decoded_body ) || isset( $decoded_body[0]['id'] ) || ( isset( $decoded_body[0] ) && is_string( $decoded_body[0] ) ) ) ) {
	            // Handle cases where the response is directly an array of models (objects with 'id' or strings)
	            $models_data = $decoded_body;
	        } else {
	            throw new \Exception( esc_html__( 'Invalid model data structure in API response. Body: ', 'wp-multilang' ) . esc_html( substr( $response_body, 0, 200 ) ) );
	        }

	        $models = [];
	        if ( is_array( $models_data ) ) {
	            foreach ( $models_data as $m ) {
	                if ( is_array( $m ) && isset( $m['id'] ) && is_string( $m['id'] ) ) {
	                    $models[] = $m['id'];
	                } elseif ( is_string( $m ) ) { // Handle cases where models are just an array of strings
	                    $models[] = $m;
	                }
	            }
	        }

	        $models = array_filter( array_unique( $models ) ); // Ensure unique and remove empty
	        sort( $models );

	        return [
	            'message' 	=>	esc_html__ ( 'API validation successfull', 'wp-multilang' ),
	            'models' 	=>	$models,
	            'api_key'	=>	$api_key,
	            'provider'	=>	$provider,
	        ];

		}else{
			throw new \InvalidArgumentException( esc_html__( 'Please enter valid API credentials for ', 'wp-multilang' ) . esc_html( $provider_key ) );
		}

	}

	/**
	 * Get provider endpoint URL
	 * @since 	2.4.23
	 * */
	public static function get_provider_endpoint( $provider ){
		
		$providers 	=	self::get_api_providers();

		return $providers[$provider]['endpoint'] ?? 'https://api.openai.com/v1/'; // Fallback for known providers

	}

	/**
	 * Save open ai settings
	 * @since 2.4.23
	 * */
	public static function save_settings() {
		
		$provider 	=	sanitize_text_field( wp_unslash( $_POST['provider'] ) );
		$model 		=	'';
		if ( $provider !== 'multilang' ) {
			$model 	=	sanitize_text_field( wp_unslash( $_POST['model'] ) );
		}

		$api_settings 	=	self::get_settings();

		$api_settings['api_provider']	=	$provider;
		$api_settings['model']			=	$model;

		update_option( 'wpm_openai_settings', $api_settings );

	}

	/**
	 * Remove footer text on wpm settings page
	 * @param 	$text 	string
	 * @return 	$text 	string
	 * @since 	2.4.23
	 * */
	public function remove_wp_footer_text() {

	    $screen = get_current_screen();
	    if ( isset($screen->id) && $screen->id === 'toplevel_page_wpm-settings' ) {
	        add_filter('admin_footer_text', '__return_empty_string', 11);
	        add_filter('update_footer', '__return_empty_string', 11);
	    }

	}

	/**
	 * @param 	$string 	string
	 * @param 	$source 	string
	 * @param 	$source 	string
	 * @return 	$string 	string
	 * @since 	2.4.23
	 * */
	public static function translate_content( $string, $source, $target, $settings ) {
		
		$provider 	=	WPM_OpenAI::get_api_providers();
		$provider 	=	$provider['openai'];
		$endpoint 	=	'https://api.openai.com/v1/chat/completions';

			
	    $api_key 	=	$settings['api_keys']['openai'];
	    $model 		=	$settings['model'];

	    $body 		=	[
	        'model' => $model,
	        'messages' => [
	            [
	                'role' => 'system',
	                'content' => "You are a professional translator that translates text from {$source} to {$target}."
	            ],
	            ['role' => 'user', 'content' => $string],
	        ],
	    ];

	    $response 	=	wp_remote_post( $endpoint, [
	        'headers' => [
	            'Content-Type'  => 'application/json',
	            'Authorization' => 'Bearer ' . $api_key,
	        ],
	        'body' => json_encode( $body ),
	    ]);

	    if ( is_wp_error( $response ) ) {
	        return $string;
	    }

	    $data = json_decode( wp_remote_retrieve_body( $response ), true );
	    
	    if ( isset( $data['choices'][0]['message']['content'] ) && ! empty( $data['choices'][0]['message']['content'] ) ) {
	    	return $data['choices'][0]['message']['content'];
	    }

	    return $string;

	}

}