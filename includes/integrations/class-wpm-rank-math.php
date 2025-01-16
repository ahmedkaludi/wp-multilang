<?php
/**
 * Class for capability with Rank Math Seo Plugin
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class    WPM_Rank_Math
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Valentyn Riaboshtan
 */
class WPM_Rank_Math {

	/**
	 * WPM_Rank_Math constructor.
	 */
	public function __construct() {
		add_filter( 'wpm_option_rank-math-options-titles_config', array( $this, 'set_posts_config' ) );
		add_filter( 'rank_math/sitemap/url', array( $this, 'add_alternate_sitemaplinks' ), 10, 2 );
		add_filter( 'rank_math/sitemap/entry', array( $this, 'add_lang_to_url' ), 10, 3 );
		add_filter( 'rank_math/sitemap/build_type', array( $this, 'add_filter_for_maps' ) );
		add_filter( 'rank_math/json_ld', array( $this, 'modify_schema_json_ld' ), 99, 2 );
		add_action( 'rank_math/schema/update', array( $this, 'modify_schema_meta_data' ), 10, 3 );
		add_filter( 'delete_post_metadata_by_mid', array( $this, 'delete_schema' ), 10, 2 );
		add_filter( 'rank_math/json_data', array( $this, 'modify_json_data' ), 10, 2 );
	}

	/**
	 * Add dynamically title setting for post types
	 *
	 * @param array $option_config
	 *
	 * @return array
	 */
	public function set_posts_config( $option_config ) {

		$post_types = get_post_types( array(), 'names' );
		foreach ( $post_types as $post_type ) {

			if ( null === wpm_get_post_config( $post_type ) ) {
				continue;
			}

			$option_post_config = array(
				"pt_{$post_type}_title"                => array(),
				"pt_{$post_type}_description"          => array(),
				"pt_{$post_type}_default_rich_snippet" => array(),
				"pt_{$post_type}_default_snippet_name" => array(),
				"pt_{$post_type}_default_snippet_desc" => array(),
				"pt_{$post_type}_default_article_type" => array(),
				"pt_{$post_type}_custom_robots"        => array(),
				"pt_{$post_type}_link_suggestions"     => array(),
				"pt_{$post_type}_ls_use_fk"            => array(),
				"pt_{$post_type}_primary_taxonomy"     => array(),
				"pt_{$post_type}_add_meta_box"         => array(),
				"pt_{$post_type}_bulk_editing"         => array(),
			);

			$option_config = wpm_array_merge_recursive( $option_post_config, $option_config );
		}

		$taxonomies = get_taxonomies();

		foreach ( $taxonomies as $taxonomy ) {

			if ( null === wpm_get_taxonomy_config( $taxonomy ) ) {
				continue;
			}

			$option_taxonomy_config = array(
				"tax_{$taxonomy}_title"        => array(),
				"tax_{$taxonomy}_description"     => array(),
				"tax_{$taxonomy}_custom_robots"   => array(),
				"tax_{$taxonomy}_robots"          => array(),
				"tax_{$taxonomy}_add_meta_box"    => array(),
				"remove_{$taxonomy}_snippet_data" => array(),
			);

			$option_config = wpm_array_merge_recursive( $option_taxonomy_config, $option_config );
		}

		return $option_config;
	}

	/**
	 * Add filter for each type
	 *
	 * @param $type
	 *
	 * @return mixed
	 */
	public function add_filter_for_maps( $type ) {
		add_filter( "rank_math/sitemap/{$type}_urlset", array( $this, 'add_namespace_to_xml' ) );
		return $type;
	}

	/**
	 * Add namespace for xmlns:xhtml
	 *
	 * @param $urlset
	 *
	 * @return mixed
	 */
	public function add_namespace_to_xml( $urlset ) {
		$urlset = str_replace(
			array(
				'http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd',
				'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"'
			),
			array(
				'http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd http://www.w3.org/1999/xhtml http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd',
				'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:xhtml="http://www.w3.org/1999/xhtml"'
			),
			$urlset );

		return $urlset;
	}

	/**
	 * Add separating by language to url
	 *
	 * @param array $url
	 * @param string $type
	 * @param object $object
	 *
	 * @return array
	 */
	public function add_lang_to_url( $url, $type, $object ) {

		$languages = array();

		switch ( $type ) {
			case 'post':
				$languages = get_post_meta( $object->ID, '_languages', true );
				break;
			case 'term':
				$languages = get_term_meta( $object->term_id, '_languages', true );
				break;
		}

		if ( $languages ) {
			$url['languages'] = $languages;
		}

		return $url;
	}

