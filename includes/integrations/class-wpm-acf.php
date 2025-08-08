<?php
/**
 * Class for capability with Advanced Custom Fields Plugin
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class    WPM_Acf
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Valentyn Riaboshtan
 */
class WPM_Acf {

	/**
	 * WPM_Acf constructor.
	 */
	public function __construct() {
		add_filter( 'wpm_post_acf-field-group_config', array( $this, 'add_config' ) );
		add_filter( 'acf/load_field_group', 'wpm_translate_value', 6 );
		add_filter( 'acf/get_field_label', 'wpm_translate_string', 6 );
		add_filter( 'acf/update_field', array( $this, 'update_field' ), 99 );
		add_filter( 'acf/update_value', array( $this, 'update_value' ), 99, 3 );
		add_filter( 'acf/load_field', 'wpm_translate_value', 6 );
		add_filter( 'acf/load_value', 'wpm_translate_value', 6 );
		add_filter( 'wpm_acf_field_text_config', array( $this, 'add_text_field_config' ) );
		add_filter( 'wpm_acf_field_textarea_config', array( $this, 'add_text_field_config' ) );
		add_filter( 'wpm_acf_field_wysiwyg_config', array( $this, 'add_text_field_config' ) );
		add_filter( 'wpm_acf_text_config', '__return_empty_array' );
		add_filter( 'wpm_acf_textarea_config', '__return_empty_array' );
		add_filter( 'wpm_acf_wysiwyg_config', '__return_empty_array' );
		add_filter( 'wpm_acf_image_config', '__return_empty_array' );
		add_filter( 'wpm_admin_pages', array( $this, 'add_dynamic_pages' ) );
		add_filter( 'wpm_filter_xliff_data', array( $this, 'add_acf_fields_to_export' ), 10, 2 );

	}


