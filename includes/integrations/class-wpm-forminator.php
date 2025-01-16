<?php
/**
 * Class for capability with Forminator
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPM_Forminator
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Magazine3
 * @since 	 2.4.16
 */
class WPM_Forminator { 

	const WPM_FORM_META					=	'forminator_form_meta';

	private $object_id 					=	0;

	/**
	 * WPM_Forminator constructor.
	 * */
	public function __construct() {
		
		$meta_keys = array(
			self::WPM_FORM_META => array(
				'set_form_meta_value',
				'get_form_meta_value'
			),
		);

		//Install meta Filters
		foreach ($meta_keys as $meta_key => $callbacks) {

			add_filter( "wpm_{$meta_key}_meta_config", 			array($this, 'config'), 10, 3 );
			add_filter( "wpm_add_{$meta_key}_meta_value", 		array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_update_{$meta_key}_meta_value", 	array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_get_{$meta_key}_meta_value", 		array($this, $callbacks[1]), 10, 1 );
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'add_lang_switcher_script'), 99 );

	}

	/**
	 * load scripts and styles for forminator plugin
	 * 
	 * @param $hook_suffix
	 * @since 2.4.16
	 */
	public function add_lang_switcher_script( $hook_suffix ) {
		
		$pages 	=	array( 'forminator_page_forminator-cform', 'forminator_page_forminator-poll', 'forminator_page_forminator-quiz', 'forminator_page_forminator-poll-wizard', 'forminator_page_forminator-cform-wizard', 'forminator_page_forminator-knowledge-wizard', 'forminator_page_forminator-entries' );

		if ( in_array( $hook_suffix, $pages ) ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	        $main_params = array(
	            'plugin_url'                 => wpm()->plugin_url(),
	            'ajax_url'                   => admin_url( 'admin-ajax.php' ),
	            'wpm_forminator_nonce'       => wp_create_nonce( 'wpm-forminator-localization' ),
	        );

	        wp_register_script( 'wpm-forminator-script', wpm_asset_path( 'scripts/wpm-forminator' . $suffix . '.js' ), array( 'jquery' ), WPM_VERSION, true );
	        wp_localize_script( 'wpm-forminator-script', 'wpm_forminator_params', $main_params );
	        wp_enqueue_script( 'wpm-forminator-script' );

	        wp_enqueue_style( 'wpm-forminator-css', wpm_asset_path( 'styles/admin/wpm-forminator' . $suffix . '.css' ), array(), WPM_VERSION );
			
		}

	}

	/**
	 * Config meta keys
	 *
	 * @param $config
	 * @param $meta_value
	 * @param $object_id
	 * @return mixed
	 * @since 2.4.16
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
	 * @since 2.4.16
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
	 * @since 2.4.16
	 */
	public function set_form_meta_value( $value ) {

		$key = self::WPM_FORM_META;

		return $this->set_value( $key, $value );
	}

		/**
	 * Get meta value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.16
	 */
	public function get_form_meta_value( $value ) {

		$key = self::WPM_FORM_META;

		return $this->get_value( $key, $value );
	}

}