	/**
	 * Add alternate links to sitemap
	 *
	 * @param string $output
	 * @param array $url
	 *
	 * @return string
	 */
	public function add_alternate_sitemaplinks( $output, $url ) {
		$loc        = $output;
		$new_output = '';

		foreach ( wpm_get_languages() as $code => $language ) {

			if ( isset( $url['languages'] ) && ! in_array( $code, $url['languages'] ) ) {
				continue;
			}

			$alternate = array();
			$new_loc   = str_replace( $url['loc'], esc_url( wpm_translate_url( $url['loc'], $code ) ), $loc );

			foreach ( wpm_get_languages() as $key => $lg ) {
				if ( isset( $url['languages'] ) && ! in_array( $key, $url['languages'] ) ) {
					continue;
				}

				$alternate[ $key ] = sprintf( "\t<xhtml:link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />\n\t", esc_attr( wpm_sanitize_lang_slug( $lg['locale'] ) ), esc_url( wpm_translate_url( $url['loc'], $key ) ) );
			}

			$alternate  = apply_filters( 'wpm_sitemap_alternate_links', $alternate, $url['loc'], $code );
			$new_loc    = str_replace( '</url>', implode( '', $alternate ) . '</url>', $new_loc );
			$new_output .= $new_loc;
		}

		return $new_output;
	}
	
	/**
	 * Translate the schema markup as respective of there language
	 * @param 	$schemas 	array
	 * @param 	$json_ld 	object
	 * @return 	$schemas 	array
	 * @since 	2.4.16
	 * */
	public function modify_schema_json_ld( $schemas, $json_ld ) {
		
		global $wpdb;
		$flag 				=	0;
		if ( ! empty( $schemas ) && is_array( $schemas ) && is_object( $json_ld ) ) {

			$post_id 		=	0;
			if ( ! empty( $json_ld->post_id ) ) {
				$post_id 	=	$json_ld->post_id;
			}

			$current_lang 	=	wpm_get_language();

			foreach ( $schemas as $schema_key => $schema ) {

				if ( is_array( $schema ) && ! empty( $schema['@type'] ) && strpos( $schema_key, 'schema-' ) !== false ) {

					$translated_key 				=	'_wpm_rank_math_schema_'.$schema['@type'];
					$translated_schema 				=	'';

					if ( $post_id > 0 ) {
						$translated_schema 			=	get_post_meta( $post_id, $translated_key, true );
					}else{
						$meta_id 					=	absint( str_replace( 'schema-', '', $schema_key ) );
						if ( $meta_id > 0 ) {

							$term_meta 				= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->termmeta} WHERE meta_id = %d", $meta_id ) );
							if ( is_object( $term_meta ) && ! empty( $term_meta->term_id ) ) {
								$translated_schema 	=	get_term_meta( $term_meta->term_id, $translated_key, true );
							}
						}
					}
					
					if ( ! empty( $translated_schema ) && is_string( $translated_schema ) ) {

						$translated_schema 			= maybe_unserialize( base64_decode( wpm_translate_value( $translated_schema ) , true ) );

						if ( is_array( $translated_schema ) && ! empty( $translated_schema['@type'] ) && $schema['@type'] == $translated_schema['@type'] ) {
							$schemas[$schema_key] 	=	$translated_schema;	
							$flag 					=	1;
						}

					}

				}

			}

		}

		if ( $flag == 1 ) {
			$schemas = $json_ld->replace_variables( $schemas, [], $schemas );
			$schemas = $json_ld->filter( $schemas, $json_ld, $schemas );	
		}
		
