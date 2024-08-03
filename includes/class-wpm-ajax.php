<?php

namespace WPM\Includes;

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
		if ( ! empty( $_GET['wpm-ajax'] ) ) {
			if ( ! wp_doing_ajax() ) {
				define( 'DOING_AJAX', true );
			}
			if ( ! defined( 'WPM_DOING_AJAX' ) ) {
				define( 'WPM_DOING_AJAX', true );
			}
			// Turn off display_errors during AJAX events to prevent malformed JSON
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
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

		if ( ! empty( $_GET['wpm-ajax'] ) ) {
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
			'send_feedback'   	   => false,
			'subscribe_to_news_letter' => false,
			'newsletter_hide_form' => false,
			'settings_newsletter_submit' => false,
			'block_lang_switcher' => true
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_wpm_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_wpm_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				// GP AJAX can be used for frontend ajax requests
				add_action( 'wpm_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
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

		wp_send_json_success( __( 'Localization deleted', 'wp-multilang' ) );
	}

	/**
	 * Set default language for object
	 *
	 * @param $object
	 * @param $object_config
	 *
	 * @return mixed
	 */
	private static function set_default_language_for_object( $object, $object_config ) {

		$lang = wpm_get_default_language();

		foreach ( get_object_vars( $object ) as $key => $content ) {
			if ( ! isset( $object_config[ $key ] ) ) {
				continue;
			}

			switch ( $key ) {
				case 'attr_title':
				case 'post_title':
				case 'name':
				case 'title':
					$object->$key = wpm_set_new_value( $content, wpm_translate_string( $content, $lang ), $object_config[ $key ], $lang );
					break;
				case 'post_excerpt':
				case 'description':
				case 'post_content':
					if ( is_serialized_string( $content ) ) {
						$content      = unserialize( $content );
						$object->$key = serialize( wpm_set_new_value( $content, wpm_translate_value( $content, $lang ), $object_config[ $key ], $lang ) );
						break;
					}

					if ( isJSON( $content ) ) {
						$content      = json_decode( $content, true );
						$object->$key = wp_json_encode( wpm_set_new_value( $content, wpm_translate_value( $content, $lang ), $object_config[ $key ], $lang ) );
						break;
					}

					if ( ! wpm_is_ml_string( $content ) ) {
						$object->$key = wpm_set_new_value( $content, wpm_translate_string( $content, $lang ), $object_config[ $key ], $lang );
						break;
					}
			}
		}

		return $object;
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
		
		if(isset($_POST['message']) && isset($_POST['email'])){
			$message        = sanitize_textarea_field($_POST['message']); 
		    $email          = sanitize_email($_POST['email']);   
		                            
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

		             echo json_encode(array('status'=>'t'));  

		        }else{

		            echo json_encode(array('status'=>'f'));            

		        }
		        
		    }
		}
	                    
	    wp_die(); 
	}
	
	/**
	 * Triggered when any support query is sent from Help & Support tab
	 * @since 2.4.6
	 * */
	public static function send_feedback()
	{
		if ( ! current_user_can( 'manage_translations' ) ) {
			wp_die( -1 );
		}

		if( isset( $_POST['data'] ) ) {
	        parse_str( $_POST['data'], $data );
	    }

	    if(!isset($data['wpm_feedback_nonce'])){
	    	wp_die( -1 );
	    }

	    if ( !wp_verify_nonce( $data['wpm_feedback_nonce'], 'wpm_feedback_nonce' ) ){
       		return;  
    	}

		$text = '';
	    if( isset( $data['wpm_disable_text'] ) ) {
	        $text = implode( "\n\r", $data['wpm_disable_text'] );
	    }

	    $headers = array();

	    $from = isset( $data['wpm_disable_from'] ) ? $data['wpm_disable_from'] : '';
	    if( $from ) {
	    	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	        $headers[] = "From: $from";
	        $headers[] = "Reply-To: $from";
	    }

	    $subject = isset( $data['wpm_disable_reason'] ) ? $data['wpm_disable_reason'] : '(no reason given)';

	    if($subject == 'technical'){
	    	  $subject = $subject.' - WP Multilang';
	    	  
	          $text = trim($text);

	          if(!empty($text)){

	            $text = 'technical issue description: '.$text;

	          }else{

	            $text = 'no description: '.$text;
	          }
	      
	    }

	    $success = wp_mail( 'team@magazine3.in', $subject, $text, $headers );

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

        if ( !wp_verify_nonce( $_POST['wpm_security_nonce'], 'wpm_security_nonce' ) ){
           wp_die( -1 );  
        }
                        
    	$name    = isset($_POST['name'])?sanitize_text_field($_POST['name']):'';
        $email   = isset($_POST['email'])?sanitize_text_field($_POST['email']):'';
        $website = isset($_POST['website'])?sanitize_text_field($_POST['website']):'';
        
        if($email){
                
            $api_url = 'http://magazine3.company/wp-json/api/central/email/subscribe';

		    $api_params = array(
		        'name'    => $name,
		        'email'   => $email,
		        'website' => $website,
		        'type'    => 'wpmultilang'
		            );
		            
		    $response = wp_remote_post( $api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
		            $response = wp_remote_retrieve_body( $response );                    
		    echo esc_html( $response );

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

        if ( !wp_verify_nonce( $_POST['wpm_admin_settings_nonce'], 'wpm_admin_settings_nonce' ) ){
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

        if ( !wp_verify_nonce( $_POST['wpm_admin_settings_nonce'], 'wpm_admin_settings_nonce' ) ){
           wp_die( -1 );  
        } 

	    if(issset($_POST['email']) && !empty($_POST['email'])){
			global $current_user;
			$api_url = 'http://magazine3.company/wp-json/api/central/email/subscribe';
		    $api_params = array(
		        'name' => sanitize_text_field($current_user->display_name),
		        'email'=> sanitize_email($_POST['email']),
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
        if ( ! isset( $_POST['security'] ) ){
            wp_die( -1 ); 
        }

        if ( !wp_verify_nonce( $_POST['security'], 'wpm_ajax_security_nonce' ) ){
           wp_die( -1 );  
        } 

        if(empty($_POST['current_url'])){
        	wp_die( -1 );  
        }

        $all_languages = wpm_get_languages();

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
}
