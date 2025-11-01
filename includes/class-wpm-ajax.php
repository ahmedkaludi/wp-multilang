<?php

namespace WPM\Includes;
use WPM\Includes\Admin\WPM_Reset_Settings;
use WPM\Includes\Admin\WPM_OpenAI;
use WPM\Includes\Admin\Settings\WPM_Settings_Auto_Translate_Pro;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WP_Multilang WPM_AJAX.
 *
 * AJAX Event Handler.
 *
 * @class    WPM_AJAX
 * @package  WPM/Classes
 * @category Class
 * @author   Valentyn Riaboshtan
 */
class WPM_AJAX {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'do_wpm_ajax' ), 0 );
		self::add_ajax_events();
	}

	/**
	 * Get WPM Ajax Endpoint.
	 *
	 * @param  string $request Optional
	 *
	 * @return string
	 */
	public static function get_endpoint( $request = '' ) {
		return esc_url_raw( add_query_arg( 'wpm-ajax', $request ) );
	}

	/**
	 * Set WPM AJAX constant and headers.
	 */
	public static function define_ajax() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this is a dependent function and its all security measurament is done wherever it has been used.
		if ( ! empty( $_GET['wpm-ajax'] ) ) {
			if ( ! wp_doing_ajax() ) {
				define( 'DOING_AJAX', true );
			}
			if ( ! defined( 'WPM_DOING_AJAX' ) ) {
				define( 'WPM_DOING_AJAX', true );
			}
			// Turn off display_errors during AJAX events to prevent malformed JSON
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged --Reason Turn off display_errors during AJAX events to prevent malformed JSON
				@ini_set( 'display_errors', 0 );
			}
			$GLOBALS['wpdb']->hide_errors();
		}
	}

	/**
	 * Send headers for WPM Ajax Requests
	 */
	private static function wpm_ajax_headers() {
		send_origin_headers();
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		@header( 'X-Robots-Tag: noindex' );
		send_nosniff_header();
		nocache_headers();
		status_header( 200 );
	}

	/**
	 * Check for WPM Ajax request and fire action.
	 */
	public static function do_wpm_ajax() {
		global $wp_query;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this is a dependent function and its all security measurament is done wherever it has been used.
		if ( ! empty( $_GET['wpm-ajax'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, 	WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- this is a dependent function and its all security measurament is done wherever it has been used.
			$wp_query->set( 'wpm-ajax', sanitize_text_field( $_GET['wpm-ajax'] ) );
		}

		if ( $action = $wp_query->get( 'wpm-ajax' ) ) {
			self::wpm_ajax_headers();
			do_action( 'wpm_ajax_' . sanitize_text_field( $action ) );
			die();
		}
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		$ajax_events = array(
			'delete_lang'          => false,
			'delete_localization'  => false,
			'qtx_import'           => false,
			'rated'                => false,
			'send_query_message'   => false,
			'deactivate_plugin'    => false,
			'subscribe_to_news_letter' => false,
			'newsletter_hide_form' => false,
			'settings_newsletter_submit' => false,
			'block_lang_switcher' => true,
			'reset_settings' 		=> true,
			'validate_secret_key' 	=> false,
			'save_openai_settings' 	=> false,
			'do_auto_translate' 		=> false,
			'singlular_auto_translate' 		=> false,
			'singlular_auto_translate_term' => false,
			'get_translation_node_count' => false,
			'process_batch_translation' => false,
		);
		
		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_wpm_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_wpm_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				// GP AJAX can be used for frontend ajax requests
				add_action( 'wpm_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}

		// Add direct AJAX action for wpmpro_search_items
		add_action( 'wp_ajax_wpmpro_search_items', array( __CLASS__, 'wpmpro_search_items' ) );

		// Add direct AJAX action for bulk auto translate
		add_action( 'wp_ajax_wpm_do_auto_translate', array( __CLASS__, 'do_auto_translate' ) );
	}

	/**
	 * Remove installed language files and option
	 */
	public static function delete_lang() {

		check_ajax_referer( 'delete-lang', 'security' );

		$language = wpm_get_post_data_by_key( 'language' );
		$options  = wpm_get_lang_option();

		if ( ! $language || ! isset( $options[ $language ] ) || ( wpm_get_user_language() === $language ) || ( wpm_get_default_language() === $language ) ) {
			return;
		}

		unset( $options[ $language ] );

		global $wpdb;
		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: using WP bulit in function updates the option of current language which does not work for our plugin in this case
		$wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $options ) ), array( 'option_name' => 'wpm_languages' ) );

		die();
	}

	/**
	 * Remove installed language files and option
	 */
	public static function delete_localization() {

		check_ajax_referer( 'delete-localization', 'security' );

		$locale  = wpm_get_post_data_by_key( 'locale' );
		$options = wpm_get_lang_option();

		if ( ! $locale ) {
			wp_send_json_error( __( 'No locale sending', 'wp-multilang' ) );
		}

		foreach ( $options as $language ) {
			if ( $language['translation'] == $locale ) {
				wp_send_json_error( __( 'Localization using', 'wp-multilang' ) );
			}
		}

		$files_delete                  = array();
		$installed_plugin_translations = wp_get_installed_translations( 'plugins' );

		foreach ( $installed_plugin_translations as $plugin => $translation ) {
			if ( isset( $translation[ $locale ] ) ) {
				$files_delete[] = WP_LANG_DIR . '/plugins/' . $plugin . '-' . $locale . '.mo';
				$files_delete[] = WP_LANG_DIR . '/plugins/' . $plugin . '-' . $locale . '.po';
			}
		}

		$installed_themes_translations = wp_get_installed_translations( 'themes' );

		foreach ( $installed_themes_translations as $theme => $translation ) {
			if ( isset( $translation[ $locale ] ) ) {
				$files_delete[] = WP_LANG_DIR . '/themes/' . $theme . '-' . $locale . '.mo';
				$files_delete[] = WP_LANG_DIR . '/themes/' . $theme . '-' . $locale . '.po';
			}
		}

		$installed_core_translations = wp_get_installed_translations( 'core' );

		foreach ( $installed_core_translations as $wp_file => $translation ) {
			if ( isset( $translation[ $locale ] ) ) {
				$files_delete[] = WP_LANG_DIR . '/' . $wp_file . '-' . $locale . '.mo';
				$files_delete[] = WP_LANG_DIR . '/' . $wp_file . '-' . $locale . '.po';
			}
		}

		$files_delete[] = WP_LANG_DIR . '/' . $locale . '.po';
		$files_delete[] = WP_LANG_DIR . '/' . $locale . '.mo';

		foreach ( $files_delete as $file ) {
			wp_delete_file( $file );
		}

		wp_send_json_success( esc_html__( 'Localization deleted', 'wp-multilang' ) );
	}

	/**
	 * Import translations for terms from qTranslate
	 *
	 * @author   Soft79
	 */
	public static function qtx_import() {

		check_ajax_referer( 'qtx-import', 'security' );

		$term_count = 0;

		if ( $qtranslate_terms = get_option( 'qtranslate_term_name', array() ) ) {

			$taxonomies = get_taxonomies();
			$terms      = get_terms( array('taxonomy' => $taxonomies, 'hide_empty' => false ) );

			foreach ( $terms as $term ) {
				$original = $term->name;

				//Translation available?
				if ( ! isset( $qtranslate_terms[ $original ] ) ) {
					continue;
				}

				//Translate the name
				$strings = wpm_value_to_ml_array( $original );
				foreach ( $qtranslate_terms[ $original ] as $code => $translation ) {
					$strings = wpm_set_language_value( $strings, $translation, array(), $code );
				}

				//Update the name
				$term->name = wpm_ml_value_to_string( $strings );
				if ( $term->name !== $original ) {
					$result = wp_update_term( $term->term_id, $term->taxonomy, array( 'name' => $term->name ) );
					if ( ! is_wp_error( $result ) ) {
						$term_count++;
					}
				}
			}

			delete_option( 'qtranslate_term_name' );
		}

		/* translators: %d: This will get the number of term counts. */
		wp_send_json( sprintf( __( '%d terms were imported successfully.', 'wp-multilang' ), $term_count ) );
	}

	/**
	 * Triggered when clicking the rating footer.
	 */
	public static function rated() {
		if ( ! current_user_can( 'manage_translations' ) ) {
			wp_die( -1 );
		}
		update_option( 'wpm_admin_footer_text_rated', 1 );
		wp_die();
	}

	/**
	 * Triggered when any support query is sent from Help & Support tab
	 * @since 2.4.2
	 * */
	public static function send_query_message()
	{
		check_ajax_referer( 'support-localization', 'security' );

		if ( ! current_user_can( 'manage_translations' ) ) {
			wp_die( -1 );
		}
		
		if ( isset( $_POST['message'] ) && isset( $_POST['email'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason unslash not needed because data is not getting stored in database, it's just being used. 
			$message        = sanitize_textarea_field( $_POST['message'] ); 
		    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason unslash not needed because data is not getting stored in database, it's just being used. 
		    $email          = sanitize_email( $_POST['email'] );   
		                            
		    if(function_exists('wp_get_current_user')){

		        $user           = wp_get_current_user();

		        $message = '<p>'.esc_html($message).'</p><br><br>'.'Query from WP Multilang plugin support tab';
		        
		        $user_data  = $user->data;        
		        $user_email = $user_data->user_email;     
		        
		        if($email){
		            $user_email = $email;
		        }            
		        //php mailer variables        
		        $sendto    = 'team@magazine3.in';
		        $subject   = "WP Multilang Query";
		        
		        $headers[] = 'Content-Type: text/html; charset=UTF-8';
		        $headers[] = 'From: '. esc_attr($user_email);            
		        $headers[] = 'Reply-To: ' . esc_attr($user_email);
		        // Load WP components, no themes.   

		        $sent = wp_mail($sendto, $subject, $message, $headers); 

		        if($sent){

		             echo wp_json_encode(array('status'=>'t'));  

		        }else{

		            echo wp_json_encode(array('status'=>'f'));            

		        }
		        
		    }
		}
	                    
	    wp_die(); 
	}
	
	/**
	 * Trigger when delete translation check box checked from popup modal when deactivating the plugin
	 * @since 2.4.17
	 * */
	public static function deactivate_plugin()
	{
		// if ( ! current_user_can( 'manage_translations' ) ) {
		// 	wp_die( -1 );
		// }

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Security measurament is done below in this function with nonce key wpm_feedback_nonce.
		if( isset( $_POST['data'] ) ) {
	        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason: Sanitization is handled below in this function
	        parse_str( $_POST['data'], $data );
	    }

	    if ( ! isset( $data['wpm_deactivate_plugin_nonce'] ) ) {
	    	wp_die( -1 );
	    }

	    if ( ! wp_verify_nonce( $data['wpm_deactivate_plugin_nonce'], 'wpm_deactivate_plugin_nonce' ) ) {
       		wp_die( -1 ); 
    	}

    	// Reset translation data
    	if ( ! empty( $data['wpm_uninstall_translations'] ) ) {
    		
    		if ( class_exists('WPM\Includes\Admin\WPM_Reset_Settings') ) {
    			
    			\WPM\Includes\Admin\WPM_Reset_Settings::wpm_uninstall_translations_data();
    		}

    	}

		wp_die();
	}
	
	/**
	 * Triggered when any newsletter subscribe button is clicked
	 * @since 2.4.7
	 * */
	public static function subscribe_to_news_letter(){
  
		if(!current_user_can('manage_options')){
            wp_die( -1 );    
        }

        if ( ! isset( $_POST['wpm_security_nonce'] ) ){
            wp_die( -1 ); 
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason unslash not needed because data is not getting stored in database, it's just being used. 
        if ( !wp_verify_nonce( $_POST['wpm_security_nonce'], 'wpm_security_nonce' ) ){
           wp_die( -1 );  
        }
                        
    	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $email   = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $website = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : '';
        
        if($email){
                
            $api_url = 'http://magazine3.company/wp-json/api/central/email/subscribe';

		    $api_params = array(
					'name'    => $name,
					'email'   => $email,
					'website' => $website,
					'type'    => 'wpmultilang',
		        );
		            
		    wp_remote_post( $api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );		    

        }else{
                echo esc_html__('Email id required', 'wp-multilang');                        
        }                        

        wp_die();
	}

	/**
	 * Triggered when clicked on close button of newsletter form present in settings 
	 * @since 2.4.7
	 * */
	public static function newsletter_hide_form(){  
		if(!current_user_can('manage_options')){
            wp_die( -1 );    
        }

        if ( ! isset( $_POST['wpm_admin_settings_nonce'] ) ){
            wp_die( -1 ); 
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason unslash not needed because data is not getting stored in database, it's just being used. 
        if ( ! wp_verify_nonce( $_POST['wpm_admin_settings_nonce'], 'wpm_admin_settings_nonce' ) ) {
           wp_die( -1 );  
        } 

		update_option( 'wpm_hide_newsletter', 'yes' , false);

		echo wp_json_encode(array('status'=>200, 'message'=>esc_html__('Submitted ','wp-multilang')));

	    wp_die();
	}

	/**
	 * Triggered when clicked on subscribe button of newsletter form present in settings
	 * @since 2.4.7
	 * */
	public static function settings_newsletter_submit(){  
		if(!current_user_can('manage_options')){
            wp_die( -1 );    
        }

        if ( ! isset( $_POST['wpm_admin_settings_nonce'] ) ){
            wp_die( -1 ); 
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason unslash not needed because data is not getting stored in database, it's just being used. 
        if ( !wp_verify_nonce( $_POST['wpm_admin_settings_nonce'], 'wpm_admin_settings_nonce' ) ){
           wp_die( -1 );  
        } 

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason unslash not needed because data is not getting stored in database, it's just being used.
	    if ( isset ( $_POST['email'] ) && ! empty( $_POST['email'] ) ){
			global $current_user;
			$api_url = 'http://magazine3.company/wp-json/api/central/email/subscribe';
		    $api_params = array(
		        'name' => sanitize_text_field($current_user->display_name),
		        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason unslash not needed because data is not getting stored in database, it's just being used.
		        'email'=> sanitize_email( $_POST['email'] ),
		        'website'=> sanitize_url( get_site_url() ),
		        'type'=> 'wpmultilang'
		    );

		    $response = wp_remote_post( $api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
			if ( !is_wp_error( $response ) ) {
				$response = wp_remote_retrieve_body( $response );
				echo wp_json_encode(array('status'=>200, 'message'=>esc_html__('Submitted ','wp-multilang'), 'response'=> $response));
			}else{
				echo wp_json_encode(array('status'=>500, 'message'=>esc_html__('No response from API','wp-multilang')));	
			}
		    wp_die();
		}
	}

	/**
	 * Prepare url to change the href of block language switcher
	 * @since 2.4.9
	 * */
	public static function block_lang_switcher(){  
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason unslash not needed because data is not getting stored in database, it's just being used.
        if ( ! isset( $_POST['security'] ) ){
            wp_die( -1 ); 
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason unslash not needed because data is not getting stored in database, it's just being used.
        if ( !wp_verify_nonce( $_POST['security'], 'wpm_ajax_security_nonce' ) ) {
           wp_die( -1 );  
        } 

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason unslash not needed because data is not getting stored in database, it's just being used.
        if( empty( $_POST['current_url'] ) ) {
        	wp_die( -1 );  
        }

        $all_languages = wpm_get_languages();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason unslash not needed because data is not getting stored in database, it's just being used.
		$current_url = sanitize_url($_POST['current_url']);

		$translated_urls = array();
		if(!empty($all_languages) && is_array($all_languages)){
			foreach ($all_languages as $al_key => $al_value) {
				$translated_urls[$al_key] = wpm_translate_url( $current_url, $al_key );
			}
		}

		echo wp_json_encode($translated_urls);
		wp_die();
    }
    
    /**
     * Reset plugin settings to default
     * @since 2.4.15
     * */
    public static function reset_settings() {

		check_ajax_referer( 'wpm-reset-settings', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1 );    
        }
		
		$reset_obj 	=	new WPM_Reset_Settings();
		$reset_obj->reset_settings();

		wp_die();

	}

	/**
     * Validate API secret key
     * @since 2.4.23
     * */
    public static function validate_secret_key() {

    	check_ajax_referer( 'wpmpro-openai-nonce', 'security' );

    	if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1 );    
        }

        try {
        	$result 	=	WPM_OpenAI::validate_secret_key();
        	if ( ! empty( $result['models'] ) ) {
        		$models 		=	$result['models'];
        		$provider 		=	$result['provider'];

	        	$api_settings 	=	WPM_OpenAI::get_settings();
		        $api_settings['api_keys'][$provider] 					=	$result['api_key'];
		        $api_settings['api_provider'] 							=	$provider;
		        $api_settings['api_available_models'][$provider] 		=	$models;
		     	
		        update_option( 'wpm_openai_settings', $api_settings );

		        wp_send_json_success( $result );
        	}

        } catch (\Exception $e) {
        	wp_send_json_error(['message' => 'API Validation failed: ' . $e->getMessage()]);
    	}

	}

	/**
     * Save openai settings
     * @since 2.4.23
     * */
    public static function save_openai_settings() {

    	check_ajax_referer( 'wpmpro-openai-nonce', 'security' );

    	if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1 );    
        }

        WPM_OpenAI::save_settings();
    }


	/**
	 * Process batch translation
	 * @since 	1.10
	 * */
	public static function process_batch_translation(){
		
		if ( ! wp_verify_nonce( $_POST['wpmpro_autotranslate_singular_nonce'], 'wpmpro-autotranslate-singular-nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'security nonce not verify', 'wp-multilang' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
		    wp_send_json_error( array( 'message' => esc_html__( 'not authorized', 'wp-multilang' ) ) );
		}
		
		if ( ! empty( $_POST['post_id'] ) && ! empty( $_POST['source'] ) && ! empty( $_POST['target'] ) && isset( $_POST['batch_start'] ) && isset( $_POST['batch_size'] ) ) {

			$post_id 		=	intval( $_POST['post_id'] );
			$batch_start 	=	intval( $_POST['batch_start'] );
			$batch_size 	=	intval( $_POST['batch_size'] );
			
			if ( $post_id <= 0 ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Not a valid post', 'wp-multilang' ) ) );
			}

			$source 		=	sanitize_text_field( wp_unslash( $_POST['source'] ) );
			$target 		=	sanitize_text_field( wp_unslash( $_POST['target'] ) );
			$post 			=	get_post( $post_id );
			
			if ( ! $post ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Post not found', 'wp-multilang' ) ) );
			}

			$response = WPM_Settings_Auto_Translate_Pro::auto_translate_batch( $post, $source, $target, $batch_start, $batch_size );
			
			wp_send_json( $response );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Missing required parameters', 'wp-multilang' ) ) );
		}
	}

	/**
	 * Auto translate single post, product, page custom post types
	 * @since 	1.10
	 * */
	public static function singlular_auto_translate(){
		
		// Set longer execution time and prevent timeouts
		set_time_limit(1200); // Increased to 20 minutes
		ini_set('max_input_time', 1200);
		ini_set('default_socket_timeout', 120);
		
		// Send headers to prevent browser timeout
		header('Content-Type: application/json');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	
		
		if ( ! wp_verify_nonce( $_POST['wpmpro_autotranslate_singular_nonce'], 'wpmpro-autotranslate-singular-nonce' ) ) {
			echo esc_html__( 'security nonce not verify', 'wp-multilang' );
			wp_die();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
		    echo esc_html__( 'not authorized', 'wp-multilang' );
		    wp_die();
		}
		
		if ( ! empty( $_POST['post_id'] ) && ! empty( $_POST['source'] ) && ! empty( $_POST['target'] ) ) {

			$post_id 		=	intval( $_POST['post_id'] );
			if ( $post_id <= 0 ) {
				echo esc_html__( 'Not a valid post', 'wp-multilang' );
		    	wp_die();	
			}

			$source 		=	sanitize_text_field( wp_unslash( $_POST['source'] ) );
			$target 		=	sanitize_text_field( wp_unslash( $_POST['target'] ) );
			$post 			=	get_post( $post_id );
			$post_type 		=	get_post_type( $post_id );
			$response 		=	array();
		
			// DEBUG: Start comprehensive debugging
			$debug_log = array();
			$debug_log['timestamp'] = current_time('mysql');
			$debug_log['post_id'] = $post_id;
			$debug_log['post_type'] = $post_type;
			$debug_log['source_lang'] = $source;
			$debug_log['target_lang'] = $target;
			$debug_log['post_title'] = $post->post_title;
			$debug_log['post_content_length'] = strlen($post->post_content);
			
			// DEBUG: Check Elementor data
			$elementor_data = get_post_meta($post_id, '_elementor_data', true);
			$debug_log['has_elementor_data'] = !empty($elementor_data);
			$debug_log['elementor_data_length'] = $elementor_data ? strlen($elementor_data) : 0;
			
			// DEBUG: Check Elementor translate meta
			$elementor_translate = get_post_meta($post_id, '_elementor_data_translate', true);
			$debug_log['has_elementor_translate'] = !empty($elementor_translate);
			$debug_log['elementor_translate_length'] = $elementor_translate ? strlen($elementor_translate) : 0;
			
			// DEBUG: Check for Rank Math meta fields
			$rank_math_metas = array();
			$all_metas = get_post_meta($post_id);
			foreach ($all_metas as $meta_key => $meta_value) {
				if (strpos($meta_key, 'rank_math') === 0) {
					$rank_math_metas[$meta_key] = is_array($meta_value) ? $meta_value[0] : $meta_value;
				}
			}
			$debug_log['rank_math_metas'] = $rank_math_metas;
			$debug_log['rank_math_meta_count'] = count($rank_math_metas);
			
			// DEBUG: Check for other SEO meta fields
			$seo_metas = array();
			foreach ($all_metas as $meta_key => $meta_value) {
				if (strpos($meta_key, '_yoast') === 0 || strpos($meta_key, 'rank_math') === 0 || strpos($meta_key, 'seopress') === 0) {
					$seo_metas[$meta_key] = is_array($meta_value) ? $meta_value[0] : $meta_value;
				}
			}
			$debug_log['seo_metas'] = $seo_metas;
			$debug_log['seo_meta_count'] = count($seo_metas);
			
			// DEBUG: Check if Elementor is active
			$debug_log['elementor_active'] = class_exists('\Elementor\Plugin');
			$debug_log['elementor_version'] = defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'Not defined';
			
			// DEBUG: Check if Rank Math is active
			$debug_log['rank_math_active'] = defined('RANK_MATH_VERSION');
			$debug_log['rank_math_version'] = defined('RANK_MATH_VERSION') ? RANK_MATH_VERSION : 'Not defined';
			
			// DEBUG: Log current post meta structure
			$debug_log['all_meta_keys'] = array_keys($all_metas);
			$debug_log['meta_keys_count'] = count($all_metas);

			try {
				switch( $post_type ) {

					case 'saswp_reviews':
						$response 		=	\WPM\Includes\Integrations\WPM_Schema_Saswp::auto_translate( $post, $source, $target );
					break;

					default:
						$response 		=	WPM_Settings_Auto_Translate_Pro::auto_translate( $post, $source, $target , true);
					break;

				}
			} catch (Exception $e) {
				$response = array('status' => false, 'message' => 'Translation failed: ' . $e->getMessage());
				$debug_log['exception'] = $e->getMessage();
			}
			
			// DEBUG: Log response details
			$debug_log['response'] = $response;
			
			// DEBUG: Check post meta after translation
			$post_meta_after = get_post_meta($post_id);
			$elementor_data_after = get_post_meta($post_id, '_elementor_data', true);
			$elementor_translate_after = get_post_meta($post_id, '_elementor_data_translate', true);
			
			$debug_log['elementor_data_after_length'] = $elementor_data_after ? strlen($elementor_data_after) : 0;
			$debug_log['elementor_translate_after_length'] = $elementor_translate_after ? strlen($elementor_translate_after) : 0;
			$debug_log['elementor_data_changed'] = ($elementor_data !== $elementor_data_after);
			$debug_log['elementor_translate_changed'] = ($elementor_translate !== $elementor_translate_after);
			
			// DEBUG: Check rank math metas after translation
			$rank_math_metas_after = array();
			foreach ($post_meta_after as $meta_key => $meta_value) {
				if (strpos($meta_key, 'rank_math') === 0) {
					$rank_math_metas_after[$meta_key] = is_array($meta_value) ? $meta_value[0] : $meta_value;
				}
			}
			$debug_log['rank_math_metas_after'] = $rank_math_metas_after;
			$debug_log['rank_math_metas_changed'] = ($rank_math_metas !== $rank_math_metas_after);
			
			// DEBUG: Write debug log to file
			$debug_file = WP_CONTENT_DIR . '/wpm_debug_translation.log';
			$debug_entry = "=== TRANSLATION DEBUG - " . current_time('Y-m-d H:i:s') . " ===\n";
			$debug_entry .= "Post ID: {$post_id} | Type: {$post_type} | Source: {$source} | Target: {$target}\n";
			$debug_entry .= "Elementor Active: " . ($debug_log['elementor_active'] ? 'Yes' : 'No') . " | Version: " . $debug_log['elementor_version'] . "\n";
			$debug_entry .= "Rank Math Active: " . ($debug_log['rank_math_active'] ? 'Yes' : 'No') . " | Version: " . $debug_log['rank_math_version'] . "\n";
			$debug_entry .= "Has Elementor Data: " . ($debug_log['has_elementor_data'] ? 'Yes' : 'No') . " | Length: " . $debug_log['elementor_data_length'] . "\n";
			$debug_entry .= "Has Elementor Translate: " . ($debug_log['has_elementor_translate'] ? 'Yes' : 'No') . " | Length: " . $debug_log['elementor_translate_length'] . "\n";
			$debug_entry .= "Rank Math Meta Count: " . $debug_log['rank_math_meta_count'] . "\n";
			$debug_entry .= "SEO Meta Count: " . $debug_log['seo_meta_count'] . "\n";
			$debug_entry .= "Elementor Data Changed: " . ($debug_log['elementor_data_changed'] ? 'Yes' : 'No') . "\n";
			$debug_entry .= "Elementor Translate Changed: " . ($debug_log['elementor_translate_changed'] ? 'Yes' : 'No') . "\n";
			$debug_entry .= "Rank Math Metas Changed: " . ($debug_log['rank_math_metas_changed'] ? 'Yes' : 'No') . "\n";
			$debug_entry .= "Note: rank_math_schema_Service is excluded from translation to prevent serialization errors\n";
			$debug_entry .= "Note: rank_math_title and rank_math_description are translated directly in original meta fields\n";
			$debug_entry .= "Response: " . json_encode($response) . "\n";
			$debug_entry .= "All Meta Keys: " . implode(', ', $debug_log['all_meta_keys']) . "\n";
			$debug_entry .= "Rank Math Metas: " . json_encode($rank_math_metas) . "\n";
			$debug_entry .= "Rank Math Metas After: " . json_encode($rank_math_metas_after) . "\n";
			$debug_entry .= "=== END DEBUG ===\n\n";
			
			file_put_contents($debug_file, $debug_entry, FILE_APPEND | LOCK_EX);
			
			// Ensure we have a valid response
			if (empty($response) || !is_array($response)) {
				$response = array('status' => false, 'message' => 'Translation completed but no response generated');
			}
			
			// Ensure we're sending proper JSON
			header('Content-Type: application/json');
			wp_send_json($response);
			wp_die();
		}

	}
    	
    public static function singlular_auto_translate_term(){
		
		if ( ! wp_verify_nonce( $_POST['wpmpro_autotranslate_singular_nonce'], 'wpmpro-autotranslate-singular-nonce' ) ) {
			echo esc_html__( 'security nonce not verify', 'wp-multilang' );
			wp_die();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
		    echo esc_html__( 'not authorized', 'wp-multilang' );
		    wp_die();
		}
		
		if ( ! empty( $_POST['tag_id'] ) && ! empty( $_POST['source'] ) && ! empty( $_POST['target'] ) ) {

			global $wpdb;

			$tag_id 		=	intval( $_POST['tag_id'] );
			if ( $tag_id <= 0 ) {
				echo esc_html__( 'Not a vlid post', 'wp-multilang' );
		    	wp_die();	
			}

			$source 		=	sanitize_text_field( wp_unslash( $_POST['source'] ) );
			$target 		=	sanitize_text_field( wp_unslash( $_POST['target'] ) );

			$query 			= $wpdb->prepare("
						    SELECT tt.*, t.* 
						    FROM {$wpdb->term_taxonomy} AS tt
						    INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
						    WHERE tt.term_id = %d
						", $tag_id
						);
			$term 			= $wpdb->get_row( $query );
			if ( is_object( $term ) && ! empty( $term ) ) {
				$response 		=	WPM_Settings_Auto_Translate_Pro::auto_translate_term( $term, $source, $target, true );
			}
			
			wp_send_json($response);
			wp_die();
		}
	}

	/**
	 * Auto translate single post, product, page custom post types
	 * @since 	1.10
	 * */
	/**
	 * Search items for exclusion list
	 */
	public static function wpmpro_search_items() {
		// Check nonce - handle both _wpnonce and nonce parameters
		$nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : (isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '');
		if (!wp_verify_nonce($nonce, 'wpmpro_search_items')) {
			wp_send_json_error('Invalid nonce');
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
		}

		$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
		$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
		$results = array();

		switch ($type) {
			case 'post':
			case 'page':
			case 'product':
				$args = array(
					'post_type' => $type,
					'post_status' => 'publish',
					's' => $search,
					'posts_per_page' => 10
				);
				$query = new \WP_Query($args);
				foreach ($query->posts as $post) {
					$results[] = array(
						'id' => $post->ID,
						'text' => $post->post_title
					);
				}
				break;

			case 'category':
			case 'post_tag':
			case 'product_cat':
				$args = array(
					'taxonomy' => $type,
					'hide_empty' => false,
					'search' => $search,
					'number' => 10
				);
				$terms = get_terms($args);
				if (!is_wp_error($terms)) {
					foreach ($terms as $term) {
						$results[] = array(
							'id' => $term->term_id,
							'text' => $term->name
						);
					}
				}
				break;
		}

		wp_send_json($results);
	}

	/**
	 * Get total node count for batch translation
	 * @since 	1.10
	 * */
	public static function get_translation_node_count(){
		
		if ( ! wp_verify_nonce( $_POST['wpmpro_autotranslate_singular_nonce'], 'wpmpro-autotranslate-singular-nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'security nonce not verify', 'wp-multilang' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
		    wp_send_json_error( array( 'message' => esc_html__( 'not authorized', 'wp-multilang' ) ) );
		}
		
		if ( ! empty( $_POST['post_id'] ) && ! empty( $_POST['source'] ) && ! empty( $_POST['target'] ) ) {

			$post_id 		=	intval( $_POST['post_id'] );
			if ( $post_id <= 0 ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Not a valid post', 'wp-multilang' ) ) );
			}

			$source 		=	sanitize_text_field( wp_unslash( $_POST['source'] ) );
			$target 		=	sanitize_text_field( wp_unslash( $_POST['target'] ) );
			$post 			=	get_post( $post_id );
			
			if ( ! $post ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Post not found', 'wp-multilang' ) ) );
			}

			// Get source content
			$post_title = $post->post_title;
			$post_content = $post->post_content;
			$post_excerpt = $post->post_excerpt;
			
			$is_title_exist = wpm_ml_check_language_string( $post_title, $target );
			$is_content_exist = wpm_ml_check_language_string( $post_content, $target );
			$is_excerpt_exist = wpm_ml_check_language_string( $post_excerpt, $target );
			
			$total_nodes = 0;
			
			// Count nodes in title
			if ( $is_title_exist === false ) {
				$is_src_title_exist = wpm_ml_check_language_string( $post_title, $source );
				if( $is_src_title_exist === false ) {
					$post_title = '[:'.$source.']'.$post_title.'[:]';
				}
				$source_title = wpm_ml_get_language_string( $post_title, $source );
				$title_nodes = wpm_ml_auto_translate_content( $source_title, $source, $target, 0, 0 );
				if ( is_array( $title_nodes ) && isset( $title_nodes['total_nodes'] ) ) {
					$total_nodes += $title_nodes['total_nodes'];
				}
			}
			
			// Count nodes in content
			if ( $is_content_exist === false ) {
				$is_src_content_exist = wpm_ml_check_language_string( $post_content, $source );
				if ( $is_src_content_exist === false ) {
					$post_content = '[:'.$source.']'.$post_content.'[:]';
				}
				$source_content = wpm_ml_get_language_string( $post_content, $source );
				$content_nodes = wpm_ml_auto_translate_content( $source_content, $source, $target, 0, 0 );
				if ( is_array( $content_nodes ) && isset( $content_nodes['total_nodes'] ) ) {
					$total_nodes += $content_nodes['total_nodes'];
				}
			}
			
			// Count nodes in excerpt
			if ( $is_excerpt_exist === false && $post_excerpt ) {
				$is_src_excerpt_exist = wpm_ml_check_language_string( $post_excerpt, $source );
				if ( $is_src_excerpt_exist === false ) {
					$post_excerpt = '[:'.$source.']'.$post_excerpt.'[:]';
				}
				$source_excerpt = wpm_ml_get_language_string( $post_excerpt, $source );
				$excerpt_nodes = wpm_ml_auto_translate_content( $source_excerpt, $source, $target, 0, 0 );
				if ( is_array( $excerpt_nodes ) && isset( $excerpt_nodes['total_nodes'] ) ) {
					$total_nodes += $excerpt_nodes['total_nodes'];
				}
			}
			
			wp_send_json_success( array( 
				'total_nodes' => $total_nodes,
				'message' => sprintf( esc_html__( 'Found %d text nodes to translate', 'wp-multilang' ), $total_nodes )
			) );
		} else {
			wp_send_json_error( array( 'message' => esc_html__( 'Missing required parameters', 'wp-multilang' ) ) );
		}
	}

	/**
	 * Auto Translation
	 * @since 1.4
	 * */
	public static function do_auto_translate() {

		// Set longer execution time and prevent timeouts
		set_time_limit(120); // 2 minutes
		ini_set('max_input_time', 120);
		ini_set('default_socket_timeout', 120);
		
		// Send headers to prevent browser timeout
		header('Content-Type: application/json');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

		if ( ! wp_verify_nonce( $_POST['wpmpro_autotranslate_nonce'], 'wpmpro-autotranslate-nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'security nonce not verify', 'wp-multilang' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
		    wp_send_json_error( array( 'message' => esc_html__( 'not authorized', 'wp-multilang' ) ) );
		}

		if ( ! isset( $_POST['post_type'] ) || ! isset( $_POST['offset'] ) || ! isset( $_POST['source'] ) || ! isset( $_POST['target'] ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Some parameters are missing', 'wp-multilang' ) ) );
		}

		$post_type 					=	sanitize_text_field( $_POST['post_type'] );
		$offset 					=	intval( $_POST['offset'] ) - 1;
		$source 					=	sanitize_text_field( $_POST['source'] );
		$target 					=	sanitize_text_field( $_POST['target'] );
		$response 					=	array('status'=>false, 'message'=>esc_html__('No content found to translate','wp-multilang'));
		$category_array 			=	array( 'category', 'post_tag', 'product_cat' ); // Supported categories

		try {
			if ( ! in_array( $post_type, $category_array ) ) {
				$posts 					= 	get_posts( array(
					'post_type'			=>	$post_type,
					'post_status'		=>	'publish',
					'posts_per_page'  	=> 	1,
					'offset' 			=> 	$offset,
					'orderby' 			=> 'ID',
					'order' 			=> 'ASC',
					)
				);
				
				if ( empty( $posts ) ) {
					$response = array('status'=>true, 'message'=>esc_html__('No posts found at this offset - continuing to next','wp-multilang'));
				} else {
					foreach($posts as $post) {
						if ( $post && isset($post->ID) ) {
							// Bulk auto-translate should NOT override existing translations
							$override = false;
							$response = WPM_Settings_Auto_Translate_Pro::auto_translate( $post, $source, $target, $override );
							break; // Only process one post per request
						}
					}
					
					// Ensure we always have a response
					if ( !isset($response) || empty($response) ) {
						$response = array('status'=>true, 'message'=>esc_html__('Post processed but no translation needed','wp-multilang'));
					}
				}
			} else if ( in_array( $post_type, $category_array ) ) {

				global $wpdb;

				$terms 					=	get_terms( 
												array(
												    'taxonomy'   => $post_type,
												    'hide_empty' => false, // Set to true if you only want tags that are assigned to posts / products
												    'number'     => 1,
												    'offset'     => $offset,
												    'orderby'    => 'term_id',
												    'order'      => 'ASC'
												)
				 							);
				if ( ! empty( $terms ) && is_array( $terms ) ) {
					foreach ( $terms as $term ) {
						if ( is_object( $term ) && isset( $term->term_id ) ) {

							// Here we could have passed $term as a parameter but this has translated name, so auto_translate_term function needs raw name and description so writing manual query is necessary here
							$query 			= $wpdb->prepare("
							    SELECT tt.*, t.* 
							    FROM {$wpdb->term_taxonomy} AS tt
							    INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
							    WHERE tt.term_id = %d
							", $term->term_id
							);
							$raw_term 			= $wpdb->get_row( $query );

							if ( is_object( $raw_term ) && isset( $raw_term->term_id ) ) {
								// Bulk auto-translate should NOT override existing translations
								$override = false;
								$response 		=	WPM_Settings_Auto_Translate_Pro::auto_translate_term( $raw_term, $source, $target, $override );
								break; // Only process one term per request
							}
						}
					}
					
					// Ensure we always have a response
					if ( !isset($response) || empty($response) ) {
						$response = array('status'=>true, 'message'=>esc_html__('Term processed but no translation needed','wp-multilang'));
					}
				} else {
					$response = array('status'=>true, 'message'=>esc_html__('No terms found at this offset - continuing to next','wp-multilang'));
				}

			}
		} catch ( Exception $e ) {
			$response = array('status'=>false, 'message'=>esc_html__('Translation failed: ','wp-multilang') . $e->getMessage());
		}
		
		// Ensure response is always valid
		if ( !isset($response) || !is_array($response) ) {
			$response = array('status'=>true, 'message'=>esc_html__('Request processed successfully','wp-multilang'));
		}
		
		// Add debugging information
		$response['debug'] = array(
			'post_type' => $post_type,
			'offset' => $offset + 1,
			'source' => $source,
			'target' => $target,
			'override' => false, // Bulk auto-translate never overrides existing translations
			'timestamp' => current_time('mysql')
		);
		
		wp_send_json($response);
	}

}
