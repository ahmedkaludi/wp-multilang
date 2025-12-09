<?php
/**
 * WP Multilang OpenAI
 * @since 	2.4.23
 */
namespace WPM\Includes\Admin;
use WPM\Includes\Admin\Settings\WPM_Settings_Auto_Translate_Pro;
use WPM\Includes\Admin\Settings\WPM_Settings_AI_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
;
class WPM_OpenAI {

	/**
	 * Validate api secret key
	 * @since 	2.4.23
	 * */
	public static function validate_secret_key() {
		
		$return_data 	=	[ "status" => false, "message" => "Please check your secret key" ];

		if ( ! empty( $_POST['secret_key'] ) ) {
			$api_key 	=	sanitize_text_field( wp_unslash( $_POST['secret_key'] ) );
			$provider 	=	'openai';

			$api_url 	=	WPM_Settings_AI_Integration::get_provider_endpoint( $provider );
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
	            $error_message = '(Code: ' . $response_code . '): ';
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
	            'message' 	=>	esc_html__ ( 'Key validated successfully.', 'wp-multilang' ),
	            'models' 	=>	$models,
	            'api_key'	=>	$api_key,
	            'provider'	=>	$provider,
	        ];

		}else{
			throw new \InvalidArgumentException( esc_html__( 'Please enter valid API credentials for ', 'wp-multilang' ) . esc_html( $provider_key ) );
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
		
	    $provider  = WPM_Settings_AI_Integration::get_api_providers();
	    $provider  = $provider['openai'];

	    // Correct endpoint for GPT-4.1
	    $endpoint  = 'https://api.openai.com/v1/responses';

	    $api_key   = $settings['api_keys']['openai'];
	    $model     = $settings['model'];

	    // Correct request format for Responses API
	    $body = [
	        'model' => $model,
	        'input' => [
	            [
	                'role'    => 'system',
	                'content' => "You are a professional translator that translates text from {$source} to {$target}."
	            ],
	            [
	                'role'    => 'user',
	                'content' => $string
	            ]
	        ]
	    ];

	    // Send request
	    $response = wp_remote_post( $endpoint, [
	        'headers' => [
	            'Content-Type'  => 'application/json',
	            'Authorization' => 'Bearer ' . $api_key,
	        ],
	        'body' => json_encode( $body ),
	        'timeout' => 30,
	    ]);

	    if ( is_wp_error( $response ) ) {
	    	throw new \Exception( esc_html( $response->get_error_message() ) );
	        return $string;
	    }

	    $data = json_decode( wp_remote_retrieve_body( $response ), true );

	    if ( is_array( $data ) && ! empty( $data['error'] ) && is_array( $data['error'] ) && ! empty( $data['error']['message'] ) ) {
	    	throw new \Exception( esc_html( $data['error']['message'] ) );
	    }

	    // Correct response path for Responses API
	    if ( isset($data['output'][0]['content'][0]['text']) ) {
	        return $data['output'][0]['content'][0]['text'];
	    }

	    return $string;
	}

	/**
	 * Check if API is working fine at first, if yes then send it for further translation
	 * @since 	2.4.23
	 * */
	public static function check_ai_platform_quota() {
		
		$api_settings 	=	WPM_Settings_AI_Integration::get_openai_settings();
		$api_resp['status'] = false;
		$api_resp['message'] = '';

		try{
			$response = self::translate_content( 'Hi', 'en', 'hi', $api_settings );
			$api_resp['status'] = true; 
		}catch ( \Throwable $e ) {
			$api_resp['message'] = $e->getMessage();
		}

		return $api_resp;

	}

}