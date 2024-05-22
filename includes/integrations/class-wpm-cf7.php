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
		add_action( 'update_post_metadata', array( $this, 'update_form_meta_data' ), 999, 5 );
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
	 * Update post content of _form meta key as that of post_content
	 * @since 2.4.8
	 * */
	public function update_form_meta_data($check, $object_id, $meta_key, $meta_value, $prev_value){
		if($object_id > 0){
			if($meta_key == '_form'){
				global $wpdb;
				$table = $wpdb->prefix.'posts';
				$post_content = $wpdb->get_row($wpdb->prepare("SELECT post_content FROM $table WHERE ID = %d", $object_id));

				if(is_object($post_content) && isset($post_content->post_content)){
					$table = $wpdb->prefix.'postmeta';
					$post_content = wp_unslash($post_content->post_content);
					$flag = $wpdb->query($wpdb->prepare("UPDATE $table SET meta_value = %s WHERE post_id = %d AND meta_key = '_form'", $post_content, $object_id));
					return true;
				}
			}
		}
		return $check;
	}
}
