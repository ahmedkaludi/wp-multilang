<?php
/**
 * Class for capability with Yoast Seo Plugin
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class    WPM_Yoast_Seo
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Valentyn Riaboshtan
 */
class WPM_Yoast_Seo {

	/**
	 * WPM_Yoast_Seo constructor.
	 */
	public function __construct() {
		add_filter( 'wpm_option_wpseo_titles_config', array( $this, 'set_posts_config' ) );
		add_filter( 'wpseo_title', array( $this, 'translate_title' ) );
		remove_filter( 'update_post_metadata', array( 'WPSEO_Meta', 'remove_meta_if_default' ) );
		add_filter( 'wpseo_sitemap_url', array( $this, 'add_alternate_sitemaplinks' ), 10, 2 );
		add_filter( 'wpseo_sitemap_entry', array( $this, 'add_lang_to_url' ), 10, 3 );
		add_filter( 'wpseo_build_sitemap_post_type', array( $this, 'add_filter_for_maps' ) );
		add_filter( 'wpseo_opengraph_url', array( $this, 'update_opengraph_url' ) );
		if(defined('WPSEO_VERSION') && version_compare(WPSEO_VERSION, '14.0', '>=') ) {
			add_action( 'wp_after_insert_post', array($this, 'update_yoast_post_meta_tags'));
			add_action( 'saved_term', array($this, 'update_yoast_term_meta_tags'), 10, 5);
		}


		$options = \WPSEO_Options::get_option( 'wpseo_social' );

		if ( true === $options['opengraph'] ) {
			add_action( 'wpm_language_settings', array( $this, 'set_opengraph_locale' ), 10, 2 );
			add_filter( 'wpm_rest_schema_languages', array( $this, 'add_schema_to_rest' ) );
			add_filter( 'wpm_save_languages', array( $this, 'save_languages' ), 10, 2 );
			add_filter( 'wpseo_locale', array( $this, 'add_opengraph_locale' ) );
			if(defined('WPSEO_VERSION') && version_compare(WPSEO_VERSION, '14.0', '<') ) {
				add_action( 'wpseo_opengraph', array( $this, 'add_alternate_opengraph_locale' ), 40 );
			}else {
				add_filter( 'wpseo_frontend_presenters', array( $this, 'add_wpseo_frontend_presenters' ) );
			}			
		}
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
				"title-{$post_type}"              => array(),
				"metadesc-{$post_type}"           => array(),
				"metakey-{$post_type}"            => array(),
				"title-ptarchive-{$post_type}"    => array(),
				"metadesc-ptarchive-{$post_type}" => array(),
			);

