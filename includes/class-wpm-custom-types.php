<?php

namespace WPM\Includes;
use WPM\Includes\Admin\WPM_Admin_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class WPM_Custom_Post_Types
 * @package  WPM/Includes
 * @since 	 2.4.18
 */
class WPM_Custom_Post_Types {

	public function __construct(){
		
		add_filter('wpm_posts_config', array( $this, 'add_custom_types_to_config' ) );

	}

	/**
	 * Add custom post types to config to make it compatible
	 * @since 	2.4.18
	 * */
	public function add_custom_types_to_config( $config ) {
		
		$post_types 	=	WPM_Admin_Settings::get_option( 'wpm_custom_post_types' );

		if ( ! empty( $post_types ) && is_array( $post_types ) ) {

			$updated_config 	=	array();
			foreach ( $post_types as $key => $post_type ) {
				if ( isset( $config[ $key ] ) ) {
					$updated_config[ $key ] 	=	$config[ $key ];
				}else{
					$updated_config[ $key ] 	=	array();
				}
			}
			
			return $updated_config;
		}
		
		return $config;

	}
	

	/**
	 * Check if post type support is enabled on settings page
	 * @param 	$post 	WP_Post
	 * @return 	 boolean
	 * @since 	2.4.18
	 * */
	public static function validate_post_type_support( $post ) {
		
		if ( is_object( $post ) && ! empty( $post->post_type ) ) {

			$post_types 	=	WPM_Admin_Settings::get_option( 'wpm_custom_post_types' );

			if ( ! empty( $post_types ) && is_array( $post_types ) ) {

				if ( ! array_key_exists( $post->post_type, $post_types ) ) {
					return true;
				}			
				
			}
		}

		return false;

	}

}