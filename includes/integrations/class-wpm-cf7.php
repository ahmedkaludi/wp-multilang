<?php
/**
 * Class for capability with Contact Form 7
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPM_CF7
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Valentyn Riaboshtan
 */
class WPM_CF7 {

	/**
	 * WPM_CF7 constructor.
	 */
	public function __construct() {
		add_filter( 'wpcf7_special_mail_tags', array( $this, 'add_language_tag' ), 10, 2 );
		add_filter( 'wpcf7_form_hidden_fields', array( $this, 'add_lang_field' ) );
		add_filter( 'wpcf7_special_mail_tags', array( $this, 'translate_post_title' ), 11, 2 );
		add_action( 'wpcf7_contact_form', array( $this, 'edit_form_translate_shortcode_title_attr' ), 10, 1 );
		add_filter( 'wpm_unslash_form_meta_value', array( $this, 'unslash_cf7_form_meta_value' ), 10, 2 );
	}

	public function add_language_tag( $output, $name ) {
		if ( '_language' === $name ) {
			$options = wpm_get_lang_option();

			return $options[ wpm_get_language() ]['name'];
		}

		return $output;
	}


	/**
	 * Add current user language hidden field
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function add_lang_field( $fields ) {
		$fields['lang'] = wpm_get_language();

		return $fields;
	}

	/**
	 * Translate post title
	 *
	 * @param $output string
	 * @param $name string
	 *
	 * @return string
	 * 
	 * @since 2.4.5
	 */
	public function translate_post_title( $output, $name ) {
		if ( '_post_name' == $name || '_post_title' == $name) {
			return wpm_translate_string( $output);
		}

		return $output;
	}
	
	/**
	 * Fix translation of the "title" attribute in the shortcode for copying, on the form edit page
	 *
	 * @param $wpcf7 current Contacts Form 7 instance
	 * @since 2.4.5
	*/
	public function edit_form_translate_shortcode_title_attr( $wpcf7 ) {
		if(!empty($wpcf7->title())){
			$wpcf7->set_title( wpm_translate_string( $wpcf7->title() ) );
		}
	}
	
	/**
	 * Unslash meta data with _form key
	 * @since 2.4.8
	 * */
	public function unslash_cf7_form_meta_value($meta_value, $meta_key){
		if($meta_key == '_form' && is_string($meta_value)){
			$meta_value = wp_unslash($meta_value);
		}
		return $meta_value;
	}
}
