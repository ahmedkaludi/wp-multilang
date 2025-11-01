<?php
/**
 * WPM Translation functions
 *
 * Functions for translation, set translations to multidimensional arrays.
 *
 * @author   Valentyn Riaboshtan
 * @category      Core
 * @package       WPM/Functions
 * @version       2.0.0
 */
use WPM\Includes\WPM_Custom_Post_Types;
use WPM\Includes\Admin\WPM_OpenAI;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translate url
 *
 * @param string $url
 * @param string $language
 *
 * @return string
 */
function wpm_translate_url( $url, $language = '' ) {

	$host = wpm_get_orig_home_url();

	if ( strpos( $url, $host ) === false ) {
		return $url;
	}

	/**
	 * Check if post type support is enabled or not
	 * if it is not enabled then return the string as it is
	 * @since 2.4.18
	 * */
	global $post;
	if ( WPM_Custom_Post_Types::validate_post_type_support( $post ) ) {
		return $url;
	}

	$user_language = wpm_get_language();
	$options       = wpm_get_lang_option();

	if ( $language ) {
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ( ( $language === $user_language ) && ( ! is_admin() || is_front_ajax() ) && ! isset( $_GET['lang'] ) ) || ! isset( $options[ $language ] ) ) {
			return $url;
		}
	} else {
		$language = $user_language;
	}

	if ( ! empty( $url ) ) {
		$parse_url 	=	wp_parse_url( $url, PHP_URL_PATH );
		if ( ! empty( $parse_url ) ) {
			if ( is_admin_url( $url ) || preg_match( '/^.*\.php$/i', $parse_url ) ) {
				return add_query_arg( 'lang', $language, $url );
			}
		}
	}

	$url         = remove_query_arg( 'lang', $url );
	$default_uri = str_replace( $host, '', $url );
	$default_uri = $default_uri ? $default_uri : '/';
	$languages   = wpm_get_languages();
	$parts       = explode( '/', ltrim( trailingslashit( $default_uri ), '/' ) );
	$url_lang    = $parts[0];

	if ( isset( $languages[ $url_lang ] ) ) {
		$default_uri = preg_replace( '!^/' . $url_lang . '(/|$)!i', '/', $default_uri );
	}

	$default_language    = wpm_get_default_language();
	$default_lang_prefix = get_option( 'wpm_use_prefix', 'no' ) === 'yes' ? $default_language : '';

	if ( $language === $default_language ) {
		$new_uri = '/' . $default_lang_prefix . $default_uri;
	} else {
		$new_uri = '/' . $language . $default_uri;
	}

	$new_uri = preg_replace( '/(\/+)/', '/', $new_uri );

	if ( '/' !== $new_uri ) {
		$new_url = $host . $new_uri;
	} else {
		$new_url = $host;
	}

	return apply_filters( 'wpm_translate_url', $new_url, $language, $url );
}

/**
 * Translate multilingual string
 *
 * @param string $string
 * @param string $language
 *
 * @return array|mixed|string
 */
function wpm_translate_string( $string, $language = '' ) {

	if ( ! wpm_is_ml_string( $string ) ) {
		return $string;
	}

	/**
	 * Check if post type support is enabled or not
	 * if it is not enabled then return the string as it is
	 * @since 2.4.18
	 * */
	global $post;
	if ( WPM_Custom_Post_Types::validate_post_type_support( $post ) ) {
		return $string;
	}

	$strings = wpm_string_to_ml_array( $string );

	if ( ! is_array( $strings ) || empty( $strings ) ) {
		return $string;
	}

	if ( ! wpm_is_ml_array( $strings ) ) {
		return $strings;
	}

	$languages = wpm_get_languages();

	if ( $language ) {
		if ( isset( $languages[ $language ] ) ) {
			return $strings[ $language ];
		}

		return '';
	}

	$language = wpm_get_language();

	if ( isset( $strings[ $language ] ) && ( '' === $strings[ $language ] ) && get_option( 'wpm_show_untranslated_strings', 'yes' ) === 'yes' ) {
		$default_language = wpm_get_default_language();
		$default_text = apply_filters( 'wpm_untranslated_text', $strings[ $default_language ], $strings, $language );

		return $default_text;
	}

	if ( isset( $strings[ $language ] ) ) {
		return $strings[ $language ];
	}

	return '';
}

/**
 * Translate multidimensional array with multilingual strings
 *
 * @param        $value
 * @param string $language
 *
 * @return array|mixed|string
 */
