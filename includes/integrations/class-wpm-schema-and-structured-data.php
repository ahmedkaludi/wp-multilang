<?php
/**
 * Class for capability with Schema and structured data for wp plugin
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPM_Schema_Saswp
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Magazine3
 * @since 	 2.4.15
 */
class WPM_Schema_Saswp {

	const WPM_SASWP_META_LIST_VAL 			=	'saswp_meta_list_val';
	const WPM_SASWP_TAXONOMY_TERM 			=	'saswp_taxonomy_term';
	const WPM_SASWP_FIXED_TEXT 				=	'saswp_fixed_text';
	const WPM_SASWP_CUSTOM_META_FIELD 		=	'saswp_custom_meta_field';
	const WPM_SASWP_SCHEMA_TEMPLATE_FIELD 	=	'saswp_schema_template_field';

	private $object_id = 0;

	/**
	 * WPM_Schema_Saswp constructor.
	 */
	public function __construct() {

		add_filter( 'wpm_post_fields_config', array( $this, 'load_config' ) );
		add_filter( 'wpm_term_fields_config', array( $this, 'load_config' ) );

		$meta_keys = array(
			self::WPM_SASWP_META_LIST_VAL => array(
				'set_meta_list_value',
				'get_meta_list_value'
			),
			self::WPM_SASWP_TAXONOMY_TERM => array(
				'set_taxonomy_term_value',
				'get_taxonomy_term_value'
			),
			self::WPM_SASWP_FIXED_TEXT => array(
				'set_fixed_text_value',
				'get_fixed_text_value'
			),
			self::WPM_SASWP_CUSTOM_META_FIELD => array(
				'set_custom_meta_value',
				'get_custom_meta_value'
			),
			self::WPM_SASWP_SCHEMA_TEMPLATE_FIELD => array(
				'set_template_field_value',
				'get_template_field_value'
			),
		);

		//Install meta Filters
		foreach ($meta_keys as $meta_key => $callbacks) {

			add_filter( "wpm_{$meta_key}_meta_config", 			array($this, 'config'), 10, 3 );
			add_filter( "wpm_add_{$meta_key}_meta_value", 		array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_update_{$meta_key}_meta_value", 	array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_get_{$meta_key}_meta_value", 		array($this, $callbacks[1]), 10, 1 );
		}

	}

	/**
	 * Config meta keys
	 *
	 * @param $config
	 * @param $meta_value
	 * @param $object_id
	 * @return mixed
	 * @since 2.4.15
	 */
	public function config( $config, $meta_value, $object_id ) {

		$this->object_id = $object_id;

		return $config;
	}

