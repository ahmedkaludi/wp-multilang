<?php
/**
 * WP Multilang WPM_Deepl
 * @since 	2.4.30
 */
namespace WPM\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
;
class WPM_Deepl {

	public const FREE_END_POINT  	=	'https://api-free.deepl.com/v2';
	public const PRO_END_POINT 		=	'https://api.deepl.com/v2';

	public function __construct() {
		add_action( 'wpm_render_deepl_settings', [ $this, 'render_settings' ], 10, 1);	
		add_filter( 'wpm_filter_autotranslate_localize_data', [ $this, 'filter_localize_data' ] );
	}

	public function filter_localize_data( $params ) {

		$params['wpm_deepl_integration'] = get_option( 'wpm_deepl_integration', '0' );
		$params['ai_settings']['wpm_deepl_integration'] = $params['wpm_deepl_integration'];

		return $params;

	}

	/**
	 * Render settings panel
	 * @param 	$ai_settings 	array
	 * @since 	2.4.30
	 * */
	public function render_settings( $ai_settings ) {
		
		$secret_key 			=	isset( $ai_settings['deepl_secret_key'] ) ? $ai_settings['deepl_secret_key'] : '';
		$deepl_plan 			=	isset( $ai_settings['deepl_api_plan'] ) ? $ai_settings['deepl_api_plan'] : '';
		$wpm_deepl_integration 	= 	get_option( 'wpm_deepl_integration', '0' );

		$hide_deepl_child 		=	'wpm-hide';
		if ( $wpm_deepl_integration === '1' )
			$hide_deepl_child 	=	'';
		?>

		<tr valign="top">
			<th scope="row" class="titledesc">
				<label class="wpm-label-cursor" style="cursor:pointer;" for="wpm_deepl_integration"><?php echo esc_html__( 'DeepL Integration', 'wp-multilang' ); ?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<label for="wpm_deepl_integration">
						<input name="wpm_deepl_integration" id="wpm_deepl_integration" type="checkbox" value="1" <?php checked( $wpm_deepl_integration, '1' ); ?>> 
					</label> 
				</fieldset>
			</td>
		</tr>
		<tr valign="top" class="wpm-deepl-children <?php echo esc_attr( $hide_deepl_child ); ?>">
		    <th scope="row" class="titledesc wpm-pl-20">
		        <label for="wpm-deepl-secretkey">
		            <?php echo esc_html__( 'Authentication Key', 'wp-multilang' ); ?>
		        </label>
		    </th>

		    <td class="wpm-pl-20">
		        <input
		            class="regular-text"
		            type="password"
		            id="wpm-deepl-secretkey"
		            name="wpm_deepl_secretkey"
		            placeholder="Enter your DeepL API key"
		            value="<?php echo esc_attr( $secret_key ); ?>"
		        />
		    </td>
		</tr>
		<tr valign="top" class="wpm-deepl-children <?php echo esc_attr( $hide_deepl_child ); ?>">
		    <th scope="row" class="titledesc wpm-pl-20">
		        <label for="wpm-deepl-api-plan">
		            <?php echo esc_html__( 'Account API Plan', 'wp-multilang' ); ?>
		        </label>
		    </th>

		    <td class="wpm-pl-20">

		        <select
		            id="wpm-deepl-api-plan"
		            name="wpm_deepl_api_plan"
		        >

		            <option value="free"
		                <?php selected( $deepl_plan, 'free' ); ?>>
		                <?php echo esc_html__( 'DeepL API Free (api-free.deepl.com)', 'wp-multilang' ); ?>
		            </option>

		            <option value="pro"
		                <?php selected( $deepl_plan, 'pro' ); ?>>
		                <?php echo esc_html__( 'DeepL API Pro (api.deepl.com)', 'wp-multilang' ); ?>
		            </option>

		        </select>

		        <p class="description">
		            <?php echo esc_html__( 'Select your DeepL developer account type.', 'wp-multilang' ); ?>
		        </p>

		    </td>
		</tr>
		<?php

	}

