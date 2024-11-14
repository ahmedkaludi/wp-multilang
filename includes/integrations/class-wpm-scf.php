<?php
/**
 * Class for capability with Smart Custom Fields Plugin
 * @since 2.4.14
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class    WPM_SCF
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Magazine3
 */
class WPM_SCF {

	const SMART_CF_SETTINGS = 'smart-cf-setting';

	private $object_id = 0;

	private $prefix    = 'smart_cf_';	

	/**
	 * WPM_SCF constructor.
	 */
	public function __construct() {

		$meta_keys = array(
			self::SMART_CF_SETTINGS => array(
				'set_data_value',
				'get_data_value'
			),
		);

		//Install meta Filters
		foreach ($meta_keys as $meta_key => $callbacks) {

			add_filter( "wpm_{$meta_key}_meta_config", 			array($this, 'config'), 10, 3 );
			add_filter( "wpm_add_{$meta_key}_meta_value", 		array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_update_{$meta_key}_meta_value", 	array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_get_{$meta_key}_meta_value", 		array($this, $callbacks[1]), 10, 1 );
		}

		add_action( 'add_meta_boxes', array( $this, 'translate_metabox_title' ), 100, 2 );
		
		// Hooks for Posts and pages
		add_filter( 'get_post_metadata', array( $this, 'translate_meta_data' ), 100, 5 );
		add_action( 'added_post_meta', array( $this, 'added_post_meta' ), 10, 4 );
		add_action( 'save_post_smart-custom-fields', array( $this, 'clear_post_meta_cache' ), 10, 5 );

		// Hooks for Term
		add_action( 'added_term_meta', array( $this, 'added_term_meta' ), 10, 4 );
		add_filter( 'get_term_metadata', array( $this, 'translate_term_meta_data' ), 100, 5 );

	}


	/**
	 * Set meta translate in base64
	 *
	 * @param $key
	 * @param $value
	 * @return mixed
	 * @since 2.4.14
	 */
	private function set_value( $key, $value ) {

		if ( ! $this->object_id ) {
			return $value;
		}

		$update_value 	=	$value;

		$current_value = get_post_meta( $this->object_id, "{$key}_translate", true );

		if ( ( ! empty( $update_value ) && is_array( $update_value ) ) && ( ! empty( $current_value ) ) ) {

			$translate_cur_val 	=	$current_value;
			if ( is_string( $translate_cur_val ) ) {
				$translate_cur_val 	=	json_decode( base64_decode( wpm_translate_string($current_value), true ), true );
			}	

			foreach ( $update_value as $u_key => $u_value ) {

				if ( is_array( $u_value ) && ! empty( $u_value['fields'] ) && is_array( $u_value['fields'] ) ) {

					foreach ( $u_value['fields'] as $f_key => $f_value ) {

						if ( is_array( $f_value ) && ! empty( $f_value['label'] ) ) {

							if ( ! empty( $translate_cur_val[$u_key]['fields'][$f_key]['label'] ) ) {

								$update_value[$u_key]['fields'][$f_key]['label'] = 	wpm_set_new_value( $translate_cur_val[$u_key]['fields'][$f_key]['label'], $f_value['label'] );	

							}							

						}

					}

				}

			}

			if ( is_array( $update_value ) ) {
				$update_value 	=	base64_encode( json_encode($update_value ) );
			}
		}

		update_post_meta( $this->object_id, "{$key}_translate", wpm_set_new_value( $current_value, $update_value ) );
		
		$this->object_id = 0;
		
		return $value;
	}

	/**
	 * Get meta translate from base64
	 *
	 * @param $key
	 * @param $value
	 * @return false|string
	 * @since 2.4.14
	 */
	private function get_value($key, $value) {

		if ( ! $this->object_id ) {
			return $value;
		}

		$tr_value 	=	wpm_translate_value( get_post_meta( $this->object_id, "{$key}_translate", true ) );
		if ( ! empty( $tr_value ) && is_string( $tr_value ) ) {
			$tr_value 	=	base64_decode( $tr_value, true );
		}

		$this->object_id = 0;

		if ( ! empty( $tr_value ) ) {
			return $tr_value;
		}else{
			return $value;
		}
	
	}


