<?php
/**
 * WPM Widget Functions
 *
 * Widget related functions and widget registration.
 *
 * @category      Core
 * @package       WPM/Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Widgets.
 */
function wpm_register_widgets() {
	register_widget( 'WPM\Includes\Widgets\WPM_Widget_Language_Switcher' );
}

add_action( 'widgets_init', 'wpm_register_widgets' );

/**
 * Function to get allowed html for language switcher
 * @since 2.4.11
 * */
function wpm_lang_switcher_allowed_html(){

    $allowed_html = wp_kses_allowed_html( 'post' );
            
    // select
    $allowed_html['select']['onchange'] = array();
    $allowed_html['select']['title'] 	= array();
    $allowed_html['select']['class'] 	= array();
    $allowed_html['select']['id'] 		= array();
    $allowed_html['select']['data-*'] 	= array();

    //  options
    $allowed_html['option']['selected'] = array();
    $allowed_html['option']['value'] 	= array();
    $allowed_html['option']['disabled'] = array();
    $allowed_html['option']['class'] 	= array();
    $allowed_html['option']['id'] 		= array();
    $allowed_html['option']['data-*'] 	= array();

    return $allowed_html;
}
