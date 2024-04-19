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

		$compat_Settings = array(
				'title'   => __( 'Elementor', 'wp-multilang' ),
				'class'   => 'wpm_free_compatibilities',
				'default' => 'no',
				'type'    => 'checkbox',
			);
		if(defined('WP_MULTILANG_PRO_VERSION')){
			$compat_Settings = apply_filters('wpm_premium_settings_pro', array());
		}

		$settings = apply_filters( 'wpm_' . $this->id . '_settings', array(

			array( 'title' => __( 'Premium Features', 'wp-multilang' ), 'type' => 'title', 'desc' => '', 'id' => 'premium_features' ),
			$compat_Settings,
			array( 'type' => 'sectionend', 'id' => 'premium_features' ),
			array( 'title' => '', 'type' => 'section_note', 'desc' => 'If you <strong>can’t find your compatibility</strong> with <strong>WP Multilang Pro</strong>, then we’ll make the integration for you without any extra charge, if you upgrade to pro.')

		) );

		return apply_filters( 'wpm_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();

		WPM_Admin_Settings::save_fields( $settings );
	}
}
