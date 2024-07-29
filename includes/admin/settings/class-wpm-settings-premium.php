<?php
/**
 * WP Multilang Premium Settings
 *
 * @category    Admin
 * @package     WPM/Admin
 * @author   Valentyn Riaboshtan
 */

namespace WPM\Includes\Admin\Settings;
use WPM\Includes\Admin\WPM_Admin_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WPM_Settings_Support.
 */
class WPM_Settings_Premium extends WPM_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'premium';
		$this->label = __( 'Compatibility', 'wp-multilang' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {

		/**
		 * enqueue js
		 * */
		$main_params = array(
			'plugin_url'                 => wpm()->plugin_url(),
			'ajax_url'                   => admin_url( 'admin-ajax.php' ),
			'premium_nonce'  			 => wp_create_nonce( 'premium-localization' ),
		);
		wp_localize_script( 'wpm_premium_settings', 'wpm_premium_settings_params', $main_params );
		wp_enqueue_script( 'wpm_premium_settings' );

		$section_note = array( 'title' => '', 'type' => 'section_note', 'desc' => __('If you <strong>can’t find your compatibility</strong> with <strong>WP Multilang</strong>, then we’ll make the integration. <a href="https://wp-multilang.com/contact-us/">Contact Us</a>', 'wp-multilang'));

		$compat_Settings['compat'] = array(
				array(
					'title'   => __( 'Elementor', 'wp-multilang' ),
					'class'   => 'wpm_free_compatibilities',
					'id'      => 'wpm_elementor_compatibility_free',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'title'   => __( 'Divi Builder', 'wp-multilang' ),
					'class'   => 'wpm_free_compatibilities',
					'id'      => 'wpm_divi_compatibility_free',
					'default' => 'no',
					'type'    => 'checkbox',
				)
			);
		if(defined('WP_MULTILANG_PRO_VERSION')){
			$compat_Settings = apply_filters('wpm_premium_settings_pro', array());
			$section_note = array();
		}

		$setting_array[] = array( 'title' => __( 'Compatibility', 'wp-multilang' ), 'type' => 'title', 'desc' => '', 'id' => 'premium_features' );
		foreach ($compat_Settings['compat'] as $cs_key => $cs_value) {
			$setting_array[] = $cs_value;
		}
		$setting_array[] = array( 'type' => 'sectionend', 'id' => 'premium_features' );
		$setting_array[] = $section_note;
		
		$settings = apply_filters( 'wpm_' . $this->id . '_settings', $setting_array );

		return apply_filters( 'wpm_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		if(defined('WP_MULTILANG_PRO_VERSION')){
			$settings = $this->get_settings();

			WPM_Admin_Settings::save_fields( $settings );
		}
	}
}
