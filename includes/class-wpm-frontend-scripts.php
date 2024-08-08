<?php
/**
 * Handle frontend scripts
 *
 * @class       WPM_Frontend_Scripts
 * @package     WPM/Classes/
 * @category    Class
 * @author      VaLeXaR
 */

namespace WPM\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPM_Frontend_Scripts Class.
 */
class WPM_Frontend_Scripts {

	/**
	 * Contains an array of script handles registered by WPM.
	 * @var array
	 */
	private static $scripts = array();

	/**
	 * Contains an array of script handles registered by WPM.
	 * @var array
	 */
	private static $styles = array();

	/**
	 * Contains an array of script handles localized by WPM.
	 * @var array
	 */
	private static $wp_localize_scripts = array();

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'wpm_block_script' ) );
	}

	/**
	 * Get styles for the frontend.
	 * @access private
	 * @return array
	 */
	public static function get_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$styles = array(
			'wpm-main' => array(
				'src'     => wpm_asset_path( 'styles/main' . $suffix . '.css' ),
				'deps'    => '',
				'version' => WPM_VERSION,
				'media'   => 'all',
			),
		);

		return $styles;
	}

	/**
	 * Register a script for use.
	 *
	 * @uses   wp_register_script()
	 * @access private
	 *
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  boolean  $in_footer
	 */
	private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = WPM_VERSION, $in_footer = true ) {
		self::$scripts[] = $handle;
		wp_register_script( $handle, $path, $deps, $version, $in_footer );
	}

	/**
	 * Register a style for use.
	 *
	 * @uses   wp_register_style()
	 * @access private
	 *
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  string   $media
	 */
	private static function register_style( $handle, $path, $deps = array(), $version = WPM_VERSION, $media = 'all' ) {
		self::$styles[] = $handle;
		wp_register_style( $handle, $path, $deps, $version, $media );
	}

	/**
	 * Register and enqueue a styles for use.
	 *
	 * @uses   wp_enqueue_style()
	 * @access private
	 *
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  string   $media
	 */
	private static function enqueue_style( $handle, $path = '', $deps = array(), $version = WPM_VERSION, $media = 'all' ) {
		if ( ! in_array( $handle, self::$styles, true ) && $path ) {
			self::register_style( $handle, $path, $deps, $version, $media );
		}
		wp_enqueue_style( $handle );
	}

	/**
	 * Register/queue frontend scripts.
	 */
	public static function load_scripts() {

		if ( ! did_action( 'before_wpm_init' ) ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// CSS Styles
		if ( $enqueue_styles = self::get_styles() ) {
			foreach ( $enqueue_styles as $handle => $args ) {
				self::enqueue_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'] );
			}
		}
	}

	/**
	 * Localize a WPM script once.
	 * @access private
	 *
	 * @param  string $handle
	 */
	private static function localize_script( $handle ) {
		if ( ! in_array( $handle, self::$wp_localize_scripts, true ) && wp_script_is( $handle ) && ( $data = self::get_script_data( $handle ) ) ) {
			$name                        = str_replace( '-', '_', $handle ) . '_params';
			self::$wp_localize_scripts[] = $handle;
			wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
		}
	}

	/**
	 * Return data for script handles.
	 * @access private
	 *
	 * @param  string $handle
	 *
	 * @return array|bool
	 */
	private static function get_script_data( $handle ) {

		/*switch ( $handle ) {

		}*/

		return false;
	}

	/**
	 * Localize scripts only when enqueued.
	 */
	public static function localize_printed_scripts() {
		foreach ( self::$scripts as $handle ) {
			self::localize_script( $handle );
		}
	}
	
	/**
	 * Change link of anchor tag href attribute when switcher is added on site editor or through post or page block
	 * @since 2.4.9
	 * */
	public static function wpm_block_script(){
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$script_data = array(
                'wpm_block_switch_nonce'    => wp_create_nonce( 'wpm_ajax_security_nonce' ),
                'ajax_url'              	=> admin_url( 'admin-ajax.php' ),
                'current_url'				=> wpm_get_current_url()
        );

		$filename = '/assets/blocks/language-switcher/js/switcher-block' . $suffix . '.js';
        $css_style_path = wpm()->plugin_url().$filename;

        wp_register_script( 'wpm-switcher-block-script', $css_style_path, array('jquery'), WPM_VERSION, true );
        wp_localize_script( 'wpm-switcher-block-script', 'wpm_localize_data', $script_data );
        wp_enqueue_script( 'wpm-switcher-block-script' );
	}
}
