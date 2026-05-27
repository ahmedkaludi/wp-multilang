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
use WPM\Includes\Admin\Settings\WPM_Settings_AI_Integration;

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

		$ai_settings 		=	WPM_Settings_AI_Integration::get_openai_settings();
		$main_params['ai_settings'] 	=	$ai_settings;


		$main_params['is_pro_active'] = wpm_is_pro_active();

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

								// Filter out untranslatable strings before batching
								$filterable   = array();
								$filter_keys  = array();
								foreach ( $textToTranslate as $key => $text ) {
									if ( ! wpm_ml_is_untranslatable( $text ) ) {
										$filterable[]  = $text;
										$filter_keys[] = $key;
									}
								}

								$debug_entry .= "Translatable fields after filtering: " . count($filterable) . "\n";

								// Batch translate all Elementor text fields at once
								$translated_batch = wpm_ml_batch_translate( $filterable, $source, $target );

								// Map translations back and apply str_replace on the source JSON
								foreach ( $filter_keys as $i => $orig_key ) {
									$text  = $textToTranslate[ $orig_key ];
									$trabs = isset( $translated_batch[ $i ] ) ? $translated_batch[ $i ] : $text;

									$debug_entry .= "Text: '{$text}' -> Translation: '{$trabs}'\n";

									if ( $trabs != 'false' && $trabs !== $text ) {
										if ( preg_match( '/<[^<]+>/', $translated_source ) === 1 ) {
											$original_unescaped    = stripslashes( $text );
											$translated_unescaped  = stripslashes( $trabs );

											$original_json    = json_encode( $text, JSON_UNESCAPED_SLASHES );
											$translated_json  = json_encode( $trabs, JSON_UNESCAPED_SLASHES );

											$patterns = [
												$text,
												$original_unescaped,
												$original_json,
												json_encode( $text )
											];
											$replacements = [
												$trabs,
												$translated_unescaped,
												$translated_json,
												json_encode( $trabs )
											];
											$translated_source = str_replace( $patterns, $replacements, $translated_source );
										} else {
											$translated_source = str_replace( '"' . $text . '"', '"' . $trabs . '"', $translated_source );
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

			/*
			Bricks auto translation
			*/
			if ( class_exists( '\Bricks\Frontend' ) ) {
			    
			    $debug_file = WP_CONTENT_DIR . '/wpm_debug_translation.log';
			    $debug_entry = "=== BRICKS PROCESSING START ===\n";
			    $debug_entry .= "Post ID: {$post->ID} | Source: {$source} | Target: {$target} | Override: " . ($override ? 'Yes' : 'No') . "\n";
			    
			    // Bricks stores content in these meta keys
			    $bricks_meta_keys = [
			        '_bricks_page_content_2',
			    ];
			    
			    foreach ( $bricks_meta_keys as $meta_key ) {
			        $data = get_post_meta( $post->ID, $meta_key, true );

			        global $wpdb;
	                $raw_meta_value = $wpdb->get_var(
					    $wpdb->prepare(
					        "SELECT meta_value 
					         FROM $wpdb->postmeta 
					         WHERE meta_key = %s 
					         AND post_id = %d",
					        $meta_key,
					        $post->ID
					    )
					);
					if ( is_serialized( $raw_meta_value ) ) {
						$data 	=	maybe_unserialize( $raw_meta_value );
					}

			        $debug_entry .= "Processing meta key: {$meta_key}\n";
			        $debug_entry .= "Data exists: " . (!empty($data) ? 'Yes' : 'No') . " | Length: " . (empty($data) ? 0 : strlen(maybe_serialize($data))) . "\n";
			       
			        if ( empty($data) ) {
			            $debug_entry .= "Skipping {$meta_key} - no data\n";
			            continue;
			        }
			        
			        // Bricks data may be stored as serialized array or JSON string
			        if ( is_string($data) ) {
			            $data_array = json_decode( $data, true );
			            if ( json_last_error() !== JSON_ERROR_NONE ) {
			                $data_array = maybe_unserialize( $data );
			            }
			        } else {
			            $data_array = $data; // already unserialized by get_post_meta
			        }
			        
			        $debug_entry .= "Parsed to array: " . (is_array($data_array) ? 'Yes (' . count($data_array) . ' elements)' : 'No') . "\n";
			        
			        if ( ! is_array($data_array) ) {
			            $debug_entry .= "Skipping {$meta_key} - could not parse to array\n";
			            continue;
			        }
			        
			        // Re-serialize to string for the translate meta storage (consistent with Elementor approach)
			        $data_string = wp_json_encode( $data_array );
			        
			        $data_translate = get_post_meta( $post->ID, $meta_key . '_translate', true );
			        $is_data_exists = wpm_ml_check_language_string( $data_translate, $target );
			        
			        $debug_entry .= "Translate meta exists: " . (!empty($data_translate) ? 'Yes' : 'No') . "\n";
			        $debug_entry .= "Target data exists: " . ($is_data_exists ? 'Yes' : 'No') . "\n";
			        
			        if ( $is_data_exists === false || $override === true ) {
			            
			            $debug_entry .= "Will translate {$meta_key}\n";
			            
			            $is_src_data_exists = wpm_ml_check_language_string( $data_translate, $source );
			            $debug_entry .= "Source data exists: " . ($is_src_data_exists ? 'Yes' : 'No') . "\n";
			            
			            if ( $is_src_data_exists === true ) {
			                $source_data = wpm_ml_get_language_string( $data_translate, $source );

			                if ( base64_decode($source_data, true) !== false ) {
			                    $source_data = base64_decode( $source_data );
			                }
			                $source_data 	=	maybe_unserialize($source_data);
			                $source_data 	= 	wp_json_encode( $source_data );
			                
			                $debug_entry .= "Using existing source data from translate meta\n";
			            } else {
			                $source_data = $data_string;
			                // $source_data 	=	json_decode( $source_data, true );
			                // $source_data 	=	maybe_serialize( $source_data );
			                
			                $data_translate = '[:' . $source . ']' . base64_encode($raw_meta_value) . '[:]';
			                $debug_entry .= "Using original data and creating translate meta structure\n";
			            }
			            
			            // Extract text fields from Bricks flat array structure
			            $textToTranslate = [];
			            if ( is_string($source_data) ) {
				            $source_array = json_decode( $source_data, true );
				            if ( json_last_error() !== JSON_ERROR_NONE ) {
				                $source_array = maybe_unserialize( $source_data );
				            }
				        }
			            
			            if ( is_array($source_array) ) {
			                self::extractBricksTextFields( $source_array, $textToTranslate );
			                $debug_entry .= "Bricks text fields extracted: " . count($textToTranslate) . "\n";
			            }
			            
			            // DO NOT stripslashes on the raw JSON — it corrupts escape sequences inside string values.
						// Work directly on the decoded array, re-encode cleanly at the end.
						$source_array_to_translate = json_decode( $source_data, true );
						if ( json_last_error() !== JSON_ERROR_NONE || ! is_array($source_array_to_translate) ) {
						    $source_array_to_translate = maybe_unserialize( $source_data );
						}

						// Translate in-place on the decoded array, then re-encode once at the end.
						// This avoids all string-replacement fragility against JSON-encoded HTML.
						if ( is_array($source_array_to_translate) ) {
						    self::translateBricksTextFieldsInArray( $source_array_to_translate, $source, $target );
						    $translated_source = wp_json_encode( $source_array_to_translate, JSON_UNESCAPED_UNICODE );
						} else {
						    // Fallback: could not decode, skip translation
						    $translated_source = false;
						}
			
						// Batch-translate extracted Bricks text fields for JSON str_replace
					$bricks_plain   = array();
					$bricks_html    = array();
					$bricks_plain_k = array();
					$bricks_html_k  = array();

					foreach ( $textToTranslate as $key => $text ) {
						if ( empty( trim( $text ) ) || wpm_ml_is_untranslatable( $text ) ) {
							continue;
						}
						if ( preg_match( '/<[^>]+>/', $text ) ) {
							$bricks_html[]   = $text;
							$bricks_html_k[] = $key;
						} else {
							$bricks_plain[]   = $text;
							$bricks_plain_k[] = $key;
						}
					}

					// Batch plain-text fields
					$bricks_plain_translated = array();
					if ( ! empty( $bricks_plain ) ) {
						$bricks_plain_translated = wpm_ml_batch_translate( $bricks_plain, $source, $target );
					}

					// Build a full translation map: original_key => translated_text
					$bricks_tr_map = array();
					foreach ( $bricks_plain_k as $pi => $orig_key ) {
						$bricks_tr_map[ $orig_key ] = isset( $bricks_plain_translated[ $pi ] ) ? $bricks_plain_translated[ $pi ] : $textToTranslate[ $orig_key ];
					}
					// HTML fields still need tag-aware processing (done individually)
					foreach ( $bricks_html_k as $orig_key ) {
						$text  = $textToTranslate[ $orig_key ];
						$trabs = self::translateBricksTextField( $text, $source, $target );
						$bricks_tr_map[ $orig_key ] = $trabs;
					}

					// Apply translations via str_replace on the JSON string
					foreach ( $bricks_tr_map as $orig_key => $trabs ) {
						$text = $textToTranslate[ $orig_key ];
						if ( $trabs == 'false' || empty( $trabs ) || $trabs === $text ) {
							continue;
						}

						if ( preg_match( '/<[^>]+>/', $text ) ) {
							$original_json   = json_encode( $text,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
							$translated_json = json_encode( $trabs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
							$text_amp        = str_replace( '&', '&amp;', $text );
							$trabs_amp       = str_replace( '&', '&amp;', $trabs );
							$amp_json        = json_encode( $text_amp,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
							$amp_trabs       = json_encode( $trabs_amp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

							$translated_source = str_replace(
								[ $text, $original_json, $text_amp, $amp_json ],
								[ $trabs, $translated_json, $trabs_amp, $amp_trabs ],
								$translated_source
							);
						} else {
							$translated_source = str_replace(
								'"' . $text . '"',
								'"' . $trabs . '"',
								$translated_source
							);
						}
					}
			            
			            $debug_entry .= "Translation result: " . ($translated_source != false ? 'Success' : 'Failed') . "\n";
			            
			            if ( $translated_source != false ) {
			            	if ( ! is_serialized( $translated_source ) ) {
			       
			            		$translated_source 	=	json_decode( $translated_source, true );
			            		$translated_source 	=	maybe_serialize($translated_source);
			            	}
			            	
			                $translated_source_encoded = '[:' . $target . ']' . base64_encode($translated_source) . '[:]';
			                
			                if ( $override === true && $is_data_exists !== false ) {
			                    $pattern = '/\[:' . $target . '\][^\[]*(?:\[:\])?/';
			                    $data_translate = preg_replace($pattern, '', $data_translate);
			                    $debug_entry .= "Removed existing target language content\n";
			                }
			                
			                if ( strpos($data_translate, '[:]') === false ) {
			                    $data_translate = $data_translate . '[:]';
			                }
			                
			                $new_data_string = str_replace('[:]', $translated_source_encoded, $data_translate);
			                
			                $debug_entry .= "New data string length: " . strlen($new_data_string) . "\n";
			                
			                $update_result = update_post_meta(
			                    $post->ID,
			                    $meta_key . '_translate',
			                    $new_data_string
			                );
			                
			                $debug_entry .= "Meta update result: " . ($update_result ? 'Success' : 'Failed') . "\n";
			                
			                if ( ! $update_result ) {
			                    global $wpdb;
			                    $debug_entry .= "Last database error: " . $wpdb->last_error . "\n";
			                    
			                    $insert_result = add_post_meta($post->ID, $meta_key . '_translate', $new_data_string, true);
			                    $debug_entry .= "Insert attempt result: " . ($insert_result ? 'Success' : 'Failed') . "\n";
			                    
			                    if ( ! $insert_result ) {
			                        delete_post_meta($post->ID, $meta_key . '_translate');
			                        $insert_result = add_post_meta($post->ID, $meta_key . '_translate', $new_data_string, true);
			                        $debug_entry .= "Delete and re-insert result: " . ($insert_result ? 'Success' : 'Failed') . "\n";
			                    }
			                    
			                    $update_result = $insert_result;
			                }
			                
			                if ( $update_result ) {
			                    $should_update = true;
			                }
			            }
			            
			        } else {
			            $debug_entry .= "Skipping translation for {$meta_key} - target data exists and override is false\n";
			        }
			        
			        $debug_entry .= "---\n";
			    }
			    
			    $debug_entry .= "=== BRICKS PROCESSING END ===\n\n";
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
				if (strpos($meta_key, '_yoast') === 0 || strpos($meta_key, 'rank_math') === 0 || strpos($meta_key, '_seopress') === 0) {
					$seo_metas[$meta_key] = is_array($meta_value) ? $meta_value[0] : $meta_value;
				}
			}
			
			$debug_entry .= "Rank Math meta count: " . count($rank_math_metas) . "\n";
			$debug_entry .= "SEO meta count: " . count($seo_metas) . "\n";
			
			// Check if Rank Math is active
			$rank_math_active = defined('RANK_MATH_VERSION');
			$debug_entry .= "Rank Math active: " . ($rank_math_active ? 'Yes' : 'No') . "\n";
			
			// Process Rank Math meta fields for translation (batched)
			if ($rank_math_active && !empty($rank_math_metas)) {
				$debug_entry .= "Processing Rank Math metas for translation...\n";
				
				$rank_math_translatable_keys = array(
					'rank_math_title',
					'rank_math_description'
				);
				
				// PASS 1: Collect source texts that need translation
				$rm_to_translate   = array(); // meta_key => source_data
				$rm_meta_values    = array(); // meta_key => current meta_value (with language structure)

				foreach ($rank_math_translatable_keys as $meta_key) {
					if ( ! isset($rank_math_metas[$meta_key]) ) {
						continue;
					}

					$meta_value    = $rank_math_metas[$meta_key];
					$target_exists = wpm_ml_check_language_string($meta_value, $target);
					$debug_entry  .= "Processing {$meta_key} | Target exists: " . ($target_exists ? 'Yes' : 'No') . "\n";

					if ($target_exists !== false && $override !== true) {
						$debug_entry .= "Skipping {$meta_key} - target exists and override is false\n---\n";
						continue;
					}

					$source_exists = wpm_ml_check_language_string($meta_value, $source);
					if ($source_exists === true) {
						$source_data = wpm_ml_get_language_string($meta_value, $source);
					} else {
						$source_data = $meta_value;
						$target_exists_in_original = wpm_ml_check_language_string($meta_value, $target);
						if ($target_exists_in_original) {
							$source_data = wpm_ml_get_language_string($meta_value, $target);
							$meta_value  = '[:' . $source . ']' . $source_data . '[:]';
						} else {
							if ($source === 'en' && $target === 'zh' && preg_match('/[\x{4e00}-\x{9fff}]/u', $source_data)) {
								$debug_entry .= "Content appears to be Chinese already - skipping\n---\n";
								continue;
							} elseif ($source === 'zh' && $target === 'en' && !preg_match('/[\x{4e00}-\x{9fff}]/u', $source_data) && preg_match('/[a-zA-Z]/', $source_data)) {
								$debug_entry .= "Content appears to be English already - skipping\n---\n";
								continue;
							}
							if ( ! empty(trim($source_data)) ) {
								$meta_value = '[:' . $source . ']' . $source_data . '[:]';
							} else {
								$debug_entry .= "Skipping - source data is empty for {$meta_key}\n---\n";
								continue;
							}
						}
					}

					if (empty(trim($source_data)) || preg_match('/^_+$/', trim($source_data))) {
						$debug_entry .= "Skipping - source data is empty or underscores for {$meta_key}\n---\n";
						continue;
					}

					$rm_to_translate[$meta_key] = $source_data;
					$rm_meta_values[$meta_key]  = $meta_value;
				}

				// PASS 2: Batch-translate all collected Rank Math source texts
				if ( ! empty($rm_to_translate) ) {
					$rm_keys   = array_keys($rm_to_translate);
					$rm_texts  = array_values($rm_to_translate);

					$debug_entry .= "Batch-translating " . count($rm_texts) . " Rank Math fields\n";
					$rm_translated = wpm_ml_batch_translate( $rm_texts, $source, $target );

					// PASS 3: Apply translations and update meta
					foreach ( $rm_keys as $i => $meta_key ) {
						$translated_data = isset($rm_translated[$i]) ? $rm_translated[$i] : '';
						$meta_value      = $rm_meta_values[$meta_key];
						$target_exists   = wpm_ml_check_language_string($rank_math_metas[$meta_key], $target);

						$debug_entry .= "{$meta_key}: Translation " . ($translated_data ? 'Success' : 'Failed') . "\n";

						if ( $translated_data && $translated_data !== false && trim($translated_data) !== '' ) {
							$translated_data = '[:' . $target . ']' . $translated_data . '[:]';

							if ( $override === true && $target_exists === true ) {
								$pattern    = '/\[:' . $target . '\][^\[]*(?:\[:\])?/';
								$meta_value = preg_replace($pattern, '', $meta_value);
							}

							if (strpos($meta_value, '[:]') === false) {
								$meta_value = $meta_value . '[:]';
							}

							$new_meta_value = str_replace('[:]', $translated_data, $meta_value);
							$update_result  = update_post_meta($post->ID, $meta_key, $new_meta_value);

							if ( ! $update_result ) {
								delete_post_meta($post->ID, $meta_key);
								$update_result = add_post_meta($post->ID, $meta_key, $new_meta_value, true);
							}

							if ($update_result) {
								$should_update = true;
								$debug_entry  .= "{$meta_key} translation saved successfully\n";
							}
						}
						$debug_entry .= "---\n";
					}
				}

				if (isset($rank_math_metas['rank_math_schema_Service'])) {
					$debug_entry .= "Skipping rank_math_schema_Service - excluded to prevent serialization errors\n---\n";
				}
			}
			
			// Check for other SEO plugins
			$yoast_active = defined('WPSEO_VERSION');
			$seopress_active = defined('SEOPRESS_VERSION');
			$debug_entry .= "Yoast SEO active: " . ($yoast_active ? 'Yes' : 'No') . "\n";
			$debug_entry .= "SEOPress active: " . ($seopress_active ? 'Yes' : 'No') . "\n";

			if ( $seopress_active ) {

				global $wpdb;

				$all_metas = $wpdb->get_results(
				    $wpdb->prepare(
				        "SELECT meta_key, meta_value 
				         FROM $wpdb->postmeta 
				         WHERE post_id = %d",
				        $post->ID
				    ),
				    ARRAY_A
				);

				if ( ! empty( $all_metas ) && is_array( $all_metas ) ){ 

					$seopress_translatable_keys = [
						'_seopress_titles_title',
						'_seopress_titles_desc',
						'_seopress_social_fb_title',
						'_seopress_social_fb_desc',
						'_seopress_social_twitter_title',
						'_seopress_social_twitter_desc',
					];

					// PASS 1: Collect all translatable text parts across all SEOPress fields
					$sp_collect = array(); // [ [ 'meta_key' => key, 'parts' => [...], 'translatable_indices' => [...], 'source_texts' => [...] ], ... ]

					foreach ( $all_metas as $seop_key => $seop_meta_value ) {
						if ( strpos( $seop_meta_value['meta_key'], '_seopress' ) !== 0 || empty( $seop_meta_value['meta_value'] ) ) {
							continue;
						}
						if ( ! in_array( $seop_meta_value['meta_key'], $seopress_translatable_keys ) ) {
							continue;
						}

						$meta_value = $seop_meta_value['meta_value'];
						$meta_key   = $seop_meta_value['meta_key'];

						if ( ! is_string( $meta_value ) || is_serialized( $meta_value ) || self::isJson( $meta_value ) ) {
							continue;
						}

						$target_exists = wpm_ml_check_language_string( $meta_value, $target );
						$source_data   = '';
						$should_process = false;

						if ( $target_exists === false || $override === true ) {
							$source_exists = wpm_ml_check_language_string( $meta_value, $source );
							if ( $source_exists === true ) {
								$source_data = wpm_ml_get_language_string( $meta_value, $source );
							} else {
								$source_data = $meta_value;
								$target_exists_in_original = wpm_ml_check_language_string( $meta_value, $target );
								if ( $target_exists_in_original ) {
									$source_data = wpm_ml_get_language_string( $meta_value, $target );
								}
							}
							$should_process = true;
						}
						if ( $target_exists === true && $override === true ) {
							$source_data    = wpm_ml_get_language_string( $meta_value, $source );
							$should_process = true;
						}

						if ( $should_process && ! empty( $source_data ) ) {
							$parts = preg_split(
								'/(%%[^%]+%%)/',
								$source_data,
								-1,
								PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
							);

							$translatable_indices = array();
							$source_texts         = array();

							foreach ( $parts as $pi => $part ) {
								$part = trim( $part );
								if ( ! empty( $part ) && ! preg_match( '/%%[^%]+%%/', $part ) && ! wpm_ml_is_untranslatable( $part ) ) {
									$translatable_indices[] = $pi;
									$source_texts[]         = $part;
								}
							}

							if ( ! empty( $source_texts ) ) {
								$sp_collect[] = array(
									'meta_key'             => $meta_key,
									'parts'                => $parts,
									'translatable_indices' => $translatable_indices,
									'source_texts'         => $source_texts,
								);
							}
						}
					}

					// PASS 2: Flatten all source texts and batch-translate
					if ( ! empty( $sp_collect ) ) {
						$sp_all_texts = array();
						$sp_offsets   = array(); // track where each field's texts start in the flat array

						foreach ( $sp_collect as $entry ) {
							$sp_offsets[] = count( $sp_all_texts );
							$sp_all_texts = array_merge( $sp_all_texts, $entry['source_texts'] );
						}

						$sp_translated = wpm_ml_batch_translate( $sp_all_texts, $source, $target );

						// PASS 3: Reassemble and save
						foreach ( $sp_collect as $ci => $entry ) {
							$offset = $sp_offsets[ $ci ];
							$parts  = $entry['parts'];

							foreach ( $entry['translatable_indices'] as $ti => $part_idx ) {
								$translated = isset( $sp_translated[ $offset + $ti ] ) ? $sp_translated[ $offset + $ti ] : trim( $parts[ $part_idx ] );
								$parts[ $part_idx ] = $translated;
							}

							$result = '';
							foreach ( $parts as $part ) {
								$part = trim( $part );
								if ( empty( $part ) ) {
									$result .= ' ';
								} else {
									$result .= $part . ' ';
								}
							}

							if ( ! empty( $result ) ) {
								update_post_meta( $post->ID, $entry['meta_key'], trim( $result ) );
							}
						}
					}

				}

			}

			
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
		
		if ( wpm_is_pro_active() && $params['license_status'] !== 'active' ) {

		?>
			<p class="wpm-license-error-note" style="color: red; font-weight: 600; font-size: 14px;"><?php echo esc_html__( 'Your license key is inactive or expired, please check', 'wp-multilang' ); ?><a href="<?php echo esc_attr( admin_url( 'admin.php?page=wpm-settings&tab=license' ) ) ?>"><?php echo esc_html__( ' here' ); ?></a></p>
		<?php	
		}else if ( ! wpm_is_pro_active() &&  ( empty( $params['ai_settings']['wpm_openai_integration'] ) || $params['ai_settings']['wpm_openai_integration'] == 0 ||  empty( $params['ai_settings']['model'] ) || empty( $params['ai_settings']['api_provider'] ) ) ) {
		?>
			<p class="wpm-license-error-note" style="color: red; font-weight: 600; font-size: 14px;"><?php echo esc_html__( 'Set up', 'wp-multilang' ); ?><a href="<?php echo esc_attr( admin_url( 'admin.php?page=wpm-settings&tab=ai_integration' ) ); ?>"><?php echo esc_html__( ' AI integration' ); ?></a><?php echo esc_html__( ' to use auto-translation in the free plan, or', 'wp-multilang' ); ?><a href="<?php echo esc_url( 'https://wp-multilang.com/pricing/' ); ?>" target="_blank"><?php echo esc_html__( ' upgrade to Pro' ); ?></a><?php echo esc_html__( ' for automatic translation.', 'wp-multilang' ) ?></p>
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

	/**
	 * Extract translatable text fields from Bricks builder flat element array.
	 * Bricks stores a flat array of elements, each with: id, name, parent, children, settings.
	 * Text lives inside $element['settings'] at various keys depending on widget type.
	 * @param 	$elements 			array
	 * @param 	$textToTranslate 	array - pass by reference
	 * @since 	2.4.28
	 */
	protected static function extractBricksTextFields( array $elements, array &$textToTranslate ): void {
	    
	    // These settings keys contain plain translatable text (no HTML)
	    $plain_text_keys = [
	        'text',       // heading, text-basic, button, text-link
	        'title',      // list items, accordion items
	        'subtitle',   // accordion items
	        'meta',       // list items
	        'prefix',     // animated-typing
	        'suffix',     // animated-typing
	        'label',      // form fields
	        'placeholder',// form fields
	        'successMessage',
	        'emailSubject',
	        'fromName',
	        'emailErrorMessage',
	        'mailchimpPendingMessage',
	        'mailchimpErrorMessage',
	        'sendgridErrorMessage',
	    ];
	    
	    // These settings keys contain HTML (rich text)
	    $html_text_keys = [
	        'content', // accordion content, tab content
	    ];
	    
	    foreach ( $elements as $element ) {
	        if ( empty($element['settings']) || ! is_array($element['settings']) ) {
	            continue;
	        }
	        
	        $settings = $element['settings'];
	        
	        // --- Direct plain text keys ---
	        foreach ( $plain_text_keys as $key ) {
	            if ( ! empty($settings[$key]) && is_string($settings[$key]) ) {
	                $textToTranslate[] = $settings[$key];
	            }
	        }
	        
	        // --- Direct HTML/rich text keys ---
	        foreach ( $html_text_keys as $key ) {
	            if ( ! empty($settings[$key]) && is_string($settings[$key]) ) {
	                $textToTranslate[] = $settings[$key];
	            }
	        }
	        
	        // --- Nested: accordion items [ {title, subtitle, content} ] ---
	        if ( ! empty($settings['accordions']) && is_array($settings['accordions']) ) {
	            foreach ( $settings['accordions'] as $accordion ) {
	                foreach ( ['title', 'subtitle', 'content'] as $k ) {
	                    if ( ! empty($accordion[$k]) && is_string($accordion[$k]) ) {
	                        $textToTranslate[] = $accordion[$k];
	                    }
	                }
	            }
	        }
	        
	        // --- Nested: list items [ {title, meta} ] ---
	        if ( ! empty($settings['items']) && is_array($settings['items']) ) {
	            foreach ( $settings['items'] as $item ) {
	                // testimonials: name, title, content
	                // list: title, meta
	                foreach ( ['title', 'meta', 'name', 'content'] as $k ) {
	                    if ( ! empty($item[$k]) && is_string($item[$k]) ) {
	                        $textToTranslate[] = $item[$k];
	                    }
	                }
	            }
	        }
	        
	        // --- Nested: tabs [ {title, content} ] ---
	        if ( ! empty($settings['tabs']) && is_array($settings['tabs']) ) {
	            foreach ( $settings['tabs'] as $tab ) {
	                foreach ( ['title', 'content'] as $k ) {
	                    if ( ! empty($tab[$k]) && is_string($tab[$k]) ) {
	                        $textToTranslate[] = $tab[$k];
	                    }
	                }
	            }
	        }
	        
	        // --- Nested: animated-typing strings [ {text} ] ---
	        if ( ! empty($settings['strings']) && is_array($settings['strings']) ) {
	            foreach ( $settings['strings'] as $string_item ) {
	                if ( ! empty($string_item['text']) && is_string($string_item['text']) ) {
	                    $textToTranslate[] = $string_item['text'];
	                }
	            }
	        }
	        
	        // --- Nested: form fields [ {label, placeholder} ] ---
	        if ( ! empty($settings['fields']) && is_array($settings['fields']) ) {
	            foreach ( $settings['fields'] as $field ) {
	                foreach ( ['label', 'placeholder'] as $k ) {
	                    if ( ! empty($field[$k]) && is_string($field[$k]) ) {
	                        $textToTranslate[] = $field[$k];
	                    }
	                }
	            }
	        }
	    }
	    
	    // Deduplicate to avoid translating the same string multiple times
	    $textToTranslate = array_values( array_unique($textToTranslate) );
	}

	/**
 * Recursively walk a decoded Bricks element array and translate text fields in-place.
 * Works on the PHP array directly — no JSON string replacement needed.
 *
 * @param array  $elements  Decoded Bricks elements array (modified in-place)
 * @param string $source    Source language code
 * @param string $target    Target language code
 * @since 2.4.29
 */
protected static function translateBricksTextFieldsInArray( array &$elements, string $source, string $target ): void {

    $plain_text_keys = [
        'text', 'title', 'subtitle', 'meta', 'prefix', 'suffix',
        'label', 'placeholder', 'successMessage', 'emailSubject',
        'fromName', 'emailErrorMessage', 'mailchimpPendingMessage',
        'mailchimpErrorMessage', 'sendgridErrorMessage',
    ];

    $html_text_keys = [ 'content' ];
    $all_text_keys  = array_merge( $plain_text_keys, $html_text_keys );

    // PASS 1: Collect all translatable text values and record their locations
    $collect = array(); // [ [ 'text' => string, 'path' => [...] ], ... ]

    foreach ( $elements as $ei => &$element ) {
        if ( empty($element['settings']) || ! is_array($element['settings']) ) {
            continue;
        }
        $settings = &$element['settings'];

        foreach ( $all_text_keys as $key ) {
            if ( isset($settings[$key]) && is_string($settings[$key]) && trim($settings[$key]) !== '' && ! wpm_ml_is_untranslatable($settings[$key]) ) {
                $collect[] = array( 'text' => $settings[$key], 'ref' => array( &$settings, $key ) );
            }
        }

        $nested_groups = array(
            'accordions' => array( 'title', 'subtitle', 'content' ),
            'items'      => array( 'title', 'meta', 'name', 'content' ),
            'tabs'       => array( 'title', 'content' ),
            'strings'    => array( 'text' ),
            'fields'     => array( 'label', 'placeholder' ),
        );

        foreach ( $nested_groups as $group_key => $sub_keys ) {
            if ( ! empty($settings[$group_key]) && is_array($settings[$group_key]) ) {
                foreach ( $settings[$group_key] as &$nested_item ) {
                    foreach ( $sub_keys as $k ) {
                        if ( ! empty($nested_item[$k]) && is_string($nested_item[$k]) && ! wpm_ml_is_untranslatable($nested_item[$k]) ) {
                            $collect[] = array( 'text' => $nested_item[$k], 'ref' => array( &$nested_item, $k ) );
                        }
                    }
                }
                unset($nested_item);
            }
        }
    }
    unset($element);

    if ( empty($collect) ) {
        return;
    }

    // PASS 2: Extract plain-text items for batching; HTML items need per-item handling
    $plain_indices = array();
    $plain_texts   = array();

    foreach ( $collect as $i => $entry ) {
        if ( ! preg_match( '/<[^>]+>/', $entry['text'] ) ) {
            $plain_indices[] = $i;
            $plain_texts[]   = $entry['text'];
        }
    }

    // Batch-translate all plain text in one shot
    $plain_translated = array();
    if ( ! empty($plain_texts) ) {
        $plain_translated = wpm_ml_batch_translate( $plain_texts, $source, $target );
    }

    // Write plain translations back
    foreach ( $plain_indices as $pi => $collect_idx ) {
        $translated = isset( $plain_translated[$pi] ) ? $plain_translated[$pi] : $collect[$collect_idx]['text'];
        $ref = &$collect[$collect_idx]['ref'];
        $ref[0][$ref[1]] = $translated;
    }

    // Translate HTML items individually (they need tag-aware processing)
    foreach ( $collect as $i => $entry ) {
        if ( in_array( $i, $plain_indices, true ) ) {
            continue;
        }
        $translated = self::translateBricksTextField( $entry['text'], $source, $target );
        $ref = &$collect[$i]['ref'];
        $ref[0][$ref[1]] = $translated;
    }
}

/**
 * Translate a single text value — handles both plain text and HTML strings.
 * For HTML: splits on tags, translates each text node, reassembles.
 *
 * @param  string $text    Original value (plain or HTML)
 * @param  string $source  Source language code
 * @param  string $target  Target language code
 * @return string          Translated value (same format as input)
 * @since  2.4.29	
 */
protected static function translateBricksTextField( string $text, string $source, string $target ): string {

    if ( trim($text) === '' || wpm_ml_is_untranslatable($text) ) {
        return $text;
    }

    // Plain text — no tags at all
    if ( ! preg_match('/<[^>]+>/', $text) ) {
        $translated = wpm_ml_auto_fetch_translation( $text, $source, $target );
        return ( $translated && $translated !== 'false' ) ? $translated : $text;
    }

    // HTML text — decode entities, split on tags, batch-translate all text nodes at once
    $decoded = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $parts   = preg_split( '/(<[^>]*>)/s', $decoded, -1, PREG_SPLIT_DELIM_CAPTURE );

    // Collect translatable text-node indices
    $text_indices = array();
    $text_values  = array();
    foreach ( $parts as $i => $part ) {
        if ( ! preg_match('/^<[^>]*>$/', $part) && trim($part) !== '' && ! wpm_ml_is_untranslatable($part) ) {
            $text_indices[] = $i;
            $text_values[]  = trim($part);
        }
    }

    if ( ! empty($text_values) ) {
        $translated_values = wpm_ml_batch_translate( $text_values, $source, $target );
        foreach ( $text_indices as $ti => $part_idx ) {
            $parts[$part_idx] = isset($translated_values[$ti]) ? $translated_values[$ti] : $parts[$part_idx];
        }
    }

    $result = implode( '', $parts );

    if ( strpos($text, '&amp;') !== false ) {
        $result = str_replace( '&', '&amp;', $result );
    }

    return $result;
}


/**
 * Check is string is a json data
 * @param 	$string 	string
 * @return 	boolean
 * @since 	2.4.29
 * */
public static function isJson( $string ) {

	if ( ! is_string( $string ) ) {
		return false;
	}

	json_decode( $string );

	return json_last_error() === JSON_ERROR_NONE;
}

}