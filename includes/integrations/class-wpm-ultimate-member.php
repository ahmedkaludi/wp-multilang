<?php
/**
* Class for capability with Ultimate Member
*/

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
* @class    WPM_Ultimate_Member
* @package  WPM/Includes/Integrations
* @category Integrations
* @since 	2.4.23
*/
class WPM_Ultimate_Member {

	const UTM_CUSTOM_FIELDS = '_um_custom_fields';
	const UTM_REGISTER_PRIMARY_WORD = '_um_register_primary_btn_word';
	const UTM_REGISTER_SECONDARY_WORD = '_um_register_secondary_btn_word';
	const UTM_LOGIN_PRIMARY_WORD = '_um_login_primary_btn_word';
	const UTM_LOGIN_SECONDARY_WORD = '_um_login_secondary_btn_word';

	private $object_id = 0;

	/**
	 * WPM_Ultimate_Member constructor.
	 */
	public function __construct() {

		$meta_keys = array(
			self::UTM_CUSTOM_FIELDS => array(
				'set_custom_field_value',
				'get_custom_field_value'
			),
			self::UTM_REGISTER_PRIMARY_WORD => array(
				'set_primary_field_value',
				'get_primary_field_value'
			),
			self::UTM_REGISTER_SECONDARY_WORD => array(
				'set_secondary_field_value',
				'get_secondary_field_value'
			),
			self::UTM_LOGIN_PRIMARY_WORD => array(
				'set_login_primary_field_value',
				'get_login_primary_field_value'
			),
			self::UTM_LOGIN_SECONDARY_WORD => array(
				'set_login_secondary_field_value',
				'get_login_secondary_field_value'
			),
		);

		//Install meta Filters
		foreach ($meta_keys as $meta_key => $callbacks) {

			add_filter( "wpm_{$meta_key}_meta_config", 			array( $this, 'config' ), 10, 3 );
			add_filter( "wpm_add_{$meta_key}_meta_value", 		array( $this, $callbacks[0] ), 10, 1 );
			add_filter( "wpm_update_{$meta_key}_meta_value", 	array( $this, $callbacks[0] ), 10, 1 );
			add_filter( "wpm_get_{$meta_key}_meta_value", 		array( $this, $callbacks[1] ), 10, 1 );
		}

		add_filter( 'um_register_form_button_one', array( $this, 'filter_form_button_one' ), 10, 2 );
		add_filter( 'um_register_form_button_two', array( $this, 'filter_form_button_two' ), 10, 2 );
		add_filter( 'um_login_form_button_one', array( $this, 'filter_login_form_button_one' ), 10, 2 );
		add_filter( 'um_login_form_button_two', array( $this, 'filter_login_form_button_two' ), 10, 2 );
	}

	/**
	 * Config meta keys
	 *
	 * @param 	$config
	 * @param 	$meta_value
	 * @param 	$object_id
	 * @return 	mixed
	 * @since 	2.4.23
	 */
	public function config( $config, $meta_value, $object_id ) {

		$this->object_id = $object_id;

		return $config;
	}


		/**
	 * Set meta translate in base64
	 *
	 * @param $key
	 * @param $value
	 * @return mixed
	 * @since 2.4.23
	 */
	private function set_value( $key, $value ) {

		global $wpdb;

		if ( ! $this->object_id ) {
			return $value;
		}

		$current_value = get_post_meta( $this->object_id, "{$key}_translate", true );

		// If translate data is not present then get default value
		if ( empty( $current_value ) ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason Using built function doesn't work in our case, so added manual query
			$current_value 	=	$wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id = %d", $key, $this->object_id ) );
			
			if ( ! empty( $current_value ) ) {

				$current_value 	=	base64_encode( $current_value );
				
			}
				
		}

		$db_value 	=	$value;
		if ( is_array( $db_value ) ) {
			$db_value 	=	maybe_serialize( $db_value );
		}

		update_post_meta( $this->object_id, "{$key}_translate", wpm_set_new_value( $current_value, base64_encode( $db_value ) ) );

		$this->object_id = 0;

		return $value;
	}

	/**
	 * Get meta translate from base64
	 *
	 * @param $key
	 * @param $value
	 * @return false|string
	 * @since 2.4.23
	 */
	private function get_value( $key, $value ) {

		if ( ! $this->object_id ) {
			return $value;
		}

		$tr_value = base64_decode( wpm_translate_value( get_post_meta( $this->object_id, "{$key}_translate", true ) ), true );
		
		$this->object_id = 0;

		if ( ! empty( $tr_value ) && is_string( $tr_value ) ) {
			return maybe_unserialize( $tr_value );
		}else{
			return $value;
		}

	}

	/**
	 * Set meta value data
	 *
	 * @param 	$value
	 * @return 	mixed
	 * @since 	2.4.23
	 */
	public function set_custom_field_value( $value ) {
		$key = self::UTM_CUSTOM_FIELDS;
		return $this->set_value( $key, $value );
	}

