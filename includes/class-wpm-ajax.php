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
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- this is a dependent function and its all security measurament is done wherever it has been used.
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
			'block_lang_switcher' => true,
			'do_auto_translate' => true
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
	public static function do_auto_translate(){
		$post_type = $_POST['post_type'];
		$offset = $_POST['offset']-1;
		$source = $_POST['source'];
		$target_languages = $_POST['target'];
		$what_arr = $_POST['what_arr'];
		$total_loop = $_POST['total_loop'];
		$published_post_count = 0;
		$total_pages = 0;
		$total_product = 0;
		if(in_array('post',$what_arr)){
			$total_post_arr = wp_count_posts('post');
			$published_post_count = $total_post_arr->publish;
			if($published_post_count==""){
				$published_post_count = 0;
			}
		}
		if(in_array('page',$what_arr)){
			$count_pages = wp_count_posts('page'); 
			$total_pages = $count_pages->publish; 
			if($total_pages==""){
				$total_pages = 0;
			}
		}
		if(in_array('product',$what_arr)){
			$count_product = wp_count_posts('product'); 
			$total_product = $count_product->publish;
			if($total_product==""){
				$total_product = 0;
			}
		}
		$total_record = $published_post_count + $total_pages + $total_product;

		
		
		$posts = array();
		if($post_type=='post' && $published_post_count>0 && in_array('post',$what_arr)){
			$posts = get_posts( array('post_type'=>'post','post_status'=>'publish','posts_per_page'  => 1,'offset' => $offset,'orderby' => 'ID','order' => 'ASC'));
			$post_arr = array();
			$should_update = false;
			foreach ($posts as $key => $value) {
				
				for ($i=0; $i < count($target_languages); $i++) { 
					$post_title = $value->post_title;
					$post_content = $value->post_content;
					/* $post_title = str_replace('<!-- wp:paragraph -->','',$post_title);
					$post_title = str_replace('<!-- /wp:paragraph -->','',$post_title);
					$post_content = str_replace('<!-- wp:paragraph -->','',$post_content);
					$post_content = str_replace('<!-- /wp:paragraph -->','',$post_content); */
					$target = $target_languages[$i];
					$is_title_exist = wpm_ml_check_language_string($post_title,$target);
					if($is_title_exist===false){
						$is_src_title_exist = wpm_ml_check_language_string($post_title,$source);
						if($is_src_title_exist===false){
							$post_title = '[:'.$source.']'.$post_title.'[:]';
						}
						
						$source_title = wpm_ml_get_language_string($post_title,$source);
						$new_title = wpm_ml_auto_translate_content($source_title,$source,$target);
						
						$new_title = '[:'.$target.']'.$new_title.'[:]';
						$new_title_string = str_replace('[:]',$new_title,$post_title);
						$value->post_title = $new_title_string;
						$should_update = true;
					}
					$is_content_exist = wpm_ml_check_language_string($post_content,$target);
					if($is_content_exist===false){
						$is_src_content_exist = wpm_ml_check_language_string($post_content,$source);
						if($is_src_content_exist===false){
							$post_content = '[:'.$source.']'.$post_content.'[:]';
						}
						
						$source_content = wpm_ml_get_language_string($post_content,$source);
						$new_content = wpm_ml_auto_translate_content($source_content,$source,$target);
						
						$new_content = '[:'.$target.']'.$new_content.'[:]';
						$new_content_string = str_replace('[:]',$new_content,$post_content);
						$value->post_content = $new_content_string;
						$should_update = true;
					}
				}
				$post_arr = array('ID'=>$value->ID,'post_title'=>$value->post_title,'post_content'=>$value->post_content);
			}
			if($should_update == true){
				wp_update_post( $post_arr );
			}
		}else if($post_type=='page' && $total_pages>0 && in_array('page',$what_arr)){
			$posts = get_pages( array('post_type' => 'page','post_status' => 'publish','posts_per_page'  => 1,'offset' => $offset,'orderby' => 'ID','order' => 'ASC'));
			$post_arr = array();
			$should_update = false;
			foreach ($posts as $key => $value) {
				for ($i=0; $i < count($target_languages); $i++) { 
					$post_title = $value->post_title;
					$post_content = $value->post_content;
					/* $post_title = str_replace('<!-- wp:paragraph -->','',$post_title);
					$post_title = str_replace('<!-- /wp:paragraph -->','',$post_title);
					$post_content = str_replace('<!-- wp:paragraph -->','',$post_content);
					$post_content = str_replace('<!-- /wp:paragraph -->','',$post_content); */
					$target = $target_languages[$i];
					$is_title_exist = wpm_ml_check_language_string($post_title,$target);
					if($is_title_exist===false){
						$is_src_title_exist = wpm_ml_check_language_string($post_title,$source);
						if($is_src_title_exist===false){
							$post_title = '[:'.$source.']'.$post_title.'[:]';
						}
						$source_title = wpm_ml_get_language_string($post_title,$source);
						$new_title = wpm_ml_auto_translate_content($source_title,$source,$target);
						$new_title = '[:'.$target.']'.$new_title.'[:]';
						$new_title_string = str_replace('[:]',$new_title,$post_title);
						$value->post_title = $new_title_string;
						$should_update = true;
					}

					$is_content_exist = wpm_ml_check_language_string($post_content,$target);
					if($is_content_exist===false){
						$is_src_content_exist = wpm_ml_check_language_string($post_content,$source);
						if($is_src_content_exist===false){
							$post_content = '[:'.$source.']'.$post_content.'[:]';
						}
						
						$source_content = wpm_ml_get_language_string($post_content,$source);
						$new_content = wpm_ml_auto_translate_content($source_content,$source,$target);
						
						$new_content = '[:'.$target.']'.$new_content.'[:]';
						$new_content_string = str_replace('[:]',$new_content,$post_content);
						$value->post_content = $new_content_string;
						$should_update = true;
					}
				}
				$post_arr = array('ID'=>$value->ID,'post_title'=>$value->post_title,'post_content'=>$value->post_content);
			}
			if($should_update == true){
				$is_update = wp_update_post( $post_arr );
			}
		}else if($post_type=='product' && $total_product>0 && in_array('product',$what_arr)){
			$posts = get_posts( array('post_type'=>'product','post_status'=>'publish','posts_per_page'  => 1,'offset' => $offset,'orderby' => 'ID','order' => 'ASC'));
			$post_arr = array();
			$should_update = false;
			foreach ($posts as $key => $value) {
				$post_title = $value->post_title;
				$post_content = $value->post_content;
				$post_title = str_replace('<!-- wp:paragraph -->','',$post_title);
				$post_title = str_replace('<!-- /wp:paragraph -->','',$post_title);
				$post_content = str_replace('<!-- wp:paragraph -->','',$post_content);
				$post_content = str_replace('<!-- /wp:paragraph -->','',$post_content);
				for ($i=0; $i < count($target_languages); $i++) { 
					$target = $target_languages[$i];
					$is_title_exist = wpm_ml_check_language_string($post_title,$target);
					if($is_title_exist===false){
						$is_src_title_exist = wpm_ml_check_language_string($post_title,$source);
						if($is_src_title_exist===false){
							$post_title = '[:'.$source.']'.$post_title.'[:]';
						}
						$source_title = wpm_ml_get_language_string($post_title,$source);
						$new_title = wpm_ml_auto_translate_content($source_title,$source,$target);
						$new_title = '[:'.$target.']'.$new_title.'[:]';
						$new_title_string = str_replace('[:]',$new_title,$post_title);
						$value->post_title = $new_title_string;
						$should_update = true;
					}
					$is_content_exist = wpm_ml_check_language_string($post_content,$target);
					if($is_content_exist===false){
						$is_src_content_exist = wpm_ml_check_language_string($post_content,$source);
						if($is_src_content_exist===false){
							$post_content = '[:'.$source.']'.$post_content.'[:]';
						}
						
						$source_content = wpm_ml_get_language_string($post_content,$source);
						$new_content = wpm_ml_auto_translate_content($source_content,$source,$target);
						
						$new_content = '[:'.$target.']'.$new_content.'[:]';
						$new_content_string = str_replace('[:]',$new_content,$post_content);
						$value->post_content = $new_content_string;
						$should_update = true;
					}
				}
				$post_arr = array('ID'=>$value->ID,'post_title'=>$value->post_title,'post_content'=>$value->post_content);
			}
			if($should_update == true){
				wp_update_post( $post_arr );
			}
		}
		$success=1;
		$new_offset = $offset+1;
		if($total_loop==$total_record){
			$success=2;
		}
		echo $success;
		die;
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

		             echo wp_json_encode(array('status'=>'t'));  

		        }else{

		            echo wp_json_encode(array('status'=>'f'));            

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

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Security measurament is done below in this function with nonce key wpm_feedback_nonce.
		if( isset( $_POST['data'] ) ) {
	        // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
                        
    	$name    = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $email   = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $website = isset( $_POST['website'] ) ? sanitize_text_field( $_POST['website'] ) : '';
        
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