	/**
	 * Load schema post meta keys to config array
	 * @since 2.4.15
	 */
	public function load_config( $config ) {
		
		global $wpdb;
		$schema_config 		=	array();	

		// Get schema types ids
		$schema_id_array 	= json_decode( get_transient( 'saswp_transient_schema_ids' ), true ); 
    
	    if ( ! $schema_id_array ) {
	       $schema_id_array = saswp_get_saved_schema_ids();
	    }

	    $is_term 			=	0;
	    $id 				=	0;
	    $post_type 			=	'';

	    // Get the post or term id when you are in a editor page
	    if ( is_admin() ) {

	    	// Get Post id from edit post/page
	    	// phpcs:ignore WordPress.Security.NonceVerification.Recommended --Reason Not a form submission data just accessing the values for conditional check
	    	if ( ! empty( $_GET['post'] ) ){
	    		// phpcs:ignore WordPress.Security.NonceVerification.Recommended --Reason Not a form submission data just accessing the values for conditional check
	    		$id 			=	intval( wp_unslash( $_GET['post'] ) );
	    		$post_type 		=	get_post_type( $id );
	    	}
	    	// get term id from edit page
	    	// phpcs:ignore WordPress.Security.NonceVerification.Recommended --Reason Not a form submission data just accessing the values for conditional check
	    	if ( ! empty( $_REQUEST['tag_ID'] ) ) {
	    		// phpcs:ignore WordPress.Security.NonceVerification.Recommended --Reason Not a form submission data just accessing the values for conditional check
	    		$id 			=	intval( wp_unslash( $_REQUEST['tag_ID'] ) );
	    		$is_term 		=	1;
	    	}
	    	// Get schema id from schema edit page
	    	// phpcs:ignore WordPress.Security.NonceVerification.Recommended --Reason Not a form submission data just accessing the values for conditional check
	    	if ( ! empty( $_REQUEST['post_ID'] ) ){
	    		// phpcs:ignore WordPress.Security.NonceVerification.Recommended --Reason Not a form submission data just accessing the values for conditional check
	    		$id 			=	intval( wp_unslash( $_REQUEST['post_ID'] ) );
	    		$post_type 		=	get_post_type( $id );
	    	}
	    } else{

	    	// Get the post or term id when you are on frontend
	    	$query_obj 			= get_queried_object();	
	    	if (  is_object( $query_obj ) ) {
	    		if ( ! empty( $query_obj->ID ) ) {
	    			$id 		=	$query_obj->ID;
	    		}else if( ! empty( $query_obj->term_id ) ) {
	    			$id 		=	$query_obj->term_id;
	    			$is_term 	=	1;
	    		}
	    	}

	    }

	    if ( ! empty( $schema_id_array ) && is_array( $schema_id_array ) ) {

	    	foreach ( $schema_id_array as $schema_id ) {

	    		if ( $schema_id > 0 && $id > 0 ) {

	    			$is_modify_enabled 	=	0;
	    			$modified_key 		=	'saswp_modify_this_schema_'.$schema_id;

	    			if ( $is_term == 1 ) {

	    				$cache_key    		= 	'wpm_schema_get_term_'.$modified_key.'_'.$id;
            			$termmeta_value 	= 	wp_cache_get( $cache_key );
            			if ( false === $termmeta_value ) {
	    					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery --Reason Using built function doesn't work in our case, so added manual query
	    					$termmeta_value 	=	$wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->termmeta} WHERE term_id = %d AND meta_key = %s", $id, $modified_key ) );
	    					wp_cache_set( $cache_key, $termmeta_value );
	    				}
	    				if ( is_object( $termmeta_value ) && isset( $termmeta_value->meta_value ) ) {
	    					$is_modify_enabled 	=	$termmeta_value->meta_value;
	    				}

	    			}else {

	    				$cache_key    		= 	'wpm_schema_get_post_'.$modified_key.'_'.$id;
            			$postmeta_value 	= 	wp_cache_get( $cache_key );
            			if ( false === $postmeta_value ) {
	    					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery --Reason Using built function doesn't work in our case, so added manual query
	    					$postmeta_value 	=	$wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $id, $modified_key ) );
	    					wp_cache_set( $cache_key, $postmeta_value );
	    				}
	    				if ( is_object( $postmeta_value ) && isset( $postmeta_value->meta_value ) ) {
	    					$is_modify_enabled 	=	$postmeta_value->meta_value;
	    				}

	    			}

	    			if ( $is_modify_enabled == 1 || $post_type == 'saswp' || $post_type == 'saswp_template' ) {
	    				 
			    		// Get schema type
			    		$key 			=	'schema_type';

			    		$cache_key    	= 	'wpm_schema_get_schema_meta_'.$schema_id;
            			$schema_meta 	= 	wp_cache_get( $cache_key );

            			if ( false === $schema_meta ) {
			    			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery --Reason Using built function doesn't work in our case, so added manual query
			    			$schema_meta 	=	$wpdb->get_results( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE post_id = %d", $schema_id ) );
			    			wp_cache_set( $cache_key, $schema_meta );
			    		}
			    		
			    		if ( ! empty( $schema_meta ) && is_array( $schema_meta ) ) {

			    			foreach ( $schema_meta as $meta_key => $meta_value ) {

			    				if ( is_object( $meta_value ) && isset ( $meta_value->meta_key ) && ! array_key_exists( $meta_value->meta_key, $config ) ) {

			    					$find 	=	'_'.$schema_id;

			    					if ( strpos( $meta_value->meta_key, $find ) !== false ) {
					    				$config[$meta_value->meta_key] 	=	array(); 
					    			}

				    			}

			    			}

						}
					}
				}

			}
		}
		
		return $config;

	}

	/**
	 * Set meta translate in base64
	 *
	 * @param $key
	 * @param $value
	 * @return mixed
	 * @since 2.4.15
	 */
	private function set_value( $key, $value ) {

		global $wpdb;

		if ( ! $this->object_id ) {
			return $value;
		}

		$current_value = get_post_meta( $this->object_id, "{$key}_translate", true );

		// If translate data is not present then get default value
		if ( empty( $current_value ) ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Using built function doesn't work in our case, so added manual query
			$current_value 	=	$wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id = %d", $key, $this->object_id ) );
			
			if ( ! empty( $current_value ) ) {

				$current_value 	=	base64_encode( $current_value );
				
			}
				
		}

		$db_value 	=	$value;
		if ( is_array( $db_value ) ) {
			$db_value 	=	maybe_serialize( $db_value );
		}

		update_post_meta( $this->object_id, "{$key}_translate", wpm_set_new_value( $current_value, base64_encode( $db_value ) ) );

		$this->object_id = 0;

		return $value;
	}

	/**
	 * Get meta translate from base64
	 *
	 * @param $key
	 * @param $value
	 * @return false|string
	 * @since 2.4.15
	 */
	private function get_value($key, $value) {

		if ( ! $this->object_id ) {
			return $value;
		}

		$tr_value = base64_decode( wpm_translate_value( get_post_meta( $this->object_id, "{$key}_translate", true ) ), true );
		
		$this->object_id = 0;

		if ( ! empty( $tr_value ) && is_string( $tr_value ) ) {
			return maybe_unserialize( $tr_value );
		}else{
			return $value;
		}

	}

	/**
	 * Set meta value data
	 *
	 * @param $value
	 * @return mixed
	 * @since 2.4.15
	 */
	public function set_meta_list_value( $value ) {

		$key = self::WPM_SASWP_META_LIST_VAL;

		return $this->set_value( $key, $value );
	}

		/**
	 * Get meta value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.15
	 */
	public function get_meta_list_value( $value ) {

		$key = self::WPM_SASWP_META_LIST_VAL;

		return $this->get_value( $key, $value );
	}

	/**
	 * Set taxonomy term value data
	 *
	 * @param $value
	 * @return mixed
	 * @since 2.4.15
	 */
	public function set_taxonomy_term_value( $value ) {

		$key = self::WPM_SASWP_TAXONOMY_TERM;

		return $this->set_value( $key, $value );
	}

		/**
	 * Get taxonomy term value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.15
	 */
	public function get_taxonomy_term_value( $value ) {

		$key = self::WPM_SASWP_TAXONOMY_TERM;

		return $this->get_value( $key, $value );
	}

	/**
	 * Set fixed text value data
	 *
	 * @param $value
	 * @return mixed
	 * @since 2.4.15
	 */
	public function set_fixed_text_value( $value ) {

		$key = self::WPM_SASWP_FIXED_TEXT;

		return $this->set_value( $key, $value );
	}

		/**
	 * Get fixed text value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.15
	 */
	public function get_fixed_text_value( $value ) {

		$key = self::WPM_SASWP_FIXED_TEXT;

		return $this->get_value( $key, $value );
	}

	/**
	 * Set fixed text value data
	 *
	 * @param $value
	 * @return mixed
	 * @since 2.4.15
	 */
	public function set_custom_meta_value( $value ) {

		$key = self::WPM_SASWP_CUSTOM_META_FIELD;

		return $this->set_value( $key, $value );
	}

		/**
	 * Get fixed text value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.15
	 */
	public function get_custom_meta_value( $value ) {

		$key = self::WPM_SASWP_CUSTOM_META_FIELD;

		return $this->get_value( $key, $value );
	}

	/**
	 * Set fixed text value data
	 *
	 * @param $value
	 * @return mixed
	 * @since 2.4.15
	 */
	public function set_template_field_value( $value ) {

		$key = self::WPM_SASWP_SCHEMA_TEMPLATE_FIELD;

		return $this->set_value( $key, $value );
	}

		/**
	 * Get fixed text value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.15
	 */
	public function get_template_field_value( $value ) {

		$key = self::WPM_SASWP_SCHEMA_TEMPLATE_FIELD;

		return $this->get_value( $key, $value );
	}

	/**
	 * Auto translate saswp_reviews post type data
	 * @param 	$post 		WP_Post
	 * @param 	$source 	string	
	 * @param 	$target 	string	
	 * @return 	$response	array
	 * @since 	1.10
	 * */
	public static function auto_translate( $post, $source, $target ){
		
		global $wpdb;
		$response 					=	array('status'=>true, 'message'=>esc_html__('Already Processed','wp-multilang'));

		if ( $post && isset( $post->ID ) ) {

			$post_id 				=	$post->ID;
			$post_arr 				= 	array();
			$should_update 			= 	false;

			$post_title 			=	$post->post_title;

			$is_title_exist 		= 	wpm_ml_check_language_string( $post_title, $target );
			
			
			if ( $is_title_exist === false ) {

				$is_src_title_exist = wpm_ml_check_language_string( $post_title, $source );

				if( $is_src_title_exist === false ) {
					$post_title 	=	'[:'.$source.']'.$post_title.'[:]';
				}
				
				$source_title 		=	wpm_ml_get_language_string( $post_title, $source );
				$new_title 			=	wpm_ml_auto_translate_content( $source_title, $source, $target );
				
				$new_title 			= 	'[:'.$target.']'.$new_title.'[:]';
				$new_title_string 	=	str_replace('[:]',$new_title,$post_title);
				$post->post_title 	=	$new_title_string;
				$should_update 		=	true;

			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$post_meta 	= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key IN ('saswp_reviewer_name', 'saswp_review_text') ", $post_id ) );	

			if ( is_array( $post_meta ) && ! empty( $post_meta ) ) {

				foreach ( $post_meta as $meta ) {

					if ( is_object( $meta ) && isset( $meta->meta_value ) ) {

						$val 				=	$meta->meta_value;
						$is_val_exists 		= 	wpm_ml_check_language_string( $val, $target );
						if ( $is_val_exists === false ) {

							$is_src_val_exist = wpm_ml_check_language_string( $val, $source );

							if( $is_src_val_exist === false ) {
								$val 			=	'[:'.$source.']'.$val.'[:]';
							}
							$source_val 		=	wpm_ml_get_language_string( $val, $source );
							$new_val 			=	wpm_ml_auto_translate_content( $source_val, $source, $target );
							
							$new_val 			= 	'[:'.$target.']'.$new_val.'[:]';
							$new_val_string 	=	str_replace('[:]',$new_val,$val);
								
							update_post_meta( $post_id, $meta->meta_key, $new_val_string );
						}
						
					}

				}
					
			}

			$post_arr 		=	array( 
										'ID'			=>	$post->ID,
										'post_title'	=>	$post->post_title,

									);
			if ( $should_update == true ) {
				$result  = wp_update_post( $post_arr );

				if ( is_wp_error( $result ) ) {
					// Handle error.
					$error_message = $result->get_error_message();
					$response = array('status'=>false, 'message'=>$error_message);
				} elseif ( $result === 0 ) {
					$response = array('status'=>false, 'message'=>esc_html__('Translation can not be updated','wp-multilang'));
				} else {
					$response = array('status'=>true, 'message'=>esc_html__('Translation updated successfully','wp-multilang'));
				} 
			}
			
		}

		return $response;
	}

}