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
use WPM\Includes\Admin\Settings\WPM_Settings_AI_Integration;
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
			if ( is_string( $lang_value ) ) {
				if ( strpos($lang_value, '{"') || strpos($lang_value, ':{"') || strpos($lang_value, '""')  || strpos($lang_value, '":"') ) {
					$old_value[ $key ] = wp_slash( $lang_value );
				}
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

if ( ! function_exists( 'wpm_ml_is_untranslatable' ) ) {
	/**
	 * Check if a string doesn't need translation (numbers, symbols, template tags, etc.)
	 *
	 * @param  string $string
	 * @return bool
	 * @since  2.4.30
	 */
	function wpm_ml_is_untranslatable( $string ) {
		$trimmed = trim( $string );
		if ( $trimmed === '' ) return true;
		if ( preg_match( '/^[\d\s.,+\-:;\/\\\\]+$/', $trimmed ) ) return true;
		if ( mb_strlen( $trimmed ) <= 1 ) return true;
		if ( preg_match( '/^\{[^}]+\}$/', $trimmed ) ) return true;
		if ( preg_match( '/^[㎡㎥㎏℃℉%°²³]+$/u', $trimmed ) ) return true;
		return false;
	}
}

if ( ! function_exists( 'wpm_ml_batch_translate' ) ) {
	/**
	 * Translate an array of strings in a single API call using a separator.
	 * Falls back to individual calls if the API doesn't preserve the separator.
	 *
	 * @param  array  $strings  Indexed array of strings to translate.
	 * @param  string $source   Source language code.
	 * @param  string $target   Target language code.
	 * @return array            Indexed array of translated strings (same order/count as input).
	 * @since  2.4.30
	 */
	function wpm_ml_batch_translate( array $strings, $source, $target ) {
		if ( empty( $strings ) ) {
			return $strings;
		}

		$separator     = "\n|||WPM_SEP|||\n";
		$max_batch_len = 8000;

		$batches   = array();
		$current   = array();
		$current_len = 0;

		foreach ( $strings as $s ) {
			$add_len = strlen( $s ) + strlen( $separator );
			if ( $current_len + $add_len > $max_batch_len && ! empty( $current ) ) {
				$batches[] = $current;
				$current   = array();
				$current_len = 0;
			}
			$current[]   = $s;
			$current_len += $add_len;
		}
		if ( ! empty( $current ) ) {
			$batches[] = $current;
		}

		$all_translated = array();

		foreach ( $batches as $batch ) {
			$combined   = implode( $separator, $batch );
			$translated = wpm_ml_auto_fetch_translation( $combined, $source, $target );

			if ( $translated && $translated !== 'false' ) {
				$parts = explode( '|||WPM_SEP|||', $translated );
				$parts = array_map( 'trim', $parts );

				if ( count( $parts ) === count( $batch ) ) {
					foreach ( $parts as $i => $p ) {
						$all_translated[] = ( $p !== '' && $p !== 'false' ) ? $p : $batch[ $i ];
					}
				} else {
					wpm_ml_log_message( sprintf(
						'Batch separator mismatch: expected %d, got %d. Falling back to individual calls.',
						count( $batch ), count( $parts )
					), 'warning' );
					foreach ( $batch as $single ) {
						$t = wpm_ml_auto_fetch_translation( $single, $source, $target );
						$all_translated[] = ( $t && $t !== 'false' ) ? $t : $single;
					}
				}
			} else {
				foreach ( $batch as $single ) {
					$t = wpm_ml_auto_fetch_translation( $single, $source, $target );
					$all_translated[] = ( $t && $t !== 'false' ) ? $t : $single;
				}
			}
		}

		return $all_translated;
	}
}

if ( ! function_exists( 'wpm_ml_auto_translate_content' ) ) {
	function wpm_ml_auto_translate_content( $string, $source, $target, $batch_start = 0, $batch_size = 100 ) {
		if ( $string == "" ) {
			return $string;
		}

		libxml_use_internal_errors( true );

		// Plain text (no HTML tags)
		if ( preg_match( '/<[^>]+>/', $string ) !== 1 ) {

			if ( wpm_ml_is_untranslatable( $string ) ) {
				return $string;
			}

			$words  = explode( ' ', $string );
			$chunks = array_chunk( $words, 500 );

			if ( count( $chunks ) <= 1 ) {
				$t_text = wpm_ml_auto_fetch_translation( $string, $source, $target );
				return $t_text ? $t_text : $string;
			}

			$chunk_strings = array_map( function( $chunk ) {
				return implode( ' ', $chunk );
			}, $chunks );

			wpm_ml_log_message( sprintf( 'Plain-text batching: %d chunks via batch_translate', count( $chunk_strings ) ) );
			$translated_chunks = wpm_ml_batch_translate( $chunk_strings, $source, $target );
			return implode( ' ', $translated_chunks );
		}

		// HTML content
		$dom    = new DOMDocument( '1.0', 'UTF-8' );
		$isHTML = $dom->loadHTML( '<?xml encoding="UTF-8"?>' . $string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		if ( ! $isHTML ) {
			return $string;
		}

		$xpath      = new DOMXPath( $dom );
		$text_nodes = $xpath->query( '//text()[normalize-space() and not(ancestor::script or ancestor::style or ancestor::noscript or ancestor::iframe)]' );
		$total_nodes = $text_nodes->length;

		if ( $batch_start === 0 && $batch_size === 0 ) {
			return array( 'total_nodes' => $total_nodes );
		}

		$enable_logging = apply_filters( 'wpm_ml_translation_debug_log', true );
		wpm_ml_log_message( sprintf( 'HTML batching: %d total text nodes', $total_nodes ) );

		// Collect all translatable text nodes in one pass
		$translatable_keys   = array();
		$translatable_texts  = array();
		$original_values     = array();
		$node_count          = 0;
		$collected           = 0;

		foreach ( $text_nodes as $key => $node ) {
			$node_count++;

			if ( $node_count <= $batch_start ) {
				continue;
			}
			if ( $collected >= $batch_size ) {
				break;
			}

			$node_value  = is_string( $node->nodeValue ) ? $node->nodeValue : '';
			$source_text = wpm_ml_remove_special_characters( $node_value );

			if ( wpm_ml_is_untranslatable( $source_text ) ) {
				continue;
			}

			$translatable_keys[]  = $key;
			$translatable_texts[] = $source_text;
			$original_values[]    = $node_value;
			$collected++;
		}

		if ( empty( $translatable_texts ) ) {
			$final_html = $dom->saveHTML();
			return str_replace( '<?xml encoding="UTF-8"?>', '', $final_html );
		}

		wpm_ml_log_message( sprintf( 'Sending %d text nodes in batched API call(s) (skipped %d untranslatable)',
			count( $translatable_texts ), $total_nodes - count( $translatable_texts ) ) );

		// Translate all collected texts in batched API calls
		try {
			$translated_texts = wpm_ml_batch_translate( $translatable_texts, $source, $target );
		} catch ( \Throwable $e ) {
			if ( $enable_logging ) {
				wpm_ml_log_message( sprintf( 'Batch translation error: %s', $e->getMessage() ), 'error' );
			}
			$translated_texts = $translatable_texts;
		}

		// Map translations back to DOM nodes
		foreach ( $translatable_keys as $i => $dom_key ) {
			$translated = isset( $translated_texts[ $i ] ) ? $translated_texts[ $i ] : $original_values[ $i ];
			$translated = wpm_ml_add_special_characters( $translated );

			foreach ( $text_nodes as $nk => $node ) {
				if ( $nk === $dom_key ) {
					$node->nodeValue = $translated;
					break;
				}
			}
		}

		$final_html = $dom->saveHTML();
		if ( strpos( $final_html, '<?xml encoding="UTF-8"?>' ) === 0 ) {
			$final_html = str_replace( '<?xml encoding="UTF-8"?>', '', $final_html );
		}
		return $final_html;
	}
}

if ( ! function_exists( 'wpm_ml_auto_fetch_translation' ) ) {
	function wpm_ml_auto_fetch_translation( $string, $source, $target ) {

		if( $string == "" || $source == $target || $source == "" || $target == "" ) {
			return false;
		}


		if ( wpm_is_pro_active() ) {
			$string 	=	apply_filters( 'wpmpro_auto_translate_content', $string, $source, $target );
			return $string;
		}
		
		$ai_settings 	=	array();
		$ai_settings 	=	WPM_Settings_AI_Integration::get_openai_settings();
		$enable_logging = apply_filters( 'wpm_ml_translation_debug_log', true );

		switch ( $ai_settings['api_provider'] ) {

			case 'openai':

				if ( ! empty( $ai_settings['api_keys']['openai'] ) ) {
					try {
						$string 	=	WPM_OpenAI::translate_content( $string, $source, $target, $ai_settings );
					} catch ( \Throwable $e ) {
						if ( $enable_logging ) {
	                        wpm_ml_log_message( sprintf('Error in openAI translation: %s', $e->getMessage()), 'error' );
	                    }
					}
				}
				
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