	/**
	 * Config meta keys
	 *
	 * @param $config
	 * @param $meta_value
	 * @param $object_id
	 * @return mixed
	 * @since 2.4.14
	 */
	public function config($config, $meta_value, $object_id) {

		$this->object_id = $object_id;

		return $config;
	}

	/**
	 * Set meta value data
	 *
	 * @param $value
	 * @return mixed
	 * @since 2.4.14
	 */
	public function set_data_value( $value ) {

		$key = self::SMART_CF_SETTINGS;

		return $this->set_value($key, $value);
	}

	/**
	 * Get meta value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.14
	 */
	public function get_data_value( $value ) {

		$key 		=	self::SMART_CF_SETTINGS;

		$value 		=	$this->get_value( $key, $value );

		if ( ! empty( $value ) && is_string( $value ) ) {
			$value 	=	json_decode( $this->get_value( $key, $value ), true );
		}	

		return $value;
	}
	
	/**
	 * Translate MetaBox Title on admin editor page
	 *
	 * @param $post_type
	 * @param $post
	 * @since 2.4.14
	 */
	public function translate_metabox_title( $post_type, $post ){
		
		global $wp_meta_boxes;

		if ( is_array( $wp_meta_boxes ) && 
			( ! empty( $wp_meta_boxes[$post_type]) && is_array( $wp_meta_boxes[$post_type] ) ) &&  
			( ! empty( $wp_meta_boxes[$post_type]['normal'] ) && is_array( $wp_meta_boxes[$post_type]['normal'] ) ) && 
			( ! empty( $wp_meta_boxes[$post_type]['normal']['default'] ) && is_array( $wp_meta_boxes[$post_type]['normal']['default'] ) ) ) {

			$default_meta_box 	=	$wp_meta_boxes[$post_type]['normal']['default'];
			foreach ( $default_meta_box as $key => $value ) {

				$prefix 		=	'smart-cf';
				if ( is_array( $value ) &&  ! empty( $value['title'] ) && strpos( $key, $prefix ) !== false ) {
					$wp_meta_boxes[$post_type]['normal']['default'][$key]['title'] 	=	wpm_translate_string( $value['title'] );
				}
			}

		}

	}

	/**
	 * Get All SCF field names
	 * @return $dynamic_meta_fields array
	 * @since 2.4.14
	 */
	public function get_group_field_names( $post ){
		
		$dynamic_meta_fields 	=	array();
		
		if ( is_object( $post ) ) {

			if ( class_exists( 'SCF' ) ) {
				$settings   = \SCF::get_settings( $post );
				if ( is_array( $settings ) && ! empty( $settings ) ) {
					foreach ( $settings as $setting ) {
						$groups = $setting->get_groups();
						foreach ( $groups as $group ) {
							$fields = $group->get_fields();
							foreach ( $fields as $field ) {
								$field_name = $field->get( 'name' );
								$dynamic_meta_fields[$field_name] 	=	array( 'is_repeatable' => $group->is_repeatable() );
							}
						}
					}
				}
			}
			
		}

		return $dynamic_meta_fields;

	}