function wpm_translate_value( $value, $language = '' ) {
	if ( is_array( $value ) ) {
		$result = array();
		foreach ( $value as $k => $item ) {
			$result[ $k ] = wpm_translate_value( $item, $language );
		}

		return $result;
	}

	return wpm_translate_string( $value, $language );
}

/**
 * Transform multilingual string to multilingual array
 *
 * @param $string
 *
 * @return array|mixed|string
 */
function wpm_string_to_ml_array( $string ) {
	if ( ! is_string( $string ) || $string === '' || is_serialized_string( $string ) || isJSON( $string ) ) {
		return $string;
	}

	$string = apply_filters( 'wpm_filter_string_to_ml_array', $string );
	$blocks = preg_split( '#\[:([a-z-]*)\]#im', $string, - 1, PREG_SPLIT_DELIM_CAPTURE );

	if ( empty( $blocks ) ) {
		return $string;
	}

	$languages = wpm_get_lang_option();
	$result    = array_fill_keys( array_keys( $languages ), '' );
	$language  = count( $blocks ) === 1 ? wpm_get_default_language() : '';

	foreach ( $blocks as $idx => $block ) {
		// Every odd block contains the language of '[:language]'.
		if ( $idx % 2 === 1 ) {
			$language = $block;
		} elseif ( isset( $result[ $language ] ) ) {
			$result[ $language ] .= $block;
		}
	}

	return array_map( 'trim', $result );
}

/**
 * Transform multidimensional array with multilingual strings to multidimensional array with multilingual arrays
 *
 * @param $value
 *
 * @return array|mixed|string
 */
function wpm_value_to_ml_array( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'wpm_value_to_ml_array', $value );
	}

	return wpm_string_to_ml_array( $value );
}

function wpm_ml_array_to_string( $strings ) {

	$string = '';

	if ( ! wpm_is_ml_array( $strings ) ) {
		return $string;
	}

	$languages = wpm_get_lang_option();
	foreach ( $strings as $key => $value ) {
		if ( ( '' !== $value ) && isset( $languages[ $key ] ) ) {
			if ( wpm_is_ml_string( $value ) ) {
				$string = wpm_translate_string( $string );
			}
			$string .= '[:' . $key . ']' . trim( $value );
		}
	}
	
	/* foreach ($languages as $key => $value) {
		if(isset($strings[$key]) && $strings[$key]=="" && isset($strings['en']) && $strings['en']!=""){
			$trans_content =  trim( $strings['en'] );
			$trans_content = wpm_ml_auto_translate_content($trans_content,'en',$key);
			$string .= '[:' . $key . ']' . $trans_content;
		}
	} */
	if ( $string ) {
		$string .= '[:]';
	}
	return $string;
}

/**
 * Transform multidimensional array with multilingual arrays to multidimensional array with multilingual strings
 *
 * @param $value
 *
 * @return array|string
 */
function wpm_ml_value_to_string( $value ) {

	if ( is_array( $value ) && ! empty( $value ) ) {

		if ( wpm_is_ml_array( $value ) ) {
			return wpm_ml_array_to_string( $value );
		}

		return array_map( 'wpm_ml_value_to_string', $value );
	}

	return $value;
}

/**
 * Set new value to multidimensional array with multilingual arrays by config
 *
 * @param        $localize_array
 * @param mixed  $value
 * @param array  $config
 * @param string $lang
 *
 * @return array|bool
 */
function wpm_set_language_value( $localize_array, $value, $config = array(), $lang = '' ) {
	$languages = wpm_get_languages();
	$new_value = array();

	if ( ! $lang || ! isset( $languages[ $lang ] ) ) {
		$lang = wpm_get_language();
	}

	if ( is_array( $value ) && null !== $config ) {
		foreach ( $value as $key => $item ) {
			if ( isset( $config['wpm_each'] ) ) {
				$config_key = $config['wpm_each'];
			} else {
				$config_key = ( isset( $config[ $key ] ) ? $config[ $key ] : null );
			}

			if ( ! isset( $localize_array[ $key ] ) ) {
				$localize_array[ $key ] = array();
			}

			$new_value[ $key ] = wpm_set_language_value( $localize_array[ $key ], $value[ $key ], $config_key, $lang );
		}
	} else {
		if ( null !== $config && ! is_bool( $value ) ) {

			if ( wpm_is_ml_string( $value ) ) {
				$value = wpm_translate_string( $value );
			}

			if ( wpm_is_ml_array( $localize_array ) ) {
				$new_value = $localize_array;
				$new_value[ $lang ] = $value;
			} else {
				if ( isJSON( $value ) || is_serialized_string( $value ) ) {
					$new_value  = $value;
				} else {
					$result = array_fill_keys( array_keys( $languages ), '' );
					$result[ $lang ] = $value;
					$new_value  = $result;
				}
			}
		} else {
			$new_value = $value;
		}
	}// End if().

	return $new_value;
}


