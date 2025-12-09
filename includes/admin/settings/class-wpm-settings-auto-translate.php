<?php
/**
 * WP Multilang Languages Settings
 *
 * @category    Admin
 * @package     WPM/Admin
 * @author   Valentyn Riaboshtan
 */

namespace WPM\Includes\Admin\Settings;
use WPM\Includes\Admin\WPM_Admin_Notices;
use WPM\Includes\Admin\Settings\WPM_Settings_Auto_Translate_Pro;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WPM_Settings_General.
 */
class WPM_Settings_Auto_Translate extends WPM_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'autotranslate';
		$this->label = __( 'Auto translate', 'wp-multilang' );

		parent::__construct();

		add_action( 'wpm_admin_field_autotranslate', array( $this, 'get_autotranslate' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_translation_script' ) );
	}

	public function enqueue_translation_script( $hook ){
		if( $hook === 'toplevel_page_wpm-settings' && ! defined( 'WP_MULTILANG_PRO_VERSION' ) ){

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$dir_path = plugin_dir_url(__DIR__);

			wp_register_script( 'wpm_translation_settings', wpm_asset_path( 'scripts/wpm-autotranslation-script' . $suffix . '.js' ), array( 'jquery' ), WPM_VERSION, true );

			// Generate and localize data for autotranslate script
			$main_params = array(
				'source_language' => function_exists('wpm_get_user_language') ? wpm_get_user_language() : 'en',
				'target_language' => function_exists('wpm_get_language') ? wpm_get_language() : 'en',
				'is_pro_active'					=>	wpm_is_pro_active(),
				'wpmpro_autotranslate_nonce' => wp_create_nonce('wpmpro-autotranslate-nonce'),
				
			);
			
			$main_params = WPM_Settings_Auto_Translate_Pro::filter_js_params( $main_params );
			
			wp_localize_script('wpm_translation_settings', 'wpm_autotranslate_localize_data', $main_params);
			wp_enqueue_script( 'wpm_translation_settings' );
		}
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	
	public function get_settings() {

		$settings = apply_filters( 'wpm_' . $this->id . '_settings', array(

			array( 
				'title' => __( 'Auto Translate', 'wp-multilang' ), 
				'type' => 'title', 
				/* translators: %s: url */
				'desc'  => sprintf( __( 'Read <a href="%s" target="_blank">Auto Translate Docs</a>', 'wp-multilang' ), esc_url( 'https://wp-multilang.com/docs/knowledge-base/how-to-auto-translate-your-website-contents-using-wp-multilang/' ) ),
				'id' => 'autotranslate_options', 
			),

			array(
				'title' => __( 'List Of Languages', 'wp-multilang' ),
				'id'    => 'wpm_autotranslate',
				'type'  => 'autotranslate',
			),

			array( 
				'type' => 'sectionend', 
				'id' => 'autotranslate_options', 
			),

		) );

		return apply_filters( 'wpm_get_settings_' . $this->id, $settings );
	}

	/**
	 * Render autotranslate UI
	 *
	 * @param $value
	 * @since 2.4.11
	 */
	public function get_autotranslate( $value ) {
		
		$GLOBALS['hide_save_button'] = true;

		$languages = get_option( 'wpm_languages', array() );
		$flags     = wpm_get_flags();

		include_once __DIR__ . '/views/html-auto-translate.php';
	}

}
