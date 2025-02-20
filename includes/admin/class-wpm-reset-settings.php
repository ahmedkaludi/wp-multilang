<?php
/**
 * WP Multilang Admin Reset Settings Class
 *
 * @category Admin
 * @package  WPM/Admin
 * @since	2.4.15
 */

namespace WPM\Includes\Admin;
use WPM\Includes\WPM_Install;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPM_Admin_Settings Class.
 */
class WPM_Reset_Settings {

	/**
	 * Reset plugin settings and post translations
	 * @since 	2.4.15
	 * */
	public function reset_settings(){
		
		self::reset_plugin_settings();
		self::wpm_uninstall_translations_data();

	}

	/**
	 * Reset plugin settings
	 * @since	2.4.15
	 * */
	public static function reset_plugin_settings(){

		$default_language 						=	array();
		$default_language['en']['enable'] 		=	1;
		$default_language['en']['locale'] 		=	'en_US';
		$default_language['en']['name'] 		=	'English (US)';
		$default_language['en']['translation'] 	=	'en_US';
		$default_language['en']['date'] 		=	'';
		$default_language['en']['time'] 		=	'';
		$default_language['en']['flag'] 		=	'us.png';
		
		update_option( 'wpm_admin_notices', array() );
		update_option( 'wpm_meta_box_errors', array() );
		update_option( 'wpm_site_language', wpm_get_default_language() );
		update_option( 'wpm_show_untranslated_strings', 'yes' );
		update_option( 'wpm_use_redirect', 'no' );
		update_option( 'wpm_use_prefix', 'no' );
		update_option( 'wpm_string_translation', 'no' );
		update_option( 'wpm_base_translation', 'no' );
		update_option( 'wpm_uninstall_translations', 'no' );
		update_option( 'wpm_elementor_compatibility_free', 'no' );
		update_option( 'wpm_divi_compatibility_free', 'no' );
		update_option( 'wpm_languages', $default_language );

	}