	/**
	 * Get translation data for SCF fields
	 * @since 2.4.14
	 */
	public function translate_meta_data( $value, $object_id, $meta_key, $single, $meta_type ) {

		global $post;
		$translate_key 		=	$this->prefix.$meta_key;
		$lang 				=	wpm_get_language();

		$parent_id 			=	wp_get_post_parent_id( $object_id );
		if ( ! empty( $parent_id ) ) {
			$object_id 		=	$parent_id;	
		}
		
		$settings 			=	array();
		if ( is_object( $post ) && ! empty( $post->ID ) ) {
			$settings 			=	$this->get_settings( $post );
		}

		if ( ! empty( $settings ) && is_array( $settings ) ) {

			$setting_fields 	=	$this->get_setting_fields( $settings );
			$check_scf_field 	=	$this->is_scf_field( $setting_fields, $meta_key );

			if( ! empty( $check_scf_field ) ) {

				// Using built-in function get_post_meta is causing infinite loop here, so written custom query to get the meta data
				$get_value 			=	$this->get_post_meta( $object_id, $translate_key );
				
				if ( ! empty( $get_value ) && is_array( $get_value ) ) { 

					if ( isset( $get_value[0] ) && is_object( $get_value[0] ) && isset( $get_value[0]->meta_value ) ) {

						if ( ! empty( $get_value[0]->meta_value ) && is_string( $get_value[0]->meta_value ) ) {

							$get_value 	=	maybe_unserialize($get_value[0]->meta_value);
							if ( ! empty( $get_value ) && is_array( $get_value ) && isset( $get_value[$lang] ) ) {
								if ( is_string( $get_value[$lang] ) ) {
									$value[0] 	=	$get_value[$lang];
								}else{
									if ( ! empty( $check_scf_field['is_repeatable'] ) ) {
										$value 	=	$get_value[$lang];		
									}
								}
							}

						}
					}

				}
			}
		}

		return $value;

	}

	/**
	 * Get translation data for SCF fields
	 * @since 2.4.14
	 */
	public function translate_term_meta_data( $value, $object_id, $meta_key, $single, $meta_type ) { 

		$translate_key 		=	$this->prefix.$meta_key;
		$lang 				=	wpm_get_language();

		$term 	=	get_term( $object_id );

		$settings 			=	array();
		if ( is_object( $term ) && ! empty( $term->term_id ) ) {
			$settings 			=	$this->get_settings( $term );
		}

		if ( ! empty( $settings ) && is_array( $settings ) ) {

			$setting_fields 	=	$this->get_setting_fields( $settings );
			$check_scf_field 	=	$this->is_scf_field( $setting_fields, $meta_key );

			if( ! empty( $check_scf_field ) ) {

				// Using built-in function get_post_meta is causing infinite loop here, so written custom query to get the meta data
				$get_value 			=	$this->get_term_meta( $object_id, $translate_key );

				if ( ! empty( $get_value ) && is_array( $get_value ) ) { 

					if ( isset( $get_value[0] ) && is_object( $get_value[0] ) && isset( $get_value[0]->meta_value ) ) {

						if ( ! empty( $get_value[0]->meta_value ) && is_string( $get_value[0]->meta_value ) ) {

							$get_value 	=	maybe_unserialize($get_value[0]->meta_value);
							if ( ! empty( $get_value ) && is_array( $get_value ) && isset( $get_value[$lang] ) ) {
								if ( is_string( $get_value[$lang] ) ) {
									$value[0] 	=	$get_value[$lang];
								}else{
									if ( ! empty( $check_scf_field['is_repeatable'] ) ) {
										$value 	=	$get_value[$lang];		
									}
								}
							}

						}
					}

				}
			}

		}
		
		return $value;

	}
	
	/**
	 * Get post meta data from table
	 * @param $post_id integer
	 * @param $meta_key string
	 * @return $meta_value array
	 * @since 2.4.14
	 */
	public function get_post_meta( $post_id, $meta_key ){

		global $wpdb;

		$cache_data 	=	false;
		$cache_key 		=	$post_id.$meta_key;
		$cache_group 	=	'wpm_scf';

		if ( $meta_key == self::SMART_CF_SETTINGS ) {
			$cache_data	=	wp_cache_get( $cache_key, $cache_group );
			$meta_value = 	$cache_data;
		}

		if ( $cache_data == false  ) {

			$meta_value =	$wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id = %d ORDER BY meta_id", $meta_key, $post_id ) );

			if ( $meta_key == self::SMART_CF_SETTINGS ) {
				wp_cache_set( $cache_key, $meta_value, $cache_group );
			}

		}

		return $meta_value;
	}