	/**
	 * Check Quota
	 * @return 	$api_resp	array
	 * @since 	2.4.30
	 * */
	public static function check_quota() {
		
		$api_resp['status'] = false;
		$api_resp['message'] = '';

		$ai_settings 	=	get_option( 'wpm_openai_settings' );

		$api_key  = isset( $ai_settings[ 'deepl_secret_key' ] ) ? $ai_settings[ 'deepl_secret_key' ] : '';
	    $api_plan = isset( $ai_settings[ 'deepl_api_plan' ] ) ? $ai_settings[ 'deepl_api_plan' ] : '';

	    if ( empty( $api_key ) || empty( $api_plan ) ) {
	        return new WP_Error( 'missing_key', 'API Key is required to check quota.' );
	    }

	    // Route to the /usage endpoint based on the plan tier
	    $url = ( $api_plan === 'pro' ) ? self::PRO_END_POINT : self::FREE_END_POINT;
	    $url = $url . '/usage';

	    $args = array(
	        'headers' => array(
	            'Authorization' => 'DeepL-Auth-Key ' . $api_key,
	            'Content-Type'  => 'application/json',
	        ),
	        'timeout' => 15,
	    );
	    
	    $response = wp_remote_get( $url, $args );
	    
	    if ( is_wp_error( $response ) ) {
	        return $response;
	    }

	    $status_code = wp_remote_retrieve_response_code( $response );
	    $response_data = json_decode( wp_remote_retrieve_body( $response ), true );
	    

	    if ( $status_code !== 200 ) {
	        $msg = isset( $response_data['message'] ) ? $response_data['message'] : 'Failed to retrieve usage.';
	        return new WP_Error( 'deepl_usage_error', 'DeepL Error (' . $status_code . '): ' . $msg );
	    }

		if (
		    isset( $response_data['character_count'] ) && isset( $response_data['character_limit'] )  &&
		    $response_data['character_count'] < $response_data['character_limit']
		) {
		    $api_resp['status'] = true;
		} else {
		    $api_resp['status'] = false;
		}

	    return $api_resp;	

	}

	/**
	 * API request to translate content into respected language
	 * @param 	$string 	string	
	 * @param 	$source 	string	
	 * @param 	$target 	target	
	 * @param 	$settings 	array
	 * @return 	$string 	translated string
	 * @since 	2.4.30	
	 * */
	public static function translate_content( $string, $source, $target, $settings ) {
		
		$api_key  = isset( $settings[ 'deepl_secret_key' ] ) ? $settings[ 'deepl_secret_key' ] : '';
	    $api_plan = isset( $settings[ 'deepl_api_plan' ] ) ? $settings[ 'deepl_api_plan' ] : '';

	    if ( empty( $api_key ) || empty( $api_plan ) ) {
	        throw new \Exception( 'missing_key', 'API Key is required to check quota.' );
	        return $string;
	    }

	    $url = ( $api_plan === 'pro' ) ? self::PRO_END_POINT : self::FREE_END_POINT;
	    $url = $url . '/translate';

	    // Prepare request body with support for advanced HTML tag safety handling
        $body = array(
            'text'         => array( $string ),
            'target_lang'  => strtoupper( $target ),
            'source_lang'  => strtoupper( $source ),
            'tag_handling' => 'html' // Instructs DeepL to ignore HTML tags and structure safely
        );

        $args = array(
            'body'        => json_encode( $body ),
            'headers'     => array(
                'Authorization' => 'DeepL-Auth-Key ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'timeout'     => 30,
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
	    	throw new \Exception( esc_html( $response->get_error_message() ) );
	        return $string;
	    }	

	   	$status_code = wp_remote_retrieve_response_code( $response );
        $response_data = json_decode( wp_remote_retrieve_body( $response ), true );

	    if ( $status_code !== 200 ) {
            $error_message = isset( $response_data['message'] ) ? $response_data['message'] : 'Unknown DeepL HTTP error API event.';
            throw new \Exception( 'deepl_api_failure', 'DeepL Error Code (' . $status_code . '): ' . $error_message );
            return $string;
        }

        if ( isset( $response_data['translations'][0]['text'] ) ) {
            $translated_text = $response_data['translations'][0]['text'];
            
            return $translated_text;
        }

        return $string;

	}

}