<?php
/**
 * Class for capability with Team
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPM_Team
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Magazine3
 * @since 	 2.4.15
 */
class WPM_Team {

	const WPM_TEAM_POST_SOCIAL			=	'social';
	const WPM_TEAM_SHORT_CODE_COLUMN	=	'ttp_column';

	private $object_id = 0;

	/**
	 * WPM_Team constructor.
	 * */
	public function __construct() {
			
		$meta_keys = array(
			self::WPM_TEAM_POST_SOCIAL => array(
				'set_team_post_social_value',
				'get_team_post_social_value'
			),
			self::WPM_TEAM_SHORT_CODE_COLUMN => array(
				'set_team_short_code_column_value',
				'get_team_short_code_column_value'
			),
		);

		//Install meta Filters
		foreach ($meta_keys as $meta_key => $callbacks) {

			add_filter( "wpm_{$meta_key}_meta_config", 			array($this, 'config'), 10, 3 );
			add_filter( "wpm_add_{$meta_key}_meta_value", 		array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_update_{$meta_key}_meta_value", 	array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_get_{$meta_key}_meta_value", 		array($this, $callbacks[1]), 10, 1 );
		}

	}

	/**
	 * Config meta keys
	 *
	 * @param $config
	 * @param $meta_value
	 * @param $object_id
	 * @return mixed
	 * @since 2.4.15
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
	 * @since 2.4.15
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
	 * @since 2.4.15
	 */
	private function get_value($key, $value) {

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
	 * @param $value
	 * @return mixed
	 * @since 2.4.15
	 */
	public function set_team_post_social_value( $value ) {

		$key = self::WPM_TEAM_POST_SOCIAL;

		return $this->set_value( $key, $value );
	}

		/**
	 * Get meta value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.15
	 */
	public function get_team_post_social_value( $value ) {

		$key = self::WPM_TEAM_POST_SOCIAL;

		return $this->get_value( $key, $value );
	}

	/**
	 * Set shortcode meta column value
	 *
	 * @param $value
	 * @return mixed
	 * @since 2.4.15
	 */
	public function set_team_short_code_column_value( $value ) {

		$key = self::WPM_TEAM_SHORT_CODE_COLUMN;

		return $this->set_value( $key, $value );
	}

		/**
	 * Get shortcode meta value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.15
	 */
	public function get_team_short_code_column_value( $value ) {

		$key = self::WPM_TEAM_SHORT_CODE_COLUMN;

		return $this->get_value( $key, $value );
	}

}