/**
 * Translate WP object
 *
 * @param        $object
 * @param string $lang
 *
 * @return mixed
 */
function wpm_translate_object( $object, $lang = '' ) {

	foreach ( get_object_vars( $object ) as $key => $content ) {
		switch ( $key ) {
			case 'attr_title':
			case 'post_title':
			case 'name':
			case 'title':
				$object->$key = wpm_translate_string( $content, $lang );
				break;
			case 'post_excerpt':
			case 'description':
			case 'post_content':
				if ( is_serialized_string( $content ) ) {
					$object->$key = serialize( wpm_translate_value( unserialize( $content ), $lang ) );
					break;
				}

				if ( isJSON( $content ) ) {
					$object->$key = wp_json_encode( wpm_translate_value( json_decode( $content, true ), $lang ) );
					break;
				}

				if ( wpm_is_ml_string( $content ) ) {
					$object->$key = wpm_translate_string( $content, $lang );
					break;
				}
		}
	}

	return $object;
}


/**
 * Translate post
 *
 * @param $post
 *
 * @param string $lang
 *
 * @return object WP_Post
 */
function wpm_translate_post( $post, $lang = '' ) {

	if ( ! is_object( $post ) || null === wpm_get_post_config( $post->post_type ) ) {
		return $post;
	}

	return wpm_translate_object( $post, $lang );
}


/**
 * Translate term
 *
 * @param $term
 *
 * @param $taxonomy
 *
 * @param string $lang
 *
 * @return object WP_Term
 */
function wpm_translate_term( $term, $taxonomy, $lang = '' ) {

	if ( null === wpm_get_taxonomy_config( $taxonomy ) ) {
		return $term;
	}

	if ( is_object( $term ) ) {
		return wpm_translate_object( $term, $lang );
	}

	if ( is_array( $term ) ) {
		return wpm_translate_value( $term, $lang );
	}

	return $term;
}


/**
 * Untranslate WP_Post object
 *
 * @param $post
 *
 * @return mixed
 */
function wpm_untranslate_post( $post ) {
	if ( $post instanceof WP_Post ) {
		global $wpdb;
		$cache_key 	= 'wpm_posts_by_id_key_'.$post->ID;
		$orig_post 		= wp_cache_get($cache_key);
		if( false === $orig_post ){
			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$orig_post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d;", $post->ID ) );
			wp_cache_set( $cache_key, $orig_post );
		}
		foreach ( get_object_vars( $post ) as $key => $content ) {
			switch ( $key ) {
				case 'post_title':
				case 'post_content':
				case 'post_excerpt':
					$post->$key = $orig_post->$key;
					break;
			}
		}
	}

	return $post;
}

/**
 * Check if array is multilingual
 *
 * @param $array
 *
 * @return bool
 */