	/**
	 * Add config for 'acf' post types
	 *
	 * @param $config
	 *
	 * @return mixed
	 */
	public function add_config( $config ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) ) {
			$config = array(
				'post_content' => null,
				'post_excerpt' => null,
			);
		}

		return $config;
	}


	/**
	 * Save field object with translation
	 *
	 * @param $field
	 *
	 * @return array|bool|string
	 */
	public function update_field( $field ) {

		$old_field = maybe_unserialize( get_post_field( 'post_content', $field['ID'], 'edit' ) );

		if ( ! $old_field ) {
			return $field;
		}

		$old_field          = wpm_array_merge_recursive( $field, $old_field );
		$old_field          = wpm_value_to_ml_array( $old_field );
		$field_name         = get_post_field( 'post_title', $field['ID'], 'edit' );
		$old_field['label'] = wpm_value_to_ml_array( $field_name );

		$default_config = array(
			'label'        => array(),
			'placeholder'  => array(),
			'instructions' => array(),
		);

		$acf_field_config = apply_filters( "wpm_acf_field_{$field['type']}_config", $default_config );
		$acf_field_config = apply_filters( "wpm_acf_field_name_{$field['name']}_config", $acf_field_config );

		$new_field = wpm_set_language_value( $old_field, $field, $acf_field_config );
		$field     = wpm_array_merge_recursive( $field, $new_field );
		$field     = wpm_ml_value_to_string( $field );

		return $field;
	}

	/**
	 * Add translate config for text fields.
	 *
	 * @param $config
	 *
	 * @return array
	 */
	public function add_text_field_config( $config ) {
		$config['default_value'] = array();

		return $config;
	}


	/**
	 * Save value with translation
	 *
	 * @param $value
	 * @param $post_id
	 * @param $field
	 *
	 * @return array|bool|string
	 */
	public function update_value( $value, $post_id, $field ) {

		if ( wpm_is_ml_value( $value ) ) {
			return $value;
		}

		$info = acf_get_post_id_info( $post_id );

		switch ( $info['type'] ) {

			case 'post':
				$post_type = get_post_type( $info['id'] );
				if ( ! $post_type || null === wpm_get_post_config( $post_type ) ) {
					return $value;
				}

				break;

			case 'term':
				$term = get_term( $info['id'] );
				if ( ! $term || is_wp_error( $term ) || null === wpm_get_taxonomy_config( $term->taxonomy ) ) {
					return $value;
				}
		}

		$acf_field_config = apply_filters( "wpm_acf_{$info['type']}_config", null, $value, $post_id, $field );
		$acf_field_config = apply_filters( "wpm_acf_{$field['type']}_config", $acf_field_config, $value, $post_id, $field );
		$acf_field_config = apply_filters( "wpm_acf_name_{$field['_name']}_config", $acf_field_config, $value, $post_id, $field );

		if ( null === $acf_field_config ) {
			return $value;
		}

		remove_filter( 'acf/load_value', 'wpm_translate_value', 6 );
		$old_value = get_field( $field['name'], $post_id, false );
		add_filter( 'acf/load_value', 'wpm_translate_value', 6 );

		$value = wpm_set_new_value( $old_value, $value, $acf_field_config );

		return $value;
	}
	
	/**
	 * Add dynamic option pages to config
	 * @param 	$config 	array
	 * @return 	$config 	array
	 * @since 	2.4.19
	 * */
	public function add_dynamic_pages( $config ) {
		
		$posts = get_posts([
		    'post_type'      => 'acf-ui-options-page',
		    'post_status'    => 'publish',
		    'numberposts'    => -1, // Get all published posts
		]);

		if ( ! empty( $posts ) && is_array( $posts ) ) {
			foreach ( $posts as $option_page ) {
				if ( is_object( $option_page ) && ! empty( $option_page->post_content ) ) {
					$content 	=	maybe_unserialize( wpm_translate_string( $option_page->post_content ) );
					if ( is_array( $content ) && ! empty( $content['menu_slug'] ) ) {
						$parent 	=	$content['parent_slug'];
						$page_id 	=	'toplevel_page_' . $content['menu_slug'];
						$config[] 	=	$page_id;
						
						// If page is assigned to any of the menus the get the proper base to add it into the config
						if ( $parent != 'none' ) {
							
							if ( $parent == 'options-general.php' ) {
								$page_id 	=	'settings_page_' . $content['menu_slug']; 	
							} else if( $parent == 'tools.php' ) {
								$page_id 	=	'tools_page_' . $content['menu_slug'];
							}else{

								// If parent is assigned other than settings and tools menu then get the correct base for this
								$parse_parent	=	parse_url( $parent, PHP_URL_QUERY );
								parse_str( $parse_parent, $params );
								if ( is_array( $params ) && ! empty( $params['post_type'] ) ) {
									$delimiter	=	strpos( $params['post_type'], '-' ) !== false ? '-' : '_';
									if ( ! empty( $delimiter ) ) {
										$base 	=	explode( $delimiter, $params['post_type'] )[0];
										$page_id 	=	$base . '_' . 'page_' . $content['menu_slug'];
									}
								}

							}
							
						}

						$config[] 	=	$page_id;
					}
				}	
			}
		}
		
		return $config;

	}

	/**
	 * Get acf field and value and add it into data for export
	 * @param 	$data 	array
	 * @param 	$post 	WP_Post
	 * @return 	$data 	array
	 * @since 	2.4.21
	 * */
	public function add_acf_fields_to_export( $data, $post ) {
		// Get ACF fields
        if ( function_exists( 'get_fields' ) && ! empty( $data ) ) {

        	global $wpdb;
        	$default_lang = wpm_get_default_language();
        	$current_lang = wpm_get_language();

        	foreach ( $data as $key => $value ) {
        		if ( is_array( $value ) && ! empty( $value['id'] ) ) {
        			$acf_fields     	=   get_fields( $value['id'] );
		        	if( ! empty( $acf_fields ) ) {
		                foreach ( $acf_fields as $field_key => $field ) {

		                	if ( is_string( $field ) ) {

		                		$result 		=	$wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $value['id'], $field_key ), ARRAY_A );

		                		if ( is_array( $result ) && ! empty( $result[0] ) && is_array( $result[0] ) ) {
		                			if ( ! empty( $result[0]['meta_value'] ) && is_string( $result[0]['meta_value'] ) ) {

		                				$field_array 						=	array();
		                				$field_array['key'] 				=	$field_key;
		                				$field_array['source_value'] 		=	wpm_translate_string( $result[0]['meta_value'], $default_lang );
		                				$target_value 						=	wpm_translate_string( $result[0]['meta_value'], $current_lang );
		                				$field_array['target_value'] 		=	'';
		                				if ( $field_array['source_value'] !== $target_value ) {
		                					$field_array['target_value'] 	=	$target_value;	
		                				}
										$data[$key]['acf'][] 	=	$field_array; 	

		                			}
		                		}
		              
		                	}

		                }
		            }
        				
        		}
        	} 
            
        }
        
        return $data;
	}
}