	/**
	 * Get meta value data
	 *
	 * @param 	$value
	 * @return 	false|string
	 * @since 	2.4.23
	 */
	public function get_custom_field_value( $value ) {

		$key = self::UTM_CUSTOM_FIELDS;
		return $this->get_value( $key, $value );
	}

	/**
	 * Set meta value data
	 *
	 * @param 	$value
	 * @return 	mixed
	 * @since 	2.4.23
	 */
	public function set_primary_field_value( $value ) {
		$key = self::UTM_REGISTER_PRIMARY_WORD;
		return $this->set_value( $key, $value );
	}

	/**
	 * Get meta value data
	 *
	 * @param 	$value
	 * @return 	false|string
	 * @since 	2.4.23
	 */
	public function get_primary_field_value( $value ) {
		$key = self::UTM_REGISTER_PRIMARY_WORD;
		return $this->get_value( $key, $value );
	}

	/**
	 * Set meta value data
	 *
	 * @param 	$value
	 * @return 	mixed
	 * @since 	2.4.23
	 */
	public function set_secondary_field_value( $value ) {
		$key = self::UTM_REGISTER_SECONDARY_WORD;
		return $this->set_value( $key, $value );
	}

	/**
	 * Get meta value data
	 *
	 * @param 	$value
	 * @return 	false|string
	 * @since 	2.4.23
	 */
	public function get_secondary_field_value( $value ) {

		$key = self::UTM_REGISTER_SECONDARY_WORD;
		return $this->get_value( $key, $value );
	}

	/**
	 * Set meta value data
	 *
	 * @param 	$value
	 * @return 	mixed
	 * @since 	2.4.23
	 */
	public function set_login_primary_field_value( $value ) {
		$key = self::UTM_LOGIN_PRIMARY_WORD;
		return $this->set_value( $key, $value );
	}

	/**
	 * Get meta value data
	 *
	 * @param 	$value
	 * @return 	false|string
	 * @since 	2.4.23
	 */
	public function get_login_primary_field_value( $value ) {
		$key = self::UTM_LOGIN_PRIMARY_WORD;
		return $this->get_value( $key, $value );
	}

	/**
	 * Set meta value data
	 *
	 * @param 	$value
	 * @return 	mixed
	 * @since 	2.4.23
	 */
	public function set_login_secondary_field_value( $value ) {
		$key = self::UTM_LOGIN_SECONDARY_WORD;
		return $this->set_value( $key, $value );
	}

	/**
	 * Get meta value data
	 *
	 * @param 	$value
	 * @return 	false|string
	 * @since 	2.4.23
	 */
	public function get_login_secondary_field_value( $value ) {
		$key = self::UTM_LOGIN_SECONDARY_WORD;
		return $this->get_value( $key, $value );
	}

	/** 
	 * Filter register primary button text
	 * @param 	$btn_word 	string
	 * @param 	$args 				array
	 * @return  $btn_word 	string
	 * @since 	2.4.23
	 * */
	public function filter_form_button_one( $btn_word, $args ) {
		$key = self::UTM_LOGIN_SECONDARY_WORD;
		if ( is_array( $args ) && ! empty( $args['form_id'] ) ) {
			$this->object_id = 	$args['form_id'];
			$btn_word = $this->get_value( $key, $btn_word );
		}
		
		return $btn_word;

	}

	/** 
	 * Filter register secondary button text
	 * @param 	$btn_word 	string
	 * @param 	$args 				array
	 * @return  $btn_word 	string
	 * @since 	2.4.23
	 * */
	public function filter_form_button_two( $btn_word, $args ) {
		$key = self::UTM_REGISTER_SECONDARY_WORD;
		if ( is_array( $args ) && ! empty( $args['form_id'] ) ) {
			$this->object_id = 	$args['form_id'];
			$btn_word = $this->get_value( $key, $btn_word );
		}
		
		return $btn_word;

	}

	/** 
	 * Filter loginp rimary text
	 * @param 	$btn_word 	string
	 * @param 	$args 				array
	 * @return  $btn_word 	string
	 * @since 	2.4.23
	 * */
	public function filter_login_form_button_one( $btn_word, $args ) {
		$key = self::UTM_LOGIN_PRIMARY_WORD;
		if ( is_array( $args ) && ! empty( $args['form_id'] ) ) {
			$this->object_id = 	$args['form_id'];
			$btn_word = $this->get_value( $key, $btn_word );
		}
		
		return $btn_word;

	}

	/** 
	 * Filter login secondary text
	 * @param 	$btn_word 	string
	 * @param 	$args 				array
	 * @return  $btn_word 	string
	 * @since 	2.4.23
	 * */
	public function filter_login_form_button_two( $btn_word, $args ) {
		$key = self::UTM_LOGIN_SECONDARY_WORD;
		if ( is_array( $args ) && ! empty( $args['form_id'] ) ) {
			$this->object_id = 	$args['form_id'];
			$btn_word = $this->get_value( $key, $btn_word );
		}
		
		return $btn_word;

	}
}