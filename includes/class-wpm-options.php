<?php

namespace WPM\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Set filter for options
 *
 * Class WPM_Options
 * @package WPM/Includes
 * @author   Valentyn Riaboshtan
 */
class WPM_Options {

	/**
	 * Options config
	 *
	 * @var array
	 */
	public $options_config = array();

	/**
	 * WPM_Options constructor.
	 */
	public function __construct() {
		$config               = wpm_get_config();
		$this->options_config = $config['options'];

		foreach ( $this->options_config as $key => $option ) {
			add_filter( "option_{$key}", 'wpm_translate_value', 5 );
			add_action( "add_option_{$key}", 'update_option', 99, 2 );
			add_filter( "pre_update_option_{$key}", array( $this, 'wpm_update_option' ), 99, 3 );
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'customizer_style' ), 99 );
	}


	/**
	 * Update options with translate
	 *
	 * @param $value
	 * @param $old_value
	 * @param $option
	 *
	 * @return array|bool|mixed|string
	 */
	public function wpm_update_option( $value, $old_value, $option ) {

		if ( wpm_is_ml_value( $value ) ) {
			return $value;
		}

		$this->options_config[ $option ] = apply_filters( "wpm_option_{$option}_config", isset( $this->options_config[ $option ] ) ? $this->options_config[ $option ] : null );

		if ( null === $this->options_config[ $option ] ) {
			return $value;
		}

		$original_value = $value;

		remove_filter( "option_{$option}", 'wpm_translate_value', 5 );
		$old_value = get_option( $option );
		add_filter( "option_{$option}", 'wpm_translate_value', 5 );
		$value = wpm_set_new_value( $old_value, $value, $this->options_config[ $option ] );
		$value = apply_filters( "wpm_update_{$option}_option", $value, $original_value, $option, $old_value );

		return $value;
	}

	/**
	 * Add inline css for proper positioning of astra theme
	 * @since 2.4.16
	 */
	public function customizer_style() {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( $screen_id == 'customize' ) {
			
			if ( ! function_exists( 'wp_get_theme' ) ) {
				require_once ABSPATH . 'wp-includes/theme.php';
			}

			$active_theme 		= wp_get_theme();
			$active_theme_name 	= '';
			if ( ! empty( $active_theme ) && is_object( $active_theme ) ) {
				$active_theme_name = $active_theme->get( 'Name' );
			}

			if ( $active_theme_name == 'Astra' ) {

				$custom_css = "
                	#customize-controls .wpm-language-switcher{
						margin-left: 45px;
					}";
					
				wp_add_inline_style( 'wpm_language_switcher', $custom_css );
			
			}	
		}
	}
}