	/**
	 * Get term meta data from table
	 * @param $term_id integer
	 * @param $meta_key string
	 * @return $meta_value array
	 * @since 2.4.14
	 */
	public function get_term_meta( $term_id, $meta_key ){

		global $wpdb;

		$meta_value =	$wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->termmeta} WHERE meta_key = %s AND term_id = %d ORDER BY meta_id", $meta_key, $term_id ) );
		return $meta_value;

	}

	/**
	 * Update the translated fields
	 * @param $mid integer
	 * @param $object_id integer
	 * @param $meta_key string
	 * @param $_meta_value mixed
	 * @since 2.4.14
	 */
	public function added_post_meta( $mid, $object_id, $meta_key, $_meta_value ){
		
		global $post;

		$dynamic_meta_fields 	=	$this->get_group_field_names( $post );
		
		if ( ! empty( $dynamic_meta_fields ) && array_key_exists($meta_key, $dynamic_meta_fields) ) {

			$parent_id 			=	wp_get_post_parent_id( $object_id );
			if ( ! empty( $parent_id ) ) {
				$object_id 		=	$parent_id;	
			}

			$translate_key 		=	$this->prefix.$meta_key;
			$lang 				=	wpm_get_language();
			$updated_data 		=	get_post_meta( $object_id, $translate_key, true );
			if ( empty( $updated_data ) ) {
				$updated_data 		=	array();
			}

			if ( ! empty( $dynamic_meta_fields[$meta_key]['is_repeatable'] ) ) {

				$data 			=	array();
				$get_meta 			=	$this->get_post_meta( $object_id, $meta_key );
				if ( ! empty( $get_meta ) && is_array( $get_meta ) ) {
					foreach ( $get_meta as $gkey => $gvalue ) {
						if ( is_object( $gvalue ) && isset( $gvalue->meta_value ) ) {
							$data[] 	=	$gvalue->meta_value;
						}		
					}
				}

				if ( is_array( $data ) ) {
					
					$updated_data[$lang]  	=	$data;
				}
				
			}else{
				if ( empty( $updated_data ) ) {

					$updated_data[$lang] 	=	$_meta_value;	

				}else if ( is_array( $updated_data ) ) {

					$updated_data[$lang] 	=	$_meta_value;	

				}

			}

			update_post_meta( $object_id, $translate_key, $updated_data );	
		}

	}

	/**
	 * Update the translated fields
	 * @param $mid integer
	 * @param $object_id integer
	 * @param $meta_key string
	 * @param $_meta_value mixed
	 * @since 2.4.14
	 */
	public function added_term_meta( $mid, $object_id, $meta_key, $_meta_value ){ 
		
		$term 	=	get_term( $object_id );

		$dynamic_meta_fields 	=	$this->get_group_field_names( $term );
		
		if ( ! empty( $dynamic_meta_fields ) && array_key_exists($meta_key, $dynamic_meta_fields) ) {

			$translate_key 		=	$this->prefix.$meta_key;
			$lang 				=	wpm_get_language();
			$updated_data 		=	get_term_meta( $object_id, $translate_key, true );
			if ( empty( $updated_data ) ) {
				$updated_data 	=	array();
			}

			if ( ! empty( $dynamic_meta_fields[$meta_key]['is_repeatable'] ) ) {

				$data 			=	array();
				$get_meta 			=	$this->get_term_meta( $object_id, $meta_key );
				if ( ! empty( $get_meta ) && is_array( $get_meta ) ) {
					foreach ( $get_meta as $gkey => $gvalue ) {
						if ( is_object( $gvalue ) && isset( $gvalue->meta_value ) ) {
							$data[] 	=	$gvalue->meta_value;
						}		
					}
				}

				if ( is_array( $data ) ) {
					
					$updated_data[$lang]  	=	$data;
				}
				
			}else{
				if ( empty( $updated_data ) ) {

					$updated_data[$lang] 	=	$_meta_value;	

				}else if ( is_array( $updated_data ) ) {

					$updated_data[$lang] 	=	$_meta_value;	

				}

			}

			update_term_meta( $object_id, $translate_key, $updated_data );

		}

	}

	/**
	 * Get SCF field keys 
	 * @param $wp_object integer
	 * @return $settings array
	 * @since 2.4.14
	 */
	public function get_settings( $wp_object ){

		$settings 			=	array();

		if ( ! empty( $wp_object ) && is_object( $wp_object ) ) {

			$meta      = new \Smart_Custom_Fields_Meta( $wp_object );
			
			$id        = $meta->get_id();
			$type      = $meta->get_type( false );
			$types     = $meta->get_types( false );
			$meta_type = $meta->get_meta_type();

			// IF the post that has custom field settings according to post ID,
			// don't display because the post ID would change in preview.
			// So if in preview, re-getting post ID from original post (parent of the preview).
			if ( 'post' === $meta_type && 'revision' === $wp_object->post_type ) {
				$wp_object = get_post( $wp_object->post_parent );
			}

			$settings_posts 	=	\SCF::get_settings_posts( $wp_object );


			if ( ! empty( $settings_posts ) && is_array( $settings_posts ) ) {
				
				foreach ( $settings_posts as $sp_key => $setting_post ) {

					if ( is_object( $setting_post ) && ! empty( $setting_post->ID ) ) {

						$setting_meta 	=	$this->get_post_meta( $setting_post->ID, self::SMART_CF_SETTINGS );
						
						if ( is_array( $setting_meta ) && ! empty( $setting_meta[0] ) && ! empty( $setting_meta[0]->meta_value ) && is_string( $setting_meta[0]->meta_value ) ) {
							$setting_meta	=	maybe_unserialize( $setting_meta[0]->meta_value );
							if ( ! empty( $setting_meta ) && is_array( $setting_meta ) ) {

								if ( empty( $settings ) ) {
									$settings 	=	$setting_meta;
								}else{
									$settings 	=	array_merge( $settings, $setting_meta );
								}
								
							}
						}
					}	
				}

			}
		}

		return $settings;
	}

	/**
	 * Filter the settings and and return on fields 
	 * @param $settings array
	 * @return $fields array
	 * @since 2.4.14
	 */
	public function get_setting_fields( $settings ){
		
		$fields 	=	array();

		if ( ! empty( $settings ) && is_array( $settings ) ) {
			foreach ( $settings as $set_key => $set_value ) {
				$data 					=	array();
				$data['field_name']		=	'';
				$data['is_repeatable']	=	'';

				if ( ! empty( $set_value['fields'] ) && ! empty( $set_value['fields'][0] ) && ! empty( $set_value['fields'][0]['name'] ) ) {
					$data['field_name'] = 	$set_value['fields'][0]['name'];
				}	

				$data['is_repeatable'] 	= 	isset( $set_value['repeat'] ) ? $set_value['repeat'] : '';
				$fields[] 				=	$data;
			}
		}

		return $fields;
	}

	/**
	 * Check if current post meta key is a SCF field
	 * @param $fields array
	 * @param $key string
	 * @return $match array
	 * @since 2.4.14
	 */
	public function is_scf_field( $fields, $key ){

		$match 	=	array();
		foreach ($fields as $fkey => $field) {
			if ( $field['field_name'] == $key ) {
				$match 	=	$field;
				break;
			}
		}
		
		return $match;
	}
	
	/**
	 * Clear post meta cache once SCF post is saved
	 * @param $post_id integer
	 * @param $post WP_Post
	 * @param $update bool
	 * @since 2.4.14
	 * */
	public function clear_post_meta_cache( $post_id, $post, $update ){
		
		$cache_key 		=	$post_id.self::SMART_CF_SETTINGS;
		$cache_group 	=	'wpm_scf';
		wp_cache_delete( $cache_key, $cache_group );
	}

}