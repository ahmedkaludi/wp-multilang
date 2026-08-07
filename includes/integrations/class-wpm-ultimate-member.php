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
	 * List of um_options sub-keys that are translatable text strings.
	 */
	const UM_TRANSLATABLE_OPTIONS = array(
		'profile_title',
		'profile_desc',
		'delete_account_text',
		'delete_account_no_pass_required_text',
		'restricted_access_post_title',
		'restricted_access_message',
		'welcome_email_sub',
		'checkmail_email_sub',
		'pending_email_sub',
		'approved_email_sub',
		'inactive_email_sub',
		'deletion_email_sub',
		'resetpw_email_sub',
		'changedpw_email_sub',
		'changedaccount_email_sub',
		'notification_new_user_sub',
		'notification_review_sub',
		'notification_deletion_sub',
		'suspicious-activity_sub',
	);

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

			add_filter( "wpm_{$meta_key}_meta_config", 		array( $this, 'config' ), 10, 3 );
			add_filter( "wpm_add_{$meta_key}_meta_value", 		array( $this, $callbacks[0] ), 10, 1 );
			add_filter( "wpm_update_{$meta_key}_meta_value", 	array( $this, $callbacks[0] ), 10, 1 );
			add_filter( "wpm_get_{$meta_key}_meta_value", 		array( $this, $callbacks[1] ), 10, 1 );
		}

		add_filter( 'um_register_form_button_one', array( $this, 'filter_form_button_one' ), 10, 2 );
		add_filter( 'um_register_form_button_two', array( $this, 'filter_form_button_two' ), 10, 2 );
		add_filter( 'um_login_form_button_one', array( $this, 'filter_login_form_button_one' ), 10, 2 );
		add_filter( 'um_login_form_button_two', array( $this, 'filter_login_form_button_two' ), 10, 2 );

		// Translate each um_options sub-key at read time via UM's own per-option filter.
		// This runs after get_option('um_options') so translations are applied cleanly
		// at the point UM reads each individual option, without affecting saved DB values.
		foreach ( self::UM_TRANSLATABLE_OPTIONS as $option_key ) {
			add_filter( "um_get_option_filter__{$option_key}", array( $this, 'translate_um_option' ), 10, 1 );
		}

		// Translate raw multilingual strings inside the options array to plain strings
		// before WPM's native pre_update filter check to prevent the merger from being bypassed.
		add_filter( 'pre_update_option_um_options', array( $this, 'clean_um_options_before_save' ), 10, 1 );
	}

	/**
	 * Translate in-memory raw multilingual strings inside um_options to the active language
	 * before WPM's native pre_update_option filter runs, to ensure it doesn't bypass merging.
	 *
	 * @param array $value The array being saved.
	 * @return array The cleaned array with only plain strings for translatable fields.
	 */
	public function clean_um_options_before_save( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( self::UM_TRANSLATABLE_OPTIONS as $key ) {
			if ( isset( $value[ $key ] ) && is_string( $value[ $key ] ) ) {
				$value[ $key ] = wpm_translate_string( $value[ $key ] );
			}
		}

		return $value;
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
	 * Translate a um_options sub-key string via wpm_translate_string.
	 * Hooked onto um_get_option_filter__{$option_id} so it runs when UM reads individual options.
	 *
	 * @param mixed $value The raw stored value (may contain [:en]...[:]  multilingual tags).
	 * @return mixed Translated string for the current language.
	 */
	public function translate_um_option( $value ) {
		if ( is_string( $value ) && wpm_is_ml_value( $value ) ) {
			return wpm_translate_string( $value );
		}
		return $value;
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