			$option_config = wpm_array_merge_recursive( $option_post_config, $option_config );
		}

		$taxonomies = get_taxonomies();

		foreach ( $taxonomies as $taxonomy ) {

			if ( null === wpm_get_taxonomy_config( $taxonomy ) ) {
				continue;
			}

			$option_taxonomy_config = array(
				"title-tax-{$taxonomy}"    => array(),
				"metadesc-tax-{$taxonomy}" => array(),
			);

			$option_config = wpm_array_merge_recursive( $option_taxonomy_config, $option_config );
		}

		return $option_config;
	}

	/**
	 * Translate page title
	 *
	 * @param $title
	 *
	 * @return string
	 */
	public function translate_title( $title ) {
		$separator   = wpseo_replace_vars( '%%sep%%', array() );
		$separator   = ' ' . trim( $separator ) . ' ';
		$titles_part = explode( $separator, $title );
		$titles_part = wpm_translate_value( $titles_part );
		$title       = implode( $separator, $titles_part );

		return $title;
	}

	/**
	 * Add filter for each type
	 *
	 * @param $type
	 *
	 * @return mixed
	 */
	public function add_filter_for_maps( $type ) {
		add_filter( "wpseo_sitemap_{$type}_urlset", array( $this, 'add_namespace_to_xml' ) );
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
	 * Set locale for opengraph
	 *
	 * @since 2.0.3
	 *
	 * @param $count
	 * @param $lang
	 */
	public function set_opengraph_locale( $lang, $count ) {
		$options = get_option( 'wpm_languages', array() );
		$value   = '';

		if ( isset( $options[ $lang ]['wpseo_og_locale'] ) ) {
			$value = $options[ $lang ]['wpseo_og_locale'];
		}
		?>
		<tr>
			<td class="row-title"><?php esc_attr_e( 'Yoast SEO Opengraph Locale', 'wp-multilang' ); ?></td>
			<td>
				<input type="text" name="wpm_languages[<?php echo esc_attr( $count ); ?>][wpseo_og_locale]" value="<?php esc_attr_e( $value ); ?>" title="<?php esc_attr_e( 'Yoast SEO Opengraph Locale', 'wp-multilang' ); ?>" placeholder="<?php esc_attr_e( 'Opengraph Locale', 'wp-multilang' ); ?>">
				<p><?php esc_html_e( 'Locale must be with country domain. Like en_US', 'wp-multilang' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Add param to rest schema
	 *
	 * @since 2.0.3
	 *
	 * @param $schema
	 *
	 * @return mixed
	 */
	public function add_schema_to_rest( $schema ) {
		$schema['wpseo_og_locale'] = array( 'type' => 'string' );

		return $schema;
	}

	/**
	 * Save languages
	 *
	 * @since 2.0.3
	 *
	 * @param $languages
	 * @param $request
	 *
	 * @return mixed
	 */
	public function save_languages( $languages, $request ) {
		foreach ( $request as $value ) {
			if ( isset( $languages[ $value['code'] ], $value['wpseo_og_locale'] ) ) {
				$languages[ $value['code'] ]['wpseo_og_locale'] = $value['wpseo_og_locale'];
			}
		}

		return $languages;
	}

	/**
	 * Set locale for opengraph
	 *
	 * @since 2.0.0
	 *
	 * @param $locale
	 *
	 * @return string
	 */
	public function add_opengraph_locale( $locale ) {
		$languages     = wpm_get_languages();
		$user_language = wpm_get_language();

		if ( ! empty( $languages[ $user_language ]['wpseo_og_locale'] ) ) {
			$locale = $languages[ $user_language ]['wpseo_og_locale'];
		}

		return $locale;
	}

	/**
	 * Set alternate locale for opengraph
	 *
	 * @since 2.2.0
	 */
	public function add_alternate_opengraph_locale() {
		global $wpseo_og;

		$languages = array();

		if ( is_singular() ) {
			$languages = get_post_meta( get_the_ID(), '_languages', true );
		} elseif ( is_category() || is_tax() || is_tag() ) {
			$languages = get_term_meta( get_queried_object_id(), '_languages', true );
		}

		foreach ( wpm_get_languages() as $code => $language ) {

			if ( ( $languages && ! isset( $languages[ $code ] ) ) || $code === wpm_get_language() ) {
				continue;
			}

			if ( ! empty( $language['wpseo_og_locale'] ) && null !== $wpseo_og ) {
				$wpseo_og->og_tag( 'og:locale:alternate', $language['wpseo_og_locale'] );
			}
		}
	}

	/**
	 * Update yoast meta description field value in yoast_indexable table for posts
	 * @since 2.4.3
	 * */
	public function update_yoast_post_meta_tags()
	{
		global $wpdb;

		$update_array_values = array();

		$yoast_table_name = $wpdb->prefix . 'yoast_indexable'; 

		// Check if yoast_indexable table exists
		$query = $wpdb->prepare("SHOW TABLES LIKE %s", $yoast_table_name);
		$table_exists = $wpdb->get_var($query);

		if ($table_exists) {
			$post_id = get_the_ID();
			if($post_id){
				$table_name = $wpdb->prefix . 'postmeta';

				$meta_key_array = array('_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_opengraph-title','_yoast_wpseo_opengraph-description', '_yoast_wpseo_twitter-title', '_yoast_wpseo_twitter-description', '_yoast_wpseo_focuskw');

				// Get _yoast_wpseo_metadesc and _yoast_wpseo_title values from table
				$post_meta_result = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d AND (meta_key IN('".implode("','", $meta_key_array)."') )", $post_id ));
				if(!empty($post_meta_result) && is_array($post_meta_result) && count($post_meta_result) > 0){

					// Fetch post data from yoast_indexable table
					$result = $wpdb->get_row($wpdb->prepare("SELECT object_id, title, description FROM $yoast_table_name WHERE object_id = %d", $post_id));
					if(!empty($result) && is_object($result)){
						if(isset($result->object_id)){
							// Loop through the values
							foreach ($post_meta_result as $pmr_key => $pmr_value){
								if(!empty($pmr_value) && is_object($pmr_value)){
									if(isset($pmr_value->post_id)){
										$key_name = $pmr_value->meta_key;
										$meta_value = $pmr_value->meta_value;

										// Check if data in post meta and yoast_indexable table are not same
										if($key_name == '_yoast_wpseo_title' && $result->title !== $meta_value){
											$update_array_values['title'] = sanitize_text_field($meta_value); 
										}else if($key_name == '_yoast_wpseo_metadesc' && $result->description !== $meta_value){
											$update_array_values['description'] = sanitize_textarea_field($meta_value); 
										}else if($key_name == '_yoast_wpseo_opengraph-title' && $result->description !== $meta_value){
											$update_array_values['open_graph_title'] = sanitize_textarea_field($meta_value); 
										}else if($key_name == '_yoast_wpseo_opengraph-description' && $result->description !== $meta_value){
											$update_array_values['open_graph_description'] = sanitize_textarea_field($meta_value); 
										}else if($key_name == '_yoast_wpseo_twitter-title' && $result->description !== $meta_value){
											$update_array_values['twitter_title'] = sanitize_textarea_field($meta_value); 
										}else if($key_name == '_yoast_wpseo_twitter-description' && $result->description !== $meta_value){
											$update_array_values['twitter_description'] = sanitize_textarea_field($meta_value); 
										}else if($key_name == '_yoast_wpseo_focuskw' && $result->description !== $meta_value){
											$update_array_values['primary_focus_keyword'] = sanitize_textarea_field($meta_value); 
										}
									}
								}
							}
							
							if(!empty($update_array_values)){
								// Update the title and description field values of yoast_indexable table
								$wpdb->update($yoast_table_name, $update_array_values, array('object_id' => $post_id));
							}
						}
					} 
				} // post_meta_result if end
			}
		} // table_exists if end
	}

	/**
	 * Update yoast meta description field value in yoast_indexable table for terms
	 * @since 2.4.3
	 * */
	public function update_yoast_term_meta_tags($term_id, $tt_id, $taxonomy, $update, $args)
	{
		global $wpdb;
		$update_array_values = array();

		$yoast_table_name = $wpdb->prefix . 'yoast_indexable'; 

		// Check if yoast_indexable table exists
		$query = $wpdb->prepare("SHOW TABLES LIKE %s", $yoast_table_name);
		$table_exists = $wpdb->get_var($query);

		if ($table_exists) {
			$table_name = $wpdb->prefix . 'options';

			// Get _yoast_wpseo_metadesc and _yoast_wpseo_title values from options table
			$option_name = 'wpseo_taxonomy_meta';
			$option_result = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $table_name WHERE option_name = %s", $option_name ));

			if(is_object($option_result) && isset($option_result->option_value)){
				if(!empty($option_result->option_value) && is_string($option_result->option_value)){
					$option_result_value = unserialize($option_result->option_value);	

					if(!empty($option_result_value) && is_array($option_result_value)){
						if(isset($option_result_value[$taxonomy]) && is_array($option_result_value[$taxonomy])){
							$category_array = $option_result_value[$taxonomy];
							if(isset($category_array[$term_id]) && is_array($category_array[$term_id])){
								$term_option_details = $category_array[$term_id];

								if(isset($term_option_details['wpseo_desc'])){
									$update_array_values['description'] = sanitize_textarea_field($term_option_details['wpseo_desc']);
								}
								if(isset($term_option_details['wpseo_title'])){
									$update_array_values['title'] = sanitize_text_field($term_option_details['wpseo_title']);
								}
								if(isset($term_option_details['wpseo_opengraph-title'])){
									$update_array_values['open_graph_title'] = sanitize_text_field($term_option_details['wpseo_opengraph-title']);
								}
								if(isset($term_option_details['wpseo_opengraph-description'])){
									$update_array_values['open_graph_description'] = sanitize_text_field($term_option_details['wpseo_opengraph-description']);
								}
								if(isset($term_option_details['wpseo_twitter-title'])){
									$update_array_values['twitter_title'] = sanitize_text_field($term_option_details['wpseo_twitter-title']);
								}
								if(isset($term_option_details['wpseo_twitter-description'])){
									$update_array_values['twitter_description'] = sanitize_text_field($term_option_details['wpseo_twitter-description']);
								}
								if(isset($term_option_details['wpseo_focuskw'])){
									$update_array_values['primary_focus_keyword'] = sanitize_text_field($term_option_details['wpseo_focuskw']);
								}

								// Update the title and description field values of yoast_indexable table
								$wpdb->update($yoast_table_name, $update_array_values, array('object_id' => $term_id));
							}
						}
					}
				}
			} // option_result if end
		} // table_exists if end
	}

	/**
	 * Update yoast opengraph url 
	 * @since 2.4.4
	 * @param $url String
	 * @return $url String
	 * */
	public function update_opengraph_url($url)
	{
		if(!is_admin()){
			$url = get_permalink();
		}
		return $url;
	}

	/**
	 * Adds opengraph support for translations
	 *
	 * @since 2.4.5
	 * 
	 * @param array $presenters An array of objects implementing Abstract_Indexable_Presenter
	 * @return array
	 */
	public function add_wpseo_frontend_presenters( $presenters ) {
		$_presenters = array();

		foreach ( $presenters as $presenter ) {
			$_presenters[] = $presenter;

			if ( get_class($presenter) == 'Yoast\WP\SEO\Presenters\Open_Graph\Locale_Presenter' ) {
				error_log(print_r($presenter, 1));

				foreach ( $this->get_ogp_alternate_languages() as $lang ) {
					$_presenters[] = new WPM_Yoast_Seo_Presenters( $lang );
				}
			}
		}

		return $_presenters;
	}

	/**
	 * Get alternate language codes for Opengraph
	 *
	 * @since 2.4.5
	 *
	 * @return array
	 */
	protected function get_ogp_alternate_languages() {
		$alternates = array();

		foreach ( wpm_get_languages() as $code => $language ) {
			if ( $code !== wpm_get_language() && ! empty( $language['locale'] ) ) {
				$alternates[] = $language['locale'];
			}
		}

		// There is a risk that 2 languages have the same Facebook locale. So let's make sure to output each locale only once.
		return array_unique( $alternates );
	}
}