		return $schemas;

	}

	/**
	 * Insert schema translation into db
	 * @param 	$object_id 		int
	 * @param 	$schemas 		array
	 * @param 	$object_type 	string
	 * @since 	2.4.16
	 * */
	public function modify_schema_meta_data( $object_id, $schemas, $object_type ) {
		
		if ( ! empty( $schemas ) && is_array( $schemas ) ) {

			foreach ( $schemas as $id => $schema ) {

				$schema   			=	$this->sanitize_schema_type( $schema );
				$type     			=	is_array( $schema['@type'] ) ? $schema['@type'][0] : $schema['@type'];
				$meta_key 			=	'_wpm_rank_math_schema_' . $type;
				$schema   			=	wp_kses_post_deep( $schema );

				switch ( $object_type ) {

					case 'post':

						$current_value	 	= 	get_post_meta( $object_id, $meta_key, true );

						if ( is_array( $schema ) || is_object( $schema ) ) {
							$schema 		=	maybe_serialize( $schema );
						}

						if ( empty( $current_value ) ) {
							$current_value 	=	base64_encode( $schema );
						}

						update_post_meta( $object_id, $meta_key, wpm_set_new_value( $current_value, base64_encode( $schema ) ) );

					break;

					case 'term':

						$current_value	 	= 	get_term_meta( $object_id, $meta_key, true );

						if ( is_array( $schema ) || is_object( $schema ) ) {
							$schema 		=	maybe_serialize( $schema );
						}

						if ( empty( $current_value ) ) {
							$current_value 	=	base64_encode( $schema );
						}

						update_term_meta( $object_id, $meta_key, wpm_set_new_value( $current_value, base64_encode( $schema ) ) );

					break;

				}
			}
		}

	}

	/**
	 * Sanitize schema data
	 * @param 	$schema 	array
	 * @return 	$schema 	array
	 * @since 	2.4.16
	 * */
	public function sanitize_schema_type( $schema ) {
		
		if ( ! isset( $schema['@type'] ) ) {
			return $schema;
		}

		if ( ! is_array( $schema['@type'] ) ) {
			// Sanitize single type.
			$schema['@type'] = preg_replace( '/[^a-zA-Z0-9]/', '', $schema['@type'] );
			return $schema;
		}

		// Sanitize each type.
		foreach ( $schema['@type'] as $key => $type ) {
			$schema['@type'][ $key ] = preg_replace( '/[^a-zA-Z0-9]/', '', $type );
		}

		return $schema;

	}

	/**
	 * Delete schema by meta id
	 * @param 	$delete 	bool
	 * @param 	$meta_id  	int
	 * @return 	$delete 	bool
	 * @since 	2.4.16
	 * */
	public function delete_schema( $delete, $meta_id ) {
		
		$get_meta_data 	=	get_metadata_by_mid( 'post', $meta_id );

		if ( is_object( $get_meta_data ) && ! empty( $get_meta_data->meta_key ) && strpos( $get_meta_data->meta_key, 'rank_math_schema_' ) !== false && ! empty( $get_meta_data->post_id ) ) {

			$id 		=	$get_meta_data->post_id;
			$this->delete_schema_metadata( $meta_id, 'post', $id, $get_meta_data->meta_key ); 
			
		}

		return $delete;
	}

	/**
	 * Delete schema by meta id
	 * @param 	$meta_id 		bool
	 * @param 	$object_type  	int
	 * @param 	$id  			int
	 * @param 	$meta_key  		int
	 * @since 	2.4.16
	 * */
	public function delete_schema_metadata( $meta_id, $object_type, $id, $meta_key ){
		
		$translated_key 			=	'_wpm_'.$meta_key;

		switch ( $object_type ) {

			case 'post':

				$meta_data 			=	get_post_meta( $id );
				if ( ! empty( $meta_data ) && is_array( $meta_data ) ) {
					if ( array_key_exists( $translated_key , $meta_data ) ) {
						delete_post_meta( $id, $translated_key );
					}
				}

			break;

		}

	}

	/**
	 * Translate schema data on admin panel schema tab
	 * @param 	$data 	array
	 * @return 	$data 	array
	 * @since 	2.4.16
	 * */
	public function modify_json_data( $data ){
		
		if ( is_array( $data ) && ! empty( $data['schemas'] ) && is_array( $data['schemas'] ) ) {

			if ( ! empty( $data['objectID'] ) && $data['objectType'] ) {

				$id 	=	$data['objectID'];
				$type 	=	$data['objectType'];

				foreach ( $data['schemas'] as $schema_key => $schema ) {
					
					if ( is_array( $schema ) && ! empty( $schema['@type'] ) ) {

						$translated_key 		=	'_wpm_rank_math_schema_'.$schema['@type'];
						$meta_data 				=	array();
						if ( $type == 'post' ) {
							$meta_data 			=	get_post_meta( $id );
						}
						if ( $type == 'term' ) {
							$meta_data 			=	get_term_meta( $id );
						}
						
						if ( ! empty( $meta_data ) && is_array( $meta_data ) ) {

							if ( array_key_exists( $translated_key , $meta_data ) ) {

								if ( is_array( $meta_data[$translated_key] ) && ! empty( $meta_data[$translated_key][0] ) && is_string( $meta_data[$translated_key][0] ) ) {
									$translated_schema 	=	maybe_unserialize( base64_decode( $meta_data[$translated_key][0], true) ); 
									if ( ! empty( $translated_schema ) && is_array( $translated_schema ) ) {	
										$data['schemas'][$schema_key]	=	$translated_schema;
									}		
								}

							}

						}

					}

				}
			}
		}
		
		return $data;

	}

}