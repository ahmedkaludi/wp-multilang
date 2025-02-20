<?php
/**
 * Class for capability with Gravity Forms plugin
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPM_Gravityforms
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Magazine3
 * @since 	 2.4.17
 */
class WPM_Gravityforms {

	public function __construct() {
		add_filter( 'gform_form_update_meta', array( $this, 'modify_form_fields' ), 10, 3 );
		add_filter( 'gform_form_post_get_meta', array( $this, 'render_form_fields' ) );
	}

	/**
	 * Modify the display meta field of gf_form_meta table before saving into the database
	 * @param 	$meta 		array
	 * @param 	$form_id 	int
	 * @param 	$meta_name 	string
	 * @since 	2.4.17
	 * */
	public function modify_form_fields( $meta, $form_id, $meta_name ) {

		if ( $meta_name == 'display_meta' ) {

			$option_key 	=	'gf_display_meta_'.$form_id;
			$old_value 		=	'';
			$old_value 		=	get_option( $option_key );
			$option_meta 	=	base64_encode( maybe_serialize( $meta ) );
			
			update_option( $option_key, wpm_set_new_value( $old_value, $option_meta ) );

		}
		
   		return $meta;
	}

	/**
	 * Modify the display of form fields while rendering
	 * @param 	$form 	array
	 * @return 	$form 	array 
	 * @since 	2.4.17
	 * */
	public function render_form_fields( $form ) {
		
		$form_id 		=	isset( $form['id'] ) ? $form['id'] : 0;
		if ( $form_id > 0 ) {

			$option_key 			=	'gf_display_meta_'.$form_id;
			$get_option_value 		=	get_option( $option_key );
			$get_option_value 		=	wpm_translate_value( $get_option_value);

			if ( ! empty( $get_option_value ) && is_string( $get_option_value ) ) {

				$get_option_value 	=	maybe_unserialize( base64_decode( $get_option_value, true ) );
				if ( is_array( $get_option_value ) ) {

					if ( ! empty( $form['title'] ) && ! empty( $get_option_value['title'] ) ) {
						$form['title'] 			=	$get_option_value['title'];	
					}
					if ( ! empty( $form['description'] ) && ! empty( $get_option_value['description'] ) ) {
						$form['description'] 	=	$get_option_value['description'];	
					}
					if ( ! empty( $form['fields'] ) && ! empty( $get_option_value['fields'] ) ) {
						$form['fields'] 		=	$get_option_value['fields'];	
					}

				}
				
			}

		}
		

		return $form;

	}

}