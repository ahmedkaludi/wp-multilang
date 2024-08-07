<?php
/**
 * WP Multilang General Settings
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
 * WPM_Settings_General.
 */
class WPM_Settings_General extends WPM_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = esc_html__( 'General', 'wp-multilang' );

		parent::__construct();

		add_filter( 'wpm_general_settings', array( $this, 'add_uninstall_setting' ) );
		add_action( 'wpm_update_options_general', array( $this, 'update_wplang' ) );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {

		$languages = wpm_get_languages();

		$language_options = array();
		foreach ( $languages as $code => $language ) {
			$language_options[ $code ] = $language['name'];
		}

		$settings = apply_filters( 'wpm_general_settings', array(

			array(
				'title' => esc_html__( 'General options', 'wp-multilang' ),
				'type'  => 'title',
				/* translators: %s: url */
				'desc'  => sprintf( __( 'Read <a href="%s" target="_blank">Google guidelines</a> before.', 'wp-multilang' ), esc_url( 'https://support.google.com/webmasters/answer/182192?hl=' . wpm_get_user_language() ) ),
				'id'    => 'general_options'
			),

			array(
				'title'    => esc_html__( 'Site Language', 'wp-multilang' ),
				'desc'     => esc_html__( 'Set default site language.', 'wp-multilang' ),
				'id'       => 'wpm_site_language',
				'default'  => wpm_get_default_language(),
				'type'     => 'select',
				'class'    => 'wpm-enhanced-select',
				'css'      => 'min-width: 350px;',
				'options'  => $language_options,
			),

			array(
				'title'   => esc_html__( 'Show untranslated', 'wp-multilang' ),
				'desc'    => esc_html__( 'Show untranslated strings on language by default.', 'wp-multilang' ),
				'id'      => 'wpm_show_untranslated_strings',
				'default' => 'yes',
				'type'    => 'checkbox',
			),

			array(
				'title'   => esc_html__( 'Browser redirect', 'wp-multilang' ),
				'desc'    => esc_html__( 'Use redirect to user browser language in first time.', 'wp-multilang' ),
				'id'      => 'wpm_use_redirect',
				'default' => 'no',
				'type'    => 'checkbox',
			),

			array(
				'title'   => esc_html__( 'Use prefix', 'wp-multilang' ),
				'desc'    => esc_html__( 'Use prefix for language by default.', 'wp-multilang' ),
				'id'      => 'wpm_use_prefix',
				'default' => 'no',
				'type'    => 'checkbox',
			),

			array( 'type' => 'sectionend', 'id' => 'general_options' ),

		) );

		return apply_filters( 'wpm_get_settings_' . $this->id, $settings );
	}

	/**
	 * Add uninstall settings only for Super Admin
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	public function add_uninstall_setting( $settings ) {

		if ( ! is_multisite() || ( is_main_site() ) ) {
			$settings[] = array(
				'title' => esc_html__( 'Uninstalling', 'wp-multilang' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'uninstall_options',
			);

			$settings[] = array(
				'title'   => esc_html__( 'Delete translations', 'wp-multilang' ),
				'desc'    => esc_html__( 'Delete translations when uninstalling plugin (some translations may not be deleted and you must delete them manually).', 'wp-multilang' ),
				'id'      => 'wpm_uninstall_translations',
				'default' => 'no',
				'type'    => 'checkbox',
			);

			$settings[] = array( 'type' => 'sectionend', 'id' => 'uninstall_options' );
		}

		return $settings;
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();

		WPM_Admin_Settings::save_fields( $settings );
	}

	/**
	 * Update WPLANG option
	 */
	public function update_wplang() {
		$value     = WPM_Admin_Settings::get_option( 'wpm_site_language' );
		$languages = wpm_get_languages();
		$locale    = $languages[ $value ]['translation'];
		update_option( 'WPLANG', 'en_US' !== $locale ? $locale : '' );
	}
}