	/**
	 * Reset data translation to default
	 * @since 	2.4.15
	 * */
	public static function wpm_uninstall_translations_data(){

		global $wpdb;

		// Roles + caps.
		WPM_Install::remove_roles();
		$config           = wpm_get_config();
		$default_language = wpm_get_default_language();

		$post_types = get_post_types( '', 'names' );

		foreach ( $post_types as $post_type ) {

			$post_config = wpm_get_post_config( $post_type );

			if ( is_null( $post_config ) ) {
				continue;
			}

			$fields  = wpm_filter_post_config_fields( array_keys( $post_config ) );
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.QuotedSimplePlaceholder,WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason We are just cleaning the data that has been changed by our plugin
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT ID, " . implode( ', ', $fields ) . " FROM {$wpdb->posts} WHERE post_type = '%s';", esc_sql( $post_type ) ) );

			foreach ( $results as $result ) {

				$args       = array();
				$new_result = wpm_translate_object( $result, $default_language );

				foreach ( get_object_vars( $new_result ) as $key => $content ) {
					if ( 'ID' == $key ) {
						continue;
					}

					$args[ $key ] = $content;
				}

				//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching --Reason We are just cleaning the data that has been changed by our plugin
				$wpdb->update( $wpdb->posts, $args, array( 'ID' => $result->ID ) );
			}
		}

		$taxonomies = get_taxonomies();

		foreach ( $taxonomies as $taxonomy ) {

			if ( is_null( wpm_get_taxonomy_config( $taxonomy ) ) ) {
				continue;
			}

			//phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedSimplePlaceholder, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery --Reason We are just cleaning the data that has been changed by our plugin
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT t.term_id, `name`, description FROM {$wpdb->terms} t LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.taxonomy = '%s';", esc_sql( $taxonomy ) ) );

			foreach ( $results as $result ) {

				$result      = wpm_translate_object( $result, $default_language );
				$description = $result->description;
				$name        = $result->name;

				//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason We are just cleaning the data that has been changed by our plugin
				$wpdb->update( $wpdb->term_taxonomy, compact( 'description' ), array( 'term_id' => $result->term_id ) );
				//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason We are just cleaning the data that has been changed by our plugin
				$wpdb->update( $wpdb->terms, compact( 'name' ), array( 'term_id' => $result->term_id ) );
			}
		}

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason We are just cleaning the data that has been changed by our plugin
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%';" );
		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason We are just cleaning the data that has been changed by our plugin
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%';" );

		foreach ( $config as $key => $item_config ) {

			switch ( $key ) {

				case 'post_fields':
					//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason We are just cleaning the data that has been changed by our plugin
					$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_languages' ) );

					//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason We are just cleaning the data that has been changed by our plugin
					$results = $wpdb->get_results( "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE '%![:__!]%' ESCAPE '!' OR meta_value LIKE '%{:__}%' OR meta_value LIKE '%<!--:__-->%';" );
					foreach ( $results as $result ) {
						$meta_value = $result->meta_value;

						if ( is_serialized_string( $meta_value ) ) {
							$meta_value = serialize( wpm_translate_value( unserialize( $meta_value ), $default_language ) );
						}

						if ( isJSON( $meta_value ) ) {
							$meta_value = wp_json_encode( wpm_translate_value( json_decode( $meta_value, true ), $default_language ) );
						}

						if ( wpm_is_ml_string( $meta_value ) ) {
							$meta_value = wpm_translate_string( $meta_value, $default_language );
						}

						//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason We are just cleaning the data that has been changed by our plugin
						$wpdb->update( $wpdb->postmeta, compact( 'meta_value' ), array( 'meta_id' => $result->meta_id ) );
					}

					break;

				case 'term_fields':
					//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
					$wpdb->delete( $wpdb->termmeta, array( 'meta_key' => '_languages' ) );

					//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
					$results = $wpdb->get_results( "SELECT meta_id, meta_value FROM {$wpdb->termmeta} WHERE meta_value LIKE '%![:__!]%' ESCAPE '!' OR meta_value LIKE '%{:__}%' OR meta_value LIKE '%<!--:__-->%';" );

					foreach ( $results as $result ) {
						$meta_value = $result->meta_value;

						if ( is_serialized_string( $meta_value ) ) {
							$meta_value = serialize( wpm_translate_value( unserialize( $meta_value ), $default_language ) );
						}

						if ( isJSON( $meta_value ) ) {
							$meta_value = wp_json_encode( wpm_translate_value( json_decode( $meta_value, true ), $default_language ) );
						}

						if ( wpm_is_ml_string( $meta_value ) ) {
							$meta_value = wpm_translate_string( $meta_value, $default_language );
						}

						//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
						$wpdb->update( $wpdb->termmeta, compact( 'meta_value' ), array( 'meta_id' => $result->meta_id ) );
					}

					break;

				case 'comment_fields':
					//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
					$wpdb->delete( $wpdb->commentmeta, array( 'meta_key' => '_languages' ) );

					//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
					$results = $wpdb->get_results( "SELECT meta_id, meta_value FROM {$wpdb->commentmeta} WHERE meta_value LIKE '%s' OR meta_value LIKE '%![:__!]%' ESCAPE '!' OR meta_value LIKE '%{:__}%' OR meta_value LIKE '%<!--:__-->%';" );

					foreach ( $results as $result ) {
						$meta_value = $result->meta_value;

						if ( is_serialized_string( $meta_value ) ) {
							$meta_value = serialize( wpm_translate_value( unserialize( $meta_value ), $default_language ) );
						}

						if ( isJSON( $meta_value ) ) {
							$meta_value = wp_json_encode( wpm_translate_value( json_decode( $meta_value, true ), $default_language ) );
						}

						if ( wpm_is_ml_string( $meta_value ) ) {
							$meta_value = wpm_translate_string( $meta_value, $default_language );
						}

						//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
						$wpdb->update( $wpdb->commentmeta, compact( 'meta_value' ), array( 'meta_id' => $result->meta_id ) );
					}

					break;

				case 'user_fields':

					//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
					$results = $wpdb->get_results( "SELECT umeta_id, meta_value FROM {$wpdb->usermeta} WHERE meta_value LIKE '%![:__!]%' ESCAPE '!' OR meta_value LIKE '%{:__}%' OR meta_value LIKE '%<!--:__-->%';" );

					foreach ( $results as $result ) {
						$meta_value = $result->meta_value;

						if ( is_serialized_string( $meta_value ) ) {
							$meta_value = serialize( wpm_translate_value( unserialize( $meta_value ), $default_language ) );
						}

						if ( isJSON( $meta_value ) ) {
							$meta_value = wp_json_encode( wpm_translate_value( json_decode( $meta_value, true ), $default_language ) );
						}

						if ( wpm_is_ml_string( $meta_value ) ) {
							$meta_value = wpm_translate_string( $meta_value, $default_language );
						}

						//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
						$wpdb->update( $wpdb->usermeta, compact( 'meta_value' ), array( 'umeta_id' => $result->umeta_id ) );
					}

					break;

				case 'options':

					//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
					$results = $wpdb->get_results( "SELECT option_id, option_value FROM {$wpdb->options} WHERE option_value LIKE '%![:__!]%' ESCAPE '!' OR option_value LIKE '%{:__}%' OR option_value LIKE '%<!--:__-->%';" );

					foreach ( $results as $result ) {
						$option_value = $result->option_value;

						if ( is_serialized_string( $option_value ) ) {
							$option_value = serialize( wpm_translate_value( unserialize( $option_value ), $default_language ) );
						}

						if ( isJSON( $option_value ) ) {
							$option_value = wp_json_encode( wpm_translate_value( json_decode( $option_value, true ), $default_language ) );
						}

						if ( wpm_is_ml_string( $option_value ) ) {
							$check_value 	=	maybe_unserialize( $option_value );
							if ( is_array( $check_value ) || is_object( $check_value ) ) {
								$option_value = serialize( wpm_translate_value( unserialize( $option_value ), $default_language ) );	
							}else{
								$option_value = wpm_translate_string( $option_value, $default_language );	
							}
						}

						//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
						$wpdb->update( $wpdb->options, compact( 'option_value' ), array( 'option_id' => $result->option_id ) );
					}

					/**
					 * Delete custom options stored by WP-Multilang plgin
					 * @since 2.4.17
					 * */
					$delete_options 	=	array( 'gf_display_meta_' );

					// Prepare the LIKE conditions for the query
					$like_clauses = array_map(function($prefix) use ($wpdb) {
					    return $wpdb->prepare("option_name LIKE %s", $prefix . '%');
					}, $delete_options);
					// Join the conditions with OR
					$where_clause = implode(' OR ', $like_clauses);
					// Query the options table
					$custom_options = $wpdb->get_results("SELECT * FROM {$wpdb->options} WHERE $where_clause");

					if ( ! empty( $custom_options ) && is_array( $custom_options ) ) {
						foreach ( $custom_options as $gf_options ) {
							if ( is_object( $gf_options ) && isset( $gf_options->option_id ) ) {
								$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_id=$gf_options->option_id");		
							}
						}
					}

					break;

				case 'site_options':

					//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
					$results = $wpdb->get_results( "SELECT meta_id, meta_value FROM {$wpdb->sitemeta} WHERE meta_value LIKE '%![:__!]%' ESCAPE '!' OR meta_value LIKE '%{:__}%' OR meta_value LIKE '%<!--:__-->%';" );

					foreach ( $results as $result ) {
						$meta_value = $result->meta_value;

						if ( is_serialized_string( $meta_value ) ) {
							$meta_value = serialize( wpm_translate_value( unserialize( $meta_value ), $default_language ) );
						}

						if ( isJSON( $meta_value ) ) {
							$meta_value = wp_json_encode( wpm_translate_value( json_decode( $meta_value, true ), $default_language ) );
						}

						if ( wpm_is_ml_string( $meta_value ) ) {
							$meta_value = wpm_translate_string( $meta_value, $default_language );
						}

						//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
						$wpdb->update( $wpdb->sitemeta, compact( 'meta_value' ), array( 'meta_id' => $result->meta_id ) );
					}

					break;
			} // End switch().
		} // End foreach().

		// Clear any cached data that has been removed
		wp_cache_flush();
	}

}