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
		add_filter( 'add_post_metadata', array( $this, 'add_and_translate_meta_data' ), 100, 5 );
		add_filter( 'get_post_metadata', array( $this, 'translate_meta_data' ), 100, 5 );

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
	public function get_group_field_names(){
		
		global $post;
		$dynamic_meta_fields 	=	array();
		
		if ( is_object( $post ) && ! empty( $post->ID ) ) {

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
	 * Add translation data for SCF fields
	 * @since 2.4.14
	 */
	public function add_and_translate_meta_data( $check, $object_id, $meta_key, $meta_value, $unique ){
		
		$dynamic_meta_fields 		=	$this->get_group_field_names();
		
		if ( ! empty( $dynamic_meta_fields ) && array_key_exists($meta_key, $dynamic_meta_fields) ) {

			global $post;
			if ( is_object( $post ) && ! empty( $post->ID ) ) {
				$object_id 	=	$post->ID;
			}

			$translate_key 			=	$this->prefix.$meta_key;
			$lang 					=	wpm_get_language();
			$set_value 				=	array();
			$old_value 				=	array();

			if ( ! empty( $dynamic_meta_fields[$meta_key]['is_repeatable'] ) ) {

				$old_value 			=	get_post_meta( $object_id, $translate_key, true );

				if ( ! empty( $old_value ) ) {
					$set_value 		=	$old_value;	
				}
				$set_value[$lang][] =	$meta_value;
				
			}else{
				$old_value 			=	get_post_meta( $object_id, $translate_key, true );
			}
			
			if ( empty( $dynamic_meta_fields[$meta_key]['is_repeatable'] ) ) {

				if ( empty( $old_value ) ) {

					$set_value[$lang] 	=	$meta_value;	

				}else if ( is_array( $old_value ) ) {

					$set_value 			=	$old_value;
					$set_value[$lang] 	=	$meta_value;	

				}
			}
			
			update_post_meta( $object_id, $translate_key, $set_value );

		}

		return $check;
	}

	/**
	 * Get translation data for SCF fields
	 * @since 2.4.14
	 */
	public function translate_meta_data( $value, $object_id, $meta_key, $single, $meta_type ) {

		$translate_key 		=	$this->prefix.$meta_key;
		$lang 				=	wpm_get_language();

		$get_value 			=	$this->get_post_meta( $object_id, $translate_key );

		if ( ! empty( $get_value ) && is_array( $get_value ) ) { 

			if ( isset( $get_value[0] ) && is_object( $get_value[0] ) && isset( $get_value[0]->meta_value ) ) {

				if ( ! empty( $get_value[0]->meta_value ) && is_string( $get_value[0]->meta_value ) ) {

					$get_value 	=	maybe_unserialize($get_value[0]->meta_value);
					if ( ! empty( $get_value ) && is_array( $get_value ) && isset( $get_value[$lang] ) ) {
						if ( is_string( $get_value[$lang] ) ) {
							$value[0] 	=	$get_value[$lang];
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

		$meta_value =	$wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id = %d ", $meta_key, $post_id ) );	
		return $meta_value;
	}
}