function wpm_is_ml_array( $array ) {

	if ( ! is_array( $array ) || wp_is_numeric_array( $array ) ) {
		return false;
	}

	$languages = wpm_get_lang_option();

	foreach ( $array as $key => $item ) {
		if ( ! isset( $languages[ $key ] ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Check if string is multilingual
 *
 * @param $string
 *
 * @return bool
 */
function wpm_is_ml_string( $string ) {
	if ( ! is_string( $string ) || is_serialized_string( $string ) || isJSON( $string ) ) {
		return false;
	}

	return preg_match( '#\[:[a-z-]+\]#im', $string );
}

/**
 * Check if value with multilingual strings
 *
 * @param $value
 *
 * @return bool
 */
function wpm_is_ml_value( $value ) {

	if ( is_array( $value ) && ! empty( $value ) ) {
		foreach ( $value as $item ) {
			if ( wpm_is_ml_value( $item ) ) {
				return true;
			}
		}

		return false;
	}

	return wpm_is_ml_string( $value );
}

/**
 * Set new data to value
 *
 * @param $old_value
 * @param $new_value
 * @param array $config
 * @param string $lang
 *
 * @return array|bool|string
 */
function wpm_set_new_value( $old_value, $new_value, $config = array(), $lang = '' ) {

	if ( is_bool( $new_value ) ) {
		return $new_value;
	}

	if ( is_serialized_string( $old_value ) || isJSON( $old_value ) ) {
		return $old_value;
	}

	$old_value = wpm_value_to_ml_array( $old_value );

	if ( wpm_is_ml_array( $old_value ) ) {
		foreach ($old_value as $key => $lang_value) {
			if ( strpos($lang_value, '{"') || strpos($lang_value, ':{"') || strpos($lang_value, '""')  || strpos($lang_value, '":"') ) {
				$old_value[ $key ] = wp_slash( $lang_value );
			}
		}
	}

	$value = wpm_set_language_value( $old_value, $new_value, $config, $lang );
	$value = wpm_ml_value_to_string( $value );

	return $value;
}

/**
 * Filter content if WP Githun MD plugin is active and string contains any <code> tags
 * https://github.com/ahmedkaludi/wp-multilang/issues/99
 * @param	$string 	string
 * @return	$string 	string
 * @since 	2.4.14
 * */
add_filter( 'wpm_filter_string_to_ml_array', 'wpm_filter_string_for_github_md_plugin' );
function wpm_filter_string_for_github_md_plugin( $string ) {

	$flag 	=	0;

	if ( in_array( 'githuber-md/githuber-md.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ), true )  ) ) {
		// Check if string contains <code> tag and it's post markdown option is enabled
		if ( strpos( $string, '<code>' ) !== false ) {
			$flag 	=	1;
		}
	}
	
	if ( $flag == 0 ) {
		$string = htmlspecialchars_decode( $string );
	}

	return $string;
}


/**
 * Transform multilingual array to multilingual string
 *
 * @param $strings
 *
 * @return string
 * @since 1.4
 */
if ( ! function_exists( 'wpm_ml_get_language_string' ) ) {
	function wpm_ml_get_language_string( $string,$source ) {
		if( preg_match( '/\[:'.$source.'\](.*?)\[:/si', $string, $matches ) ) {
			if( isset( $matches[1] ) ) {
				$string = $matches[1];
			}
		}
		return $string;
	}
}

if ( ! function_exists( 'wpm_ml_check_language_string' ) ) {
	function wpm_ml_check_language_string( $string, $source ) {
		$is_exist 		= false;
		if( preg_match( '/\[:'.$source.'\](.*?)\[:/si', $string ) ) {
			$is_exist 	= true;
		}
		return $is_exist;
	}
}

if ( ! function_exists( 'wpm_ml_log_message' ) ) {
	function wpm_ml_log_message( $message, $level = 'info' ) {
		$log_file = WP_CONTENT_DIR . '/wpm_translation.log';
		$timestamp = current_time('Y-m-d H:i:s');
		$log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
		file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
	}
}

if ( ! function_exists( 'wpm_ml_auto_translate_content' ) ) {
	function wpm_ml_auto_translate_content( $string, $source, $target, $batch_start = 0, $batch_size = 100 ) {
		if( $string == "" ) {
			return $string;
		}
		$translated_strings = [];
			// Load HTML into DOMDocument.
			libxml_use_internal_errors(true); 
			if(preg_match('/<[^>]+>/',  $string) !== 1) {
				$words = explode(' ', $string);
				$chunks = array_chunk($words, 500);
				$translated_string = '';
				$chunk_count = 0;
				$total_chunks = count($chunks);
				foreach ($chunks as $chunk) {
					$chunk_count++;
					$chunk_string = implode(' ', $chunk);
					$t_text = wpm_ml_auto_fetch_translation( $chunk_string, $source, $target );
					$translated_string .= $t_text ? $t_text : $chunk_string;
					
					// Add small delay to prevent overwhelming the API
					usleep(100000); // 0.1 second delay
				}
				$translated_string = $translated_string ? $translated_string : $string;
				return $translated_string;
			}

			$dom = new DOMDocument('1.0', 'UTF-8');
			$isHTML = $dom->loadHTML( '<?xml encoding="UTF-8"?>'.$string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			libxml_clear_errors();
			if ( $isHTML ) {
		
				$xpath = new DOMXPath( $dom );
				$text_nodes = $xpath->query('//text()[normalize-space() and not(ancestor::script or ancestor::style or ancestor::noscript or ancestor::iframe)]');			
			
	            $enable_logging = apply_filters( 'wpm_ml_translation_debug_log', true );
	            $total_nodes = $text_nodes->length;
	            
	            // If batch_start is 0 and batch_size is 0, return total node count
	            if ( $batch_start === 0 && $batch_size === 0 ) {
	                return array( 'total_nodes' => $total_nodes );
	            }
	            
	            $node_count = 0;
	            $processed_count = 0;
	            
	            foreach ( $text_nodes as $key => $node ) {
	                $node_count++;
	                
	                // Skip nodes before batch start
	                if ( $node_count <= $batch_start ) {
	                    continue;
	                }
	                
	                // Stop if we've processed the batch size
	                if ( $processed_count >= $batch_size ) {
	                    if ( $enable_logging ) {
	                        wpm_ml_log_message( sprintf('Reached batch size limit (%d/%d). Processed %d nodes.', $batch_size, $total_nodes, $processed_count) );
	                    }
	                    break;
	                }
	                
	                // Prepare and guard the text value
	                $node_value = is_string( $node->nodeValue ) ? $node->nodeValue : '';
	                $source_text = wpm_ml_remove_special_characters( $node_value );
	                
	                if ( $enable_logging ) {
	                    wpm_ml_log_message( sprintf('Processing text node %d of %d (len=%d)', $node_count, $total_nodes, strlen($source_text)) );
	                }
	                
	                try {
	                    $translated_string = wpm_ml_auto_fetch_translation( $source_text, $source, $target );
	                } catch ( \Throwable $e ) {
	                    // Never break the loop on translation errors
	                    if ( $enable_logging ) {
	                        wpm_ml_log_message( sprintf('Error translating node %d: %s', $node_count, $e->getMessage()), 'error' );
	                    }
	                    $translated_string = false;
	                }
	                
	                // Fallback to original on failure
	                $translated_string = $translated_string ? $translated_string : $node_value;
	                $translated_string = wpm_ml_add_special_characters( $translated_string );
	                $translated_strings[$key] = $translated_string;
	                $processed_count++;
	                
	                // Add small delay to prevent overwhelming the API
	                usleep(50000); // 0.05 second delay
	            }
				
				// Replace text nodes with translated content.
				foreach ( $text_nodes as $key => $node ) {
					if ( isset( $translated_strings[ $key ] ) ) {
						$node->nodeValue = $translated_strings[ $key ];
					}
				}
			
				// Return the updated HTML.
				$final_html = $dom->saveHTML();
				if ( strpos( $final_html, '<?xml encoding="UTF-8"?>' ) === 0 ) {
					// If the HTML starts with xml encoding, remove it.
					$final_html = str_replace( '<?xml encoding="UTF-8"?>', '', $final_html );
				}
				return $final_html;
			}
				
			return $string;
	}
}

if ( ! function_exists( 'wpm_ml_auto_fetch_translation' ) ) {
	function wpm_ml_auto_fetch_translation( $string, $source, $target ) {

		if( $string == "" || $source == $target || $source == "" || $target == "" ) {
			return false;
		}


		$ai_settings 	=	array();
		$ai_settings 	=	WPM_OpenAI::get_settings();

		switch ( $ai_settings['api_provider'] ) {

			case 'openai':

				if ( ! empty( $ai_settings['api_keys']['openai'] ) ) {
					$string 	=	WPM_OpenAI::translate_content( $string, $source, $target, $ai_settings );
				}
				
			break;

			case 'multilang':
			default:

				$string 	=	apply_filters( 'wpmpro_auto_translate_content', $string, $source, $target );

			break;
		}

		return $string;

	}
}

if ( ! function_exists( 'wpm_ml_remove_special_characters' ) ) {
	function wpm_ml_remove_special_characters( $string = '' ){
		if( $string ){
			
			 $find = ['|'];
			 
			 $tokens = ['_PIPE_TOKEN_'];
	        
	         $stringWithToken = str_replace( $find, $tokens, $string );
			 
			 return $stringWithToken;
		}
		
		return '';
	}
}

if ( ! function_exists( 'wpm_ml_add_special_characters' ) ) {
	function wpm_ml_add_special_characters( $string = '' ){
		if( $string ){
			
			 $tokens = ['_PIPE_TOKEN_'];
			 
			 $replace = ['|'];
	        
	         $stringWithoutToken = str_replace( $tokens, $replace, $string );
			 
			 return $stringWithoutToken;
			
		}
		
		return '';
	}
}

function wpm_is_pro_active(){
	
	$is_active 		=	is_plugin_active( 'wp-multilang-pro/wp-multilang-pro.php' );
	return $is_active;

}