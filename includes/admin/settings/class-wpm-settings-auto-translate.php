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
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	
	public function get_settings() {

		$settings = apply_filters( 'wpm_' . $this->id . '_settings', array(

			array( 'title' => __( 'Auto Translate', 'wp-multilang' ), 'type' => 'title', 'desc' => '', 'id' => 'autotranslate_options' ),

			array(
				'title' => __( 'List Of Languages', 'wp-multilang' ),
				'id'    => 'wpm_autotranslate',
				'type'  => 'autotranslate',
			),

			array( 'type' => 'sectionend', 'id' => 'autotranslate_options' ),

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

		if ( defined('WP_MULTILANG_PRO_VERSION') ) { 
			do_action('wpmpro_render_autotranslate_ui');
		}else {
			include_once __DIR__ . '/views/html-auto-translate.php';
		}
	}

}
