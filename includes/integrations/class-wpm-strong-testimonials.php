<?php
/**
* Class for capability with Strong Testimonials
*/

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
* @class    WPM_Strong_Testimonials
* @package  WPM/Includes/Integrations
* @category Integrations
* @since 	2.4.23
*/
class WPM_Strong_Testimonials {

	const CUSTOM_FORMS_OPTION = 'wpmtst_custom_forms';
	const FORM_OPTION = 'wpmtst_form_options';

	/**
	 * WPM_Strong_Testimonials constructor.
	 */
	public function __construct() {

		$options = array(
			self::CUSTOM_FORMS_OPTION => array(
				'set_option_value',
				'get_option_value'	
			),
			self::FORM_OPTION => array(
				'set_option_value',
				'get_option_value'	
			),
		);

		foreach ($options as $option_key => $callbacks) {
			add_filter( "wpm_update_{$option_key}_option", array( $this, $callbacks[0] ), 10, 4 );
			add_filter( "option_{$option_key}", array( $this, $callbacks[1] ), 10, 2 );
		}

	}

	/**
	 * Set option value
	 * @param 	$value 	mixed
	 * @param 	$key 	string
	 * @return 	$value 	mixed
	 * @since 	2.4.23
	 * */
	public function set_option_value( $value, $original_value, $key, $old_value ) {

		global $wpdb;

		$translated_key 	=	$key . '_translate';

		$current_value = get_option( $translated_key );

		// If translate data is not present then get default value
		if ( empty( $current_value ) ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason Using built function doesn't work in our case, so added manual query
			$current_value 	=	$wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ) );
			
			if ( ! empty( $current_value ) ) {
				$current_value 	=	base64_encode( $current_value );
			}
		}

		$db_value 	=	$original_value;
		if ( is_array( $db_value ) ) {
			$db_value 	=	maybe_serialize( $db_value );
		}
		
		update_option( $translated_key, wpm_set_new_value( $current_value, base64_encode( $db_value ) ) );

		return $old_value;

	}

	/**
	 * Get option translated value
	 * @param 	$value 	mixed
	 * @param 	$key 	string
	 * @return 	$value 	mixed
	 * @since 	2.4.23
	 * */
	public function get_option_value( $value, $key ){

		$tr_value = base64_decode( wpm_translate_value( get_option( "{$key}_translate" ) ), true );

		if ( ! empty( $tr_value ) && is_string( $tr_value ) ) {
			return maybe_unserialize( $tr_value );
		}else{
			return $value;
		}

	}
}