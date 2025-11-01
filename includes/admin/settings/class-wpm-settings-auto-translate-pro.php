<?php
/**
 * WP Multilang Auto Translate Settings
 *
 * @category    Admin
 * @package     WPM/Admin
 * @author   	Magazine3
 * @since 		1.4
 */

namespace WPM\Includes\Admin\Settings;
use WPM\Includes\Admin\WPM_OpenAI;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WPM_Settings_Auto_Translate_Pro.
 */
class WPM_Settings_Auto_Translate_Pro {
	
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'wpm_localize_autotranslate_params', array( $this, 'filter_js_params' ) );
		add_action( 'wpm_display_license_status_msg', array( $this, 'display_message' ) );
		add_action( 'admin_enqueue_scripts', array($this, 'wpm_enqueue_style' ) );
		add_action( 'wpmpro_autotranslate_enqueue_script', array( $this, 'get_localize_data' ) );
	}


	public function wpm_enqueue_style($hook) {

		if($hook === 'toplevel_page_wpm-settings'){

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$dir_path = plugin_dir_url(__DIR__);


			// Ensure Select2 is always available for the settings page
			wp_register_style( 'select2', wpm_asset_path( 'styles/admin/select2-4.0.5.min.css' ), array(), WPM_VERSION );
			wp_register_script('select2', wpm_asset_path( 'scripts/select2.4.0.5.min.js' ), array(), WPM_VERSION, true );
			
			// Ensure Select2 is loaded before our scripts
			wp_enqueue_script('select2');
			wp_enqueue_style('select2');

			wp_register_script('wpmpro-autotranslate', wpm_asset_path( 'scripts/wpmpro-autotranslate' . $suffix . '.js' ), array('jquery', 'select2'), WPM_VERSION, true);
		}

		if ( $hook == 'post.php' || $hook == 'term.php' ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$dir_path = plugin_dir_url( __DIR__ );

			$tag_id 	=	isset( $_GET['tag_ID'] ) ? intval( $_GET['tag_ID'] ) : 0;

			$main_params = array(
			    'ajax_url'                          		=>	admin_url( 'admin-ajax.php' ),
			    'ajaxurl'                          			=>	admin_url( 'admin-ajax.php' ), // Also provide ajaxurl for compatibility
			    'source_language'                   		=>	function_exists('wpm_get_user_language') ? wpm_get_user_language() : 'en',
			    'target_language'                   		=>	function_exists('wpm_get_language') ? wpm_get_language() : 'en',
			    'post_id'                   				=>	get_the_ID(),
			    'tag_id'                   					=>	$tag_id,
			    'wpmpro_autotranslate_singular_nonce'       =>	wp_create_nonce( 'wpmpro-autotranslate-singular-nonce' ),
			);
			
			$main_params 	= 	self::filter_js_params( $main_params );	

			wp_register_script('wpmpro-singular-autotranslate', wpm_asset_path( 'scripts/wpmpro-autotranslate-singular' . $suffix . '.js' ), array('jquery', 'select2'), WPM_VERSION);
			wp_localize_script( 'wpmpro-singular-autotranslate', 'wpmpro_ats_localize_data', $main_params );

			wp_enqueue_style('wpmpro-singular-autotranslate', wpm_asset_path( 'styles/admin/wpmpro-autotranslate-singular' . $suffix . '.css' ), array(), WPM_VERSION);

		}
	}

	/**
	 * Localize and enqueue script
	 * @param $main_params Array this action hook is called in core plugin
	 * @since 1.4
	 * */
	public function get_localize_data( $main_params ){
		$main_params 	= 	self::filter_js_params( $main_params );	
		wp_localize_script( 'wpmpro-autotranslate', 'wpmpro_autotranslate_localize_data', $main_params );
		wp_enqueue_script( 'wpmpro-autotranslate' );	
	}


	/**
	 * Add license status to js params
	 * @param 	$main_params 	array
	 * @return 	$main_params 	array
	 * @since 	1.10
	 * */
	public static function filter_js_params( $main_params ){
		
		$main_params['license_status'] 		=	'';
		$license = get_option( 'wpmpro_upgrade_license' );
		$main_params['confirmation_message']  =  esc_html__( 'Are you sure you want to auto translate this content? It will overwrite the existing content for the current language.', 'wp-multilang-pro' );
		$main_params['nonce'] = wp_create_nonce('wpmpro_search_items');
		if ( is_array( $license ) && ! empty( $license['pro'] ) && is_array( $license['pro'] ) && ! empty( $license['pro']['license_key_status'] ) ){
			$main_params['license_status'] 	= 	$license['pro']['license_key_status'];
		}

		$ai_settings 		=	WPM_OpenAI::get_settings();
		$main_params['ai_settings'] 	=	$ai_settings;

		$is_pro_active = wpm_is_pro_active();

		return $main_params;

	}

	/**
	 * Auto translate meta box callback function
	 * @param 	$post 	WP_post object
	 * @since 	1.10
	 * */
	public static function auto_translate_metabox_output( $post ) {
		
		wp_enqueue_script( 'wpmpro-singular-autotranslate' );


		?>
		<div id='wpm-auto-metabox-wrapper' style="text-align: center;">
			<p><?php echo esc_html__( 'Click on Auto translate button to translate the content', 'wp-multilang' ); ?></p>
			<button type="button" id="wpm-auto-translate-btn" class="button button-primary"><p><?php echo esc_html__( ' Auto translate', 'wp-multilang' ) ?></p></button>
			<input type="hidden" id="wpm-current-post-id"  value="<?php echo esc_attr( $post->ID ); ?>"/>
		</div>
		<?php do_action( 'wpm_display_license_status_msg' ); ?>
		<?php	

	}

	/**
	 * Auto translate post data in batches
	 * @param 	$post 		WP_Post
	 * @param 	$source 	string	
	 * @param 	$target 	string	
	 * @param 	$batch_start int
	 * @param 	$batch_size 	int
	 * @return 	$response	array
	 * @since 	1.10
	 * */
	public static function auto_translate_batch( $post, $source, $target, $batch_start = 0, $batch_size = 100 ){
		
		$response = array('status'=>true, 'message'=>esc_html__('Batch processed','wp-multilang'));

		if ( $post && isset($post->ID) ) {

			$post_arr = array();
			$should_update = false;

			$post_title = $post->post_title;
			$post_content = $post->post_content;
			$post_excerpt = $post->post_excerpt;
		
			$is_title_exist = wpm_ml_check_language_string( $post_title, $target );
			
			// Check if we should translate title (either doesn't exist OR override is true)
			if ( $is_title_exist === false ) {
				$is_src_title_exist = wpm_ml_check_language_string( $post_title, $source );

				if( $is_src_title_exist === false ) {
					$post_title = '[:'.$source.']'.$post_title.'[:]';
				}
				// Get the source title and translate it
				$source_title = wpm_ml_get_language_string( $post_title, $source );
				$new_title = wpm_ml_auto_translate_content( $source_title, $source, $target, $batch_start, $batch_size );
				$new_slug = $new_title;
				
				$new_title = '[:'.$target.']'.$new_title.'[:]';
				// Check if there's already a [:target] section to replace
				if (preg_match('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $post_title)) {
					$post->post_title = preg_replace('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $new_title, $post_title);
				} else {
					// No existing target language, append to the end
					$post->post_title = str_replace('[:]', $new_title, $post_title);
				} 

				$should_update = true;
				
				self::auto_translate_slug( $post->ID, $target, $post->post_type, $new_slug );
			}

			$is_content_exist = wpm_ml_check_language_string($post_content,$target);
			
			// Check if we should translate content (either doesn't exist OR override is true)
			if ( $is_content_exist === false ) {
				$is_src_content_exist = wpm_ml_check_language_string($post_content,$source);

				if ( $is_src_content_exist === false ) {
					$post_content = '[:'.$source.']'.$post_content.'[:]';
				}
				
				$source_content = wpm_ml_get_language_string($post_content,$source);
				$new_content = wpm_ml_auto_translate_content($source_content,$source,$target, $batch_start, $batch_size);
				
				$new_content = '[:'.$target.']'.$new_content.'[:]';
				// Check if there's already a [:target] section to replace
				if (preg_match('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $post_content)) {
					$post->post_content = preg_replace('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $new_content, $post_content);
				} else {
					// No existing target language, append to the end
					$post->post_content = str_replace('[:]', $new_content, $post_content);
				} 
				
				$should_update = true;
			}
			
			if($post_excerpt){
				$is_excerpt_exist = wpm_ml_check_language_string( $post_excerpt, $target );
				
				// Check if we should translate excerpt (either doesn't exist OR override is true)
				if ( $is_excerpt_exist === false ) {
					$is_src_excerpt_exist = wpm_ml_check_language_string($post_excerpt,$source);

					if ( $is_src_excerpt_exist === false ) {
						$post_excerpt = '[:'.$source.']'.$post_excerpt.'[:]';
					}
					
					$source_excerpt = wpm_ml_get_language_string($post_excerpt,$source);
					$new_excerpt = wpm_ml_auto_translate_content($source_excerpt,$source,$target, $batch_start, $batch_size);
					
					$new_excerpt = '[:'.$target.']'.$new_excerpt.'[:]';
					// Check if there's already a [:target] section to replace
					if (preg_match('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $post_excerpt)) {
						$post->post_excerpt = preg_replace('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $new_excerpt, $post_excerpt);
					} else {
						// No existing target language, append to the end
						$post->post_excerpt = str_replace('[:]', $new_excerpt, $post_excerpt);
					}
					
					$should_update = true;
				}
		}
			
			$post_arr = array( 
								'ID'			=>	$post->ID,
								'post_title'	=>	$post->post_title,
								'post_content'	=>	$post->post_content
							);
			if($post->post_excerpt){
				$post_arr['post_excerpt'] = $post->post_excerpt;
			}
			
			if ( $should_update == true ) {
				
				$result  = wp_update_post( $post_arr );
				
				if ( is_wp_error( $result ) ) {
					// Handle error.
					$error_message = $result->get_error_message();
					$response = array('status'=>false, 'message'=>$error_message);
				} elseif ( $result === 0 ) {
					$response = array('status'=>false, 'message'=>esc_html__('Translation can not be updated','wp-multilang'));
				} else {
					$message =  esc_html__('Batch translation updated successfully','wp-multilang');
					$response = array('status'=>true, 'message'=>$message);
				} 
			} else {
				$response = array('status'=>false, 'message'=>esc_html__('No updates needed','wp-multilang'));
			}
		}

		return $response; 
	}

	/**
	 * Auto translate post data
	 * @param 	$post 		WP_Post
	 * @param 	$source 	string	
	 * @param 	$target 	string	
	 * @return 	$response	array
	 * @since 	1.10
	 * */
	public static function is_slug_exists( $post_id, $current_lang ){
		global $wpdb;
		$table_name =	$wpdb->prefix.WPM_SLUG_TABLE;
		$count 		=	$wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM {$table_name} WHERE slug_id = %d AND language = %s", $post_id, $current_lang ) );
		return $count;
	}
	public static function auto_translate( $post, $source, $target, $override = false ){
		
		$response = array('status'=>true, 'message'=>esc_html__('Already Processed','wp-multilang'));

		// Check if the post is excluded
		if (isset($_POST['excluded_items']) && !empty($_POST['excluded_items'])) {
			$excluded_items = json_decode(stripslashes($_POST['excluded_items']), true);
			$post_type = get_post_type($post);
			
			if (isset($excluded_items[$post_type]) && in_array($post->ID, $excluded_items[$post_type])) {
				$response['status'] = false;
				$response['message'] = esc_html__('Item excluded from translation', 'wp-multilang');
				return $response;
			}
		}

		if ( $post && isset($post->ID) ) {

			$post_arr = array();
			$should_update = false;

			$post_title = $post->post_title;
			$post_content = $post->post_content;
			$post_excerpt = $post->post_excerpt;
		
			
			$is_title_exist = wpm_ml_check_language_string( $post_title, $target );
			
			
			// Check if we should translate title (either doesn't exist OR override is true)
			if ( $is_title_exist === false || $override === true ) {
				$is_src_title_exist = wpm_ml_check_language_string( $post_title, $source );

				if( $is_src_title_exist === false ) {
					$post_title = '[:'.$source.']'.$post_title.'[:]';
				}
				// Get the source title and translate it
				$source_title = wpm_ml_get_language_string( $post_title, $source );
				$new_title = wpm_ml_auto_translate_content( $source_title, $source, $target );
				$new_slug = $new_title;
				
				// If override is true and content exists, remove existing target language content first
				if ( $override === true && $is_title_exist === true ) {
					$pattern = '/\[:'.$target.'\][^\[]*(?:\[:\])?/';
					$post->post_title = preg_replace($pattern, '', $post_title);
					// Ensure we have proper structure before adding new translation
					if (strpos($post->post_title, '[:]') === false) {
						$post->post_title = $post->post_title . '[:]';
					}
					$post->post_title = str_replace('[:]', '[:'.$target.']'.$new_title.'[:]', $post->post_title);
				}else{
					$new_title = '[:'.$target.']'.$new_title.'[:]';
					// Check if there's already a [:target] section to replace
					if (preg_match('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $post_title)) {
						$post->post_title = preg_replace('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $new_title, $post_title);
					} else {
						// No existing target language, append to the end
						$post->post_title = str_replace('[:]', $new_title, $post_title);
					}
				} 

				
				
				$should_update = true;
				
				self::auto_translate_slug( $post->ID, $target, $post->post_type, $new_slug );

			}

			$is_content_exist = wpm_ml_check_language_string($post_content,$target);
			
			// Check if we should translate content (either doesn't exist OR override is true)
			if ( $is_content_exist === false || $override === true ) {
				$is_src_content_exist = wpm_ml_check_language_string($post_content,$source);

				if ( $is_src_content_exist === false ) {
					$post_content = '[:'.$source.']'.$post_content.'[:]';
				}
				
				$source_content = wpm_ml_get_language_string($post_content,$source);
				$new_content = wpm_ml_auto_translate_content($source_content,$source,$target);
				
				
				
				// If override is true and content exists, remove existing target language content first
				if ( $override === true && $is_content_exist === true ) {
					$pattern = '/\[:'.$target.'\][^\[]*(?:\[:\])?/';
					$post->post_content = preg_replace($pattern, '', $post_content);
					// Ensure we have proper structure before adding new translation
					if (strpos($post->post_content, '[:]') === false) {
						$post->post_content = $post->post_content . '[:]';
					}
					$post->post_content = str_replace('[:]', '[:'.$target.']'.$new_content.'[:]', $post->post_content);
				}else{
					$new_content = '[:'.$target.']'.$new_content.'[:]';
					// Check if there's already a [:target] section to replace
					if (preg_match('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $post_content)) {
						$post->post_content = preg_replace('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $new_content, $post_content);
					} else {
						// No existing target language, append to the end
						$post->post_content = str_replace('[:]', $new_content, $post_content);
					}
				} 
				
				$should_update = true;
			}
			
			if($post_excerpt){
				$is_excerpt_exist = wpm_ml_check_language_string( $post_excerpt, $target );
				
				// Check if we should translate excerpt (either doesn't exist OR override is true)
				if ( $is_excerpt_exist === false || $override === true ) {
					$is_src_excerpt_exist = wpm_ml_check_language_string($post_excerpt,$source);

					if ( $is_src_excerpt_exist === false ) {
						$post_excerpt = '[:'.$source.']'.$post_excerpt.'[:]';
					}
					
					$source_excerpt = wpm_ml_get_language_string($post_excerpt,$source);
					$new_excerpt = wpm_ml_auto_translate_content($source_excerpt,$source,$target);
					
					
					// If override is true and content exists, remove existing target language content first
					if ( $override === true && $is_excerpt_exist === true ) {
						$pattern = '/\[:'.$target.'\][^\[]*(?:\[:\])?/';
						$post->post_excerpt = preg_replace($pattern, '', $post_excerpt);
						// Ensure we have proper structure before adding new translation
						if (strpos($post->post_excerpt, '[:]') === false) {
							$post->post_excerpt = $post->post_excerpt . '[:]';
						}
						$post->post_excerpt = str_replace('[:]', '[:'.$target.']'.$new_excerpt.'[:]', $post->post_excerpt);
					}else{
						$new_excerpt = '[:'.$target.']'.$new_excerpt.'[:]';
						// Check if there's already a [:target] section to replace
						if (preg_match('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $post_excerpt)) {
							$post->post_excerpt = preg_replace('/\[:'.$target.'\][^\[]*(?:\[:\])?/', $new_excerpt, $post_excerpt);
						} else {
							// No existing target language, append to the end
							$post->post_excerpt = str_replace('[:]', $new_excerpt, $post_excerpt);
						}
					}
					
					$should_update = true;
				}
		}
			
			if ( class_exists( '\Elementor\Plugin' ) ) {
				
				// DEBUG: Log Elementor processing start
				$debug_file = WP_CONTENT_DIR . '/wpm_debug_translation.log';
				$debug_entry = "=== ELEMENTOR PROCESSING START ===\n";
				$debug_entry .= "Post ID: {$post->ID} | Source: {$source} | Target: {$target} | Override: " . ($override ? 'Yes' : 'No') . "\n";
				
				$meta_keys = [
					'_elementor_data', 
					'_elementor_template',
					'_elementor_element_cache',
				];
				
				foreach ($meta_keys as $meta_key) {
					$data = get_post_meta($post->ID, $meta_key, true);
					$debug_entry .= "Processing meta key: {$meta_key}\n";
					$debug_entry .= "Data exists: " . (!empty($data) ? 'Yes' : 'No') . " | Length: " . (empty($data) ? 0 : strlen($data)) . "\n";
					
					if( !$data ) {
						$debug_entry .= "Skipping {$meta_key} - no data\n";
						continue;
					}
					
					$data_translate = get_post_meta($post->ID, $meta_key . '_translate', true);
					$is_data_exists = wpm_ml_check_language_string($data_translate, $target);
					
					$debug_entry .= "Translate meta exists: " . (!empty($data_translate) ? 'Yes' : 'No') . " | Length: " . (empty($data_translate) ? 0 : strlen($data_translate)) . "\n";
					$debug_entry .= "Target data exists: " . ($is_data_exists ? 'Yes' : 'No') . "\n";
					
					// Check if we should translate meta data (either doesn't exist OR override is true)
					if ($is_data_exists === false || $override === true) {
						
						$debug_entry .= "Will translate {$meta_key}\n";
						
						$is_src_data_exists = wpm_ml_check_language_string($data_translate, $source);
						$debug_entry .= "Source data exists: " . ($is_src_data_exists ? 'Yes' : 'No') . "\n";
						
						// Get source data - if it exists in translated structure, use that, otherwise use original data
						if ($is_src_data_exists === true) {
							$source_data = wpm_ml_get_language_string($data_translate, $source);
							if (base64_decode($source_data, true) !== false) {
								$source_data = base64_decode($source_data);
							}
							$debug_entry .= "Using existing source data from translate meta\n";
						} else {
							$source_data = $data;
							// If source data doesn't exist in proper format, create it
							$data_translate = '[:' . $source . ']' . base64_encode($source_data) . '[:]';
							$debug_entry .= "Using original data and creating translate meta structure\n";
						}
						
						$textToTranslate = [];
						$translated_source = $source_data;
						if($meta_key == '_elementor_data' || $meta_key == '_elementor_template' || $meta_key == '_elementor_element_cache') {
							$data_array = json_decode($source_data, true);
							$debug_entry .= "JSON decode successful: " . ($data_array ? 'Yes' : 'No') . "\n";
							if ($data_array) {
								self::extractTextFields($data_array,$textToTranslate);
								$debug_entry .= "Text fields extracted: " . count($textToTranslate) . "\n";
								foreach ($textToTranslate as $key => $text) {
									$trabs = wpm_ml_auto_translate_content($text, $source, $target);
									$debug_entry .= "Text: '{$text}' -> Translation: '{$trabs}'\n";
									if ($trabs != 'false') {
											if(preg_match('/<[^<]+>/', $translated_source) === 1){
											$translated_source = str_replace(json_encode($text), json_encode($trabs), $translated_source);
											}else{
											$translated_source = str_replace('"'.$text.'"', '"'.$trabs.'"', $translated_source);
											}
									}
								}
								if( $meta_key == '_elementor_element_cache' ){
									delete_post_meta($post->ID, $meta_key);
									$debug_entry .= "Deleted element cache meta\n";
								}
								
							}
						}else {
							$translated_source = wpm_ml_auto_translate_content($source_data, $source, $target);
							$debug_entry .= "Direct translation applied\n";
						}
						
						$debug_entry .= "Translation result: " . ($translated_source != false ? 'Success' : 'Failed') . "\n";
						
						if ($translated_source != false) {
							$translated_source = '[:' . $target . ']' . base64_encode( $translated_source ) . '[:]';
						
							// If override is true and data exists, remove existing target language content first
							if ( $override === true && $is_data_exists !== false ) {
								$pattern = '/\[:'.$target.'\][^\[]*(?:\[:\])?/';
								$data_translate = preg_replace($pattern, '', $data_translate);
								$debug_entry .= "Removed existing target language content\n";
								$debug_entry .= "After removal: " . substr($data_translate, 0, 100) . "...\n";
							}
							
							// Ensure we have proper structure before adding new translation
							if (strpos($data_translate, '[:]') === false) {
								// If no [:], add it at the end
								$data_translate = $data_translate . '[:]';
							}
							
							$new_data_string = str_replace('[:]', $translated_source, $data_translate);
							$debug_entry .= "Final Elementor data string: " . substr($new_data_string, 0, 100) . "...\n";
							
							// DEBUG: Check if the new data string is valid
							$debug_entry .= "New data string length: " . strlen($new_data_string) . "\n";
							$debug_entry .= "New data string preview: " . substr($new_data_string, 0, 200) . "...\n";
							
							// DEBUG: Check if the meta key exists
							$existing_meta = get_post_meta($post->ID, $meta_key . '_translate', true);
							$debug_entry .= "Existing meta length: " . strlen($existing_meta) . "\n";
							
							// DEBUG: Try to update the meta
							$update_result = update_post_meta(
								$post->ID,
								$meta_key . '_translate',
								$new_data_string
							);
							
							$debug_entry .= "Meta update result: " . ($update_result ? 'Success' : 'Failed') . "\n";
							
							// DEBUG: If update failed, try to get the error
							if (!$update_result) {
								global $wpdb;
								$debug_entry .= "Last database error: " . $wpdb->last_error . "\n";
								
								// Try to insert instead of update
								$insert_result = add_post_meta($post->ID, $meta_key . '_translate', $new_data_string, true);
								$debug_entry .= "Insert attempt result: " . ($insert_result ? 'Success' : 'Failed') . "\n";
								
								if (!$insert_result) {
									// Try to delete and re-insert
									delete_post_meta($post->ID, $meta_key . '_translate');
									$insert_result = add_post_meta($post->ID, $meta_key . '_translate', $new_data_string, true);
									$debug_entry .= "Delete and re-insert result: " . ($insert_result ? 'Success' : 'Failed') . "\n";
								}
								
								// Update the result for the should_update flag
								$update_result = $insert_result;
							}
							
							// Set should_update flag to true since we updated meta data
							$should_update = true;
						}
						
					} else {
						$debug_entry .= "Skipping translation for {$meta_key} - target data exists and override is false\n";
					}
					
					$debug_entry .= "---\n";
				}
				
				$debug_entry .= "=== ELEMENTOR PROCESSING END ===\n\n";
				file_put_contents($debug_file, $debug_entry, FILE_APPEND | LOCK_EX);
			}

			// DEBUG: Check for Rank Math and other SEO meta fields
			$debug_file = WP_CONTENT_DIR . '/wpm_debug_translation.log';
			$debug_entry = "=== SEO META PROCESSING START ===\n";
			$debug_entry .= "Post ID: {$post->ID} | Source: {$source} | Target: {$target} | Override: " . ($override ? 'Yes' : 'No') . "\n";
			$debug_entry .= "=== LANGUAGE DETECTION DEBUG ===\n";
			
			// Get all post meta
			$all_metas = get_post_meta($post->ID);
			
			// Check for Rank Math meta fields
			$rank_math_metas = array();
			$seo_metas = array();
			
			foreach ($all_metas as $meta_key => $meta_value) {
				if (strpos($meta_key, 'rank_math') === 0) {
					$rank_math_metas[$meta_key] = is_array($meta_value) ? $meta_value[0] : $meta_value;
				}
				if (strpos($meta_key, '_yoast') === 0 || strpos($meta_key, 'rank_math') === 0 || strpos($meta_key, 'seopress') === 0) {
					$seo_metas[$meta_key] = is_array($meta_value) ? $meta_value[0] : $meta_value;
				}
			}
			
			$debug_entry .= "Rank Math meta count: " . count($rank_math_metas) . "\n";
			$debug_entry .= "SEO meta count: " . count($seo_metas) . "\n";
			
			// Check if Rank Math is active
			$rank_math_active = defined('RANK_MATH_VERSION');
			$debug_entry .= "Rank Math active: " . ($rank_math_active ? 'Yes' : 'No') . "\n";
			
			// Process Rank Math meta fields for translation
			if ($rank_math_active && !empty($rank_math_metas)) {
				$debug_entry .= "Processing Rank Math metas for translation...\n";
				
				// Define Rank Math meta keys that should be translated
				$rank_math_translatable_keys = array(
					'rank_math_title',
					'rank_math_description'
					// Note: rank_math_schema_Service contains serialized data and should not be translated directly
				);
				
				foreach ($rank_math_translatable_keys as $meta_key) {
					if (isset($rank_math_metas[$meta_key])) {
						$debug_entry .= "Processing {$meta_key}\n";
						
						$meta_value = $rank_math_metas[$meta_key];
						
						$debug_entry .= "Original value: " . substr($meta_value, 0, 100) . "...\n";
						
						// Check if target language already exists in the original meta
						$target_exists = wpm_ml_check_language_string($meta_value, $target);
						$debug_entry .= "Target exists: " . ($target_exists ? 'Yes' : 'No') . "\n";
						
						// Check if we should translate (either doesn't exist OR override is true)
						if ($target_exists === false || $override === true) {
							$debug_entry .= "Will translate {$meta_key}\n";
							
							// Check if source language exists in the original meta
							$source_exists = wpm_ml_check_language_string($meta_value, $source);
							$debug_entry .= "Source exists in original meta: " . ($source_exists ? 'Yes' : 'No') . "\n";
							
							// Get source data
							if ($source_exists === true) {
								$source_data = wpm_ml_get_language_string($meta_value, $source);
								$debug_entry .= "Using existing source data from original meta\n";
							} else {
								$source_data = $meta_value;
								$debug_entry .= "Original meta value: " . substr($source_data, 0, 100) . "...\n";
								
								// Check if the original content is actually in the target language, not source
								// This happens when the meta was previously translated but source/target got mixed up
								$target_exists_in_original = wpm_ml_check_language_string($meta_value, $target);
								if ($target_exists_in_original) {
									$debug_entry .= "Found target language in original meta - extracting target content\n";
									$source_data = wpm_ml_get_language_string($meta_value, $target);
									// Create proper structure with source language
									$meta_value = '[:' . $source . ']' . $source_data . '[:]';
									$debug_entry .= "Restructured with source language: " . substr($meta_value, 0, 100) . "...\n";
								} else {
									// Check if the content appears to be in the target language already
									// This is a heuristic check - if we're translating from EN to ZH but content is Chinese, skip
									$debug_entry .= "Checking if content is already in target language...\n";
									
									// Simple heuristic: if source is 'en' and target is 'zh', check if content contains Chinese characters
									if ($source === 'en' && $target === 'zh') {
										if (preg_match('/[\x{4e00}-\x{9fff}]/u', $source_data)) {
											$debug_entry .= "Content appears to be Chinese already - skipping translation\n";
											continue;
										}
									}
									// Reverse check: if source is 'zh' and target is 'en', check if content contains English characters
									elseif ($source === 'zh' && $target === 'en') {
										if (!preg_match('/[\x{4e00}-\x{9fff}]/u', $source_data) && preg_match('/[a-zA-Z]/', $source_data)) {
											$debug_entry .= "Content appears to be English already - skipping translation\n";
											continue;
										}
									}
									
									// Only create language string structure if source data is not empty
									if (!empty(trim($source_data))) {
										$meta_value = '[:' . $source . ']' . $source_data . '[:]';
										$debug_entry .= "Using original data and creating language string structure\n";
									} else {
										$debug_entry .= "Skipping - source data is empty for {$meta_key}\n";
										continue;
									}
								}
							}
							
							// Additional validation - skip if source data is empty or just underscores
							if (empty(trim($source_data)) || trim($source_data) === '' || preg_match('/^_+$/', trim($source_data))) {
								$debug_entry .= "Skipping - source data is empty or contains only underscores for {$meta_key}\n";
								continue;
							}
							
							// Translate the content
							$translated_data = wpm_ml_auto_translate_content($source_data, $source, $target);
							$debug_entry .= "Translation result: " . ($translated_data ? 'Success' : 'Failed') . "\n";
							$debug_entry .= "Translated data: " . ($translated_data ? substr($translated_data, 0, 100) . "..." : 'Empty/False') . "\n";
							
							// Only proceed if translation was successful and not empty/false
							if ($translated_data && $translated_data !== false && trim($translated_data) !== '') {
								$translated_data = '[:' . $target . ']' . $translated_data . '[:]';
								
								// If override is true and target exists, remove existing target content
								if ($override === true && $target_exists === true) {
									$pattern = '/\[:'.$target.'\][^\[]*(?:\[:\])?/';
									$meta_value = preg_replace($pattern, '', $meta_value);
									$debug_entry .= "Removed existing target content\n";
									$debug_entry .= "After removal: " . substr($meta_value, 0, 100) . "...\n";
								}
								
								// Ensure we have proper structure before adding new translation
								if (strpos($meta_value, '[:]') === false) {
									// If no [:], add it at the end
									$meta_value = $meta_value . '[:]';
								}
								
								$new_meta_value = str_replace('[:]', $translated_data, $meta_value);
								$debug_entry .= "Final meta value: " . substr($new_meta_value, 0, 100) . "...\n";
								
								// Update the original meta directly
								$update_result = update_post_meta($post->ID, $meta_key, $new_meta_value);
								$debug_entry .= "Update result: " . ($update_result ? 'Success' : 'Failed') . "\n";
								
								if (!$update_result) {
									// Try alternative update methods
									delete_post_meta($post->ID, $meta_key);
									$insert_result = add_post_meta($post->ID, $meta_key, $new_meta_value, true);
									$debug_entry .= "Insert result: " . ($insert_result ? 'Success' : 'Failed') . "\n";
									$update_result = $insert_result;
								}
								
								if ($update_result) {
									$should_update = true;
									$debug_entry .= "Rank Math meta translation successful\n";
								}
							} else {
								$debug_entry .= "Skipping translation - empty or failed result for {$meta_key}\n";
								$debug_entry .= "Source data was: " . substr($source_data, 0, 100) . "...\n";
							}
						} else {
							$debug_entry .= "Skipping {$meta_key} - target exists and override is false\n";
						}
						
						$debug_entry .= "---\n";
					}
				}
				
				// Skip Rank Math schema data translation to prevent errors
				if (isset($rank_math_metas['rank_math_schema_Service'])) {
					$debug_entry .= "Skipping rank_math_schema_Service - excluded from translation to prevent serialization errors\n";
					$debug_entry .= "Schema data length: " . strlen($rank_math_metas['rank_math_schema_Service']) . "\n";
					$debug_entry .= "---\n";
				}
			}
			
			// Check for other SEO plugins
			$yoast_active = defined('WPSEO_VERSION');
			$seopress_active = defined('SEOPRESS_VERSION');
			$debug_entry .= "Yoast SEO active: " . ($yoast_active ? 'Yes' : 'No') . "\n";
			$debug_entry .= "SEOPress active: " . ($seopress_active ? 'Yes' : 'No') . "\n";
			
			$debug_entry .= "=== SEO META PROCESSING END ===\n\n";
			file_put_contents($debug_file, $debug_entry, FILE_APPEND | LOCK_EX);

			$post_arr = array( 
										'ID'			=>	$post->ID,
										'post_title'	=>	$post->post_title,
										'post_content'	=>	$post->post_content
									);
			if($post->post_excerpt){
				$post_arr['post_excerpt'] = $post->post_excerpt;
			}
			
			if ( $should_update == true ) {
				
				// DEBUG: Log before update
				$debug_file = WP_CONTENT_DIR . '/wpm_debug_translation.log';
				$debug_entry = "=== POST UPDATE START ===\n";
				$debug_entry .= "Post ID: {$post->ID} | Should update: Yes\n";
				$debug_entry .= "Post title: " . $post->post_title . "\n";
				$debug_entry .= "Post content length: " . strlen($post->post_content) . "\n";
				$debug_entry .= "Post excerpt: " . ($post->post_excerpt ? $post->post_excerpt : 'None') . "\n";
				
				$result  = wp_update_post( $post_arr );
				
				$debug_entry .= "Update result: " . ($result ? 'Success' : 'Failed') . "\n";
				if (is_wp_error($result)) {
					$debug_entry .= "Error message: " . $result->get_error_message() . "\n";
				}

				if ( is_wp_error( $result ) ) {
					// Handle error.
					$error_message = $result->get_error_message();
					$response = array('status'=>false, 'message'=>$error_message);
					$debug_entry .= "Response: Error - {$error_message}\n";
				} elseif ( $result === 0 ) {
					$response = array('status'=>false, 'message'=>esc_html__('Translation can not be updated','wp-multilang'));
					$debug_entry .= "Response: Error - Translation can not be updated\n";
				} else {
					$message =  esc_html__('Translation updated successfully','wp-multilang');
					$response = array('status'=>true, 'message'=>$message);
					$debug_entry .= "Response: Success - {$message}\n";
				} 
				
				$debug_entry .= "=== POST UPDATE END ===\n\n";
				file_put_contents($debug_file, $debug_entry, FILE_APPEND | LOCK_EX);
			} else {
				// DEBUG: Log why no update
				$debug_file = WP_CONTENT_DIR . '/wpm_debug_translation.log';
				$debug_entry = "=== NO UPDATE REASON ===\n";
				$debug_entry .= "Post ID: {$post->ID} | Should update: No\n";
				$debug_entry .= "This means no content was translated or updated\n";
				$debug_entry .= "Check Elementor and SEO meta processing above for details\n";
				$debug_entry .= "=== END NO UPDATE REASON ===\n\n";
				file_put_contents($debug_file, $debug_entry, FILE_APPEND | LOCK_EX);
				
				$response = array('status'=>false, 'message'=>esc_html__('No updates needed','wp-multilang'));
			}
		}

		return $response; 
	}
	
	/**
	 * Auto translate term data
	 * @param 	$term 		WP_Term
	 * @param 	$source 	string	
	 * @param 	$target 	string	
	 * @return 	$response	array
	 * @since 	1.10
	 * */
	public static function auto_translate_term( $term, $source, $target, $override = false ) {
		$response = array('status'=>true, 'message'=>esc_html__('Already Processed','wp-multilang'));

		// Check if the term is excluded
		if (isset($_POST['excluded_items']) && !empty($_POST['excluded_items'])) {
			$excluded_items = json_decode(stripslashes($_POST['excluded_items']), true);
			
			if (isset($excluded_items[$term->taxonomy]) && in_array($term->term_id, $excluded_items[$term->taxonomy])) {
				$response['status'] = false;
				$response['message'] = esc_html__('Term excluded from translation', 'wp-multilang');
				return $response;
			}
		}
		
		if ( is_object( $term ) && isset( $term->name ) && isset( $term->description ) ) {

			$should_update 	= 	false;
			$term_arr 		=	array();
			$term_name 		=	$term->name;
			$term_desc 		=	$term->description;

			$is_title_exist = 	wpm_ml_check_language_string( $term_name, $target );

			// Check if we should translate title (either doesn't exist OR override is true)
			if ( $is_title_exist === false || $override === true ) {
				$is_src_title_exist = wpm_ml_check_language_string( $term_name, $source );

				if( $is_src_title_exist === false ) {
					$term_name 			= '[:'.$source.']'.$term_name.'[:]';
				}
				
				$source_title 			=	wpm_ml_get_language_string( $term_name, $source );
				$new_title 				=	wpm_ml_auto_translate_content( $source_title, $source, $target );
				$new_slug 				=	$new_title;
				
				// If override is true and content exists, remove existing target language content first
				if ( $override === true && $is_title_exist === true ) {
					$pattern 			= '/\[:'.$target.'\][^\[]*(?:\[:\])?/';
					$term->name 		= preg_replace( $pattern, '[:'.$target.']'.$new_title , $term_name );
				}else{
					$new_title 			= 	'[:'.$target.']'.$new_title.'[:]';
					$term->name 		=	str_replace( '[:]', $new_title, $term_name );
				}

				$should_update 			=	true;

				self::auto_translate_slug( $term->term_id, $target, $term->taxonomy, $new_slug );

			}

			$is_content_exist 			=	wpm_ml_check_language_string( $term_desc,$target );
			

			// Check if we should translate content (either doesn't exist OR override is true)
			if ( $is_content_exist === false || $override === true ) {
				$is_src_content_exist 	=	wpm_ml_check_language_string( $term_desc,$source );

				if ( $is_src_content_exist === false ) {
					$term_desc 			=	'[:'.$source.']'.$term_desc.'[:]';
				}
				
				$source_content 		=	wpm_ml_get_language_string( $term_desc, $source );
				$new_content 			=	wpm_ml_auto_translate_content( $source_content, $source, $target );
				
				// If override is true and content exists, remove existing target language content first
				if ( $override === true && $is_content_exist === true ) {
					$term->description = preg_replace('/\[:'.$target.'\][^\[]*(?:\[:\])?/', '[:'.$target.']'.$new_content, $term_desc);
				}else{
					$new_content 			=	'[:'.$target.']'.$new_content.'[:]';
					$term->description 		=	str_replace( '[:]',$new_content,$term_desc );	
				}
				
				$should_update 			=	true;
			}

			$term_arr 					=	array(
			 									'name'	=>	$term->name,
												'description'	=>	$term->description,
											);

			if ( $should_update == true ) {

				remove_filter( 'pre_term_description', 'wp_filter_kses' );
				remove_filter( 'term_description', 'wp_kses_data' );

				$result  = wp_update_term( $term->term_id, $term->taxonomy, $term_arr );

				add_filter( 'pre_term_description', 'wp_filter_kses' );
				add_filter( 'term_description', 'wp_kses_data' );

				if ( is_wp_error( $result ) ) {
					// Handle error.
					$error_message 	=	$result->get_error_message();
					$response 		=	array( 'status'=>false, 'message'=>$error_message );
				} elseif ( $result === 0 ) {
					$response 		=	array( 'status'=>false, 'message'=>esc_html__( 'Translation can not be updated','wp-multilang' ) );
				} else {
					$response 		=	array( 'status'=>true, 'message'=>esc_html__( 'Translation updated successfully','wp-multilang' ) );
				}

			}

		}

		return $response;
	}
	
	/**
	 * Translate extra text fields
	 * @param 	$data 	array
	 * @param 	$texts 	array
	 * @return 	$texts 	array
	 * @since 	1.10
	 * */
	public static function extractTextFields( &$data, &$texts = [] ) {
		static $keys_of_interest = null;
		if ( $keys_of_interest === null ) {
			$keys_of_interest = array_flip( [ 'title', 'title_text', 'editor', 'description_text', 'text', 'item_title', 'item_desc', 'item_text' ] );
		}
	
		if ( is_array( $data ) ) {
			foreach ( $data as $key => &$value ) {
				if ( isset( $keys_of_interest[ $key ] ) && is_string( $value ) && ! empty( $value ) ) {
					$texts[] = $value;
				}
				if ( is_array( $value ) || is_object( $value ) ) {
					self::extractTextFields( $value, $texts );
				}
			}
		} elseif ( is_object( $data ) ) {
			foreach ( $data as $key => &$value ) {
				if ( isset( $keys_of_interest[ $key ] ) && is_string( $value ) && ! empty( $value ) ) {
					$texts[] = $value;
				}
				if ( is_array( $value ) || is_object( $value ) ) {
					self::extractTextFields( $value, $texts );
				}
			}
		}
	
		return $texts;
	}
	
	/**
	 * Display license status message
	 * @since 	1.10
	 * */	
	public function display_message(){

		$params 	=	array();
		$params 	=	self::filter_js_params( $params );
		
		if ( wpm_is_pro_active() && $params['license_status'] !== 'active' && ( empty( $params['ai_settings']['api_provider'] ) || $params['ai_settings']['api_provider'] === 'multilang' ) ) {

		?>
			<p class="wpm-license-error-note" style="color: red; font-weight: 600; font-size: 14px;"><?php echo esc_html__( 'Your license key is inactive or expired, please check', 'wp-multilang' ); ?><a href="<?php echo esc_attr( admin_url( 'admin.php?page=wpm-settings&tab=license' ) ) ?>"><?php echo esc_html__( ' here' ); ?></a></p>
		<?php	
		}else if ( empty( $params['ai_settings']['model'] ) && ! empty( $params['ai_settings']['api_provider'] ) && $params['ai_settings']['api_provider'] !== 'multilang' ) {
		?>
			<p class="wpm-license-error-note" style="color: red; font-weight: 600; font-size: 14px;"><?php echo esc_html__( 'Please configure OpenAI settings', 'wp-multilang' ); ?><a href="<?php echo esc_attr( admin_url( 'admin.php?page=wpm-settings&tab=autotranslate' ) ) ?>" target="_blank"><?php echo esc_html__( ' here' ); ?></a></p>
		<?php	
		}else if ( ! wpm_is_pro_active() &&  ( $params['ai_settings']['api_provider'] === 'multilang' || empty( $params['ai_settings']['api_provider'] ) ) ) {
		?>
			<p class="wpm-license-error-note" style="color: red; font-weight: 600; font-size: 14px;"><?php echo esc_html__( 'This feature requires the ', 'wp-multilang' ); ?><a href="https://wp-multilang.com/pricing/#pricings"><?php echo esc_html__( ' Premium Version' ); ?></a></p>
		<?php
		}else{
		?>
			<p class="wpm-license-error-note wpm-hide" style="color: red; font-weight: 600; font-size: 14px;"><?php echo esc_html__( 'This feature requires the ', 'wp-multilang' ); ?><a href="https://wp-multilang.com/pricing/#pricings"><?php echo esc_html__( ' Premium Version' ); ?></a></p>
		<?php
		}

	}

	/**
	 * Auto translate slug if option is enabled
	 * @param $slug_id 		int
	 * @param $target_id 	string
	 * @param $type 		string
	 * @param $new_slug 	string
	 * @since 1.13
	 * */
	public static function auto_translate_slug( $slug_id, $target, $type, $new_slug ) {
		
		if( function_exists('wpm_is_auto_slug_translation_active') && wpm_is_auto_slug_translation_active() ) {
			global $wpdb;
			$slug_table_name 					=	$wpdb->prefix.WPM_SLUG_TABLE;
			$new_slug = str_replace(' ','-',$new_slug);
			// Save translated slug in postmeta for the target language
			$data_exists 					=	self::is_slug_exists( $slug_id, $target );
			if ( empty( $data_exists ) ) {
								
				$insert_data['slug_id']		=	$slug_id;
				$insert_data['slug']		=	$new_slug;
				$insert_data['language']	=	$target;
				$insert_data['type']		=	$type;
				$insert_data['created_at']	=	date( 'Y-m-d H:i:s' );
				$insert_data['updated_at']	=	date( 'Y-m-d H:i:s' );

				$wpdb->insert( $slug_table_name, $insert_data );	

			} else {
				$where['slug_id']			=	$slug_id;
				$where['language']			=	$target;
				$update_data['slug'] 		=	$new_slug;
				$update_data['updated_at'] 	=	date( 'Y-m-d H:i:s' );
				$wpdb->update( $slug_table_name, $update_data, $where );
			}
		}

	}

}