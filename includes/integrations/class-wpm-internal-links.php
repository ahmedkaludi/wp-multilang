<?php
/**
 * Class for capability with internal links plugin
 */

namespace WPM\Includes\Integrations;
use ILJ\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPM_Internal_Links
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Magazine3
 * @since 	 2.4.26
 */
class WPM_Internal_Links {

	const WPM_LINK_DEFINITATION 			=	'ilj_linkdefinition';

	private $object_id = 0;

	public function __construct() {

		$meta_keys = array(
			self::WPM_LINK_DEFINITATION => array(
				'set_form_meta_value',
				'get_form_meta_value'
			),
		);

		//Install meta Filters
		foreach ($meta_keys as $meta_key => $callbacks) {

			add_filter( "wpm_{$meta_key}_meta_config", 			array($this, 'config'), 10, 3 );
			add_filter( "wpm_add_{$meta_key}_meta_value", 		array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_update_{$meta_key}_meta_value", 	array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_get_{$meta_key}_meta_value", 		array($this, $callbacks[1]), 10, 1 );
		}


		add_filter( 'the_content', [ $this, 'replace_links' ], 999 );
	}

	/**
	 * Config meta keys
	 *
	 * @param $config
	 * @param $meta_value
	 * @param $object_id
	 * @return mixed
	 * @since 2.4.26
	 */
	public function config( $config, $meta_value, $object_id ) {

		$this->object_id = $object_id;

		return $config;
	}

	/**
	 * Set meta translate in base64
	 *
	 * @param $key
	 * @param $value
	 * @return mixed
	 * @since 2.4.26
	 */
	private function set_value( $key, $value ) {

		global $wpdb;

		if ( ! $this->object_id ) {
			return $value;
		}

		$current_value = get_post_meta( $this->object_id, "{$key}_translate", true );

		// If translate data is not present then get default value
		if ( empty( $current_value ) ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason Using built function doesn't work in our case, so added manual query
			$current_value 	=	$wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id = %d", $key, $this->object_id ) );
			
			if ( ! empty( $current_value ) ) {

				$current_value 	=	base64_encode( $current_value );
				
			}
				
		}

		$db_value 	=	$value;
		if ( is_array( $db_value ) ) {
			$db_value 	=	maybe_serialize( $db_value );
		}

		update_post_meta( $this->object_id, "{$key}_translate", wpm_set_new_value( $current_value, base64_encode( $db_value ) ) );

		$this->object_id = 0;

		return $value;
	}

	/**
	 * Get meta translate from base64
	 *
	 * @param $key
	 * @param $value
	 * @return false|string
	 * @since 2.4.26
	 */
	private function get_value($key, $value) {

		if ( ! $this->object_id ) {
			return $value;
		}

		$tr_value = base64_decode( wpm_translate_value( get_post_meta( $this->object_id, "{$key}_translate", true ) ), true );
		
		$this->object_id = 0;

		if ( ! empty( $tr_value ) && is_string( $tr_value ) ) {
			return maybe_unserialize( $tr_value );
		}else{
			return $value;
		}

	}

	/**
	 * Set meta value data
	 *
	 * @param $value
	 * @return mixed
	 * @since 2.4.26
	 */
	public function set_form_meta_value( $value ) {

		$key = self::WPM_LINK_DEFINITATION;

		return $this->set_value( $key, $value );
	}

		/**
	 * Get meta value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.26
	 */
	public function get_form_meta_value( $value ) {

		$key = self::WPM_LINK_DEFINITATION;

		return $this->get_value( $key, $value );
	}


	/**
	 * Replace link index with tag
	 * @param 	$content 	post content
	 * @return 	$content
	 * @since 	2.4.27
	 * */
	public function replace_links( $content ) {

		global $post;

		$post_id 				=	0;
		if ( ! empty( $post ) && is_object( $post ) ) {
			$post_id 			=	isset( $post->ID ) ? $post->ID : 0; 
		}

		if ( $post_id === 0 ) {
			return $content;
		}

		$get_links 				=	$this->get_interlink_rules( $post_id );
		
		if ( ! empty( $get_links ) ) {

			foreach ( $get_links as $link ) {
				if ( ! empty( $link ) && is_object( $link ) ) {

					$translate_key 			=	self::WPM_LINK_DEFINITATION.'_translate';	
					$keywords				=	get_post_meta( $link->link_to , $translate_key, true );
					if ( ! empty( $keywords ) && is_string( $keywords ) ) {
						$translated_keyword	=	wpm_translate_string( $keywords,  wpm_get_language() );
						
						if ( ! empty( $translated_keyword ) ) {
							$translated_keyword 	=	maybe_unserialize( base64_decode( $translated_keyword, true ) );
							if ( is_array( $translated_keyword ) ) {
								foreach ( $translated_keyword as $keyword ) {

									if ( empty( $keyword ) ) {
								        continue;
								    }

									$rule 				=	new \stdClass();
									$rule->pattern 		=	$keyword;
									$rule->value 		=	$link->link_to;
									$rule->type 		=	'post';
									$generate_link 		=	$this->generate_link( $rule, $keyword );
									
									$content 			=	str_replace( $keyword, $generate_link, $content );
								}
								
							}
						}
					}
	
				}
			}
			
		}

		return $content;
	}

	/**
	 * Get internal links of the post
	 * @param 	$id 	int
	 * @param 	$type 	string
	 * @since 	2.4.27
	 * Reference class: ILJ\Database\Linkindex Function: getRules()
	 * */
	public function get_interlink_rules( $id, $type = 'post' ) {
		
		if (!is_numeric($id)) {
			return array();
		}

		$table_name 	=	'ilj_linkindex';

		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Already prepared
		$query = $wpdb->prepare('SELECT * FROM ' . $wpdb->prefix . $table_name . ' linkindex WHERE linkindex.link_from = %d AND linkindex.type_from = %s', $id, $type);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is necessary, caching is not applicable for real-time data.
		return $wpdb->get_results($query);

	}

	/**
	 * Get link template
	 * @since 	2.4.27
	 * Reference class: ILJ\Core\LinkBuilder Function: getLinkTemplate()
	 * */
	private function get_link_template() {
		$default_template = \ILJ\Core\Options\LinkOutputInternal::getDefault();
		$template         = Options::getOption(\ILJ\Core\Options\LinkOutputInternal::getKey());

		if ('' == $template) {
			return $default_template;
		}
		return wp_specialchars_decode($template, \ENT_QUOTES);
	}

	/**
	 * Generate Link based on template
	 * @param 	$link_rule 	object
	 * @param 	$anchor 	text
	 * @return 	$link 		html element
	 * @since 	2.4.27
	 * Reference class: ILJ\Core\LinkBuilder Function: generateLink()
	 * */
	private function generate_link($link_rule, $anchor) {
		$template = $this->get_link_template();
		$nofollow = (bool) Options::getOption(\ILJ\Core\Options\InternalNofollow::getKey());
		$link_attrs = array();

		if ('post' == $link_rule->type) {
			if (get_post_status($link_rule->value) != 'publish') {
				return false;
			}
			$url = get_the_permalink($link_rule->value);
		}

		$link = str_replace('{{url}}', (isset($url) ? $url : '#'), $template);
		$link = str_replace('{{anchor}}', $anchor, $link);

		$check_nofollow = true;

		if ($check_nofollow && $nofollow) {
			$link = str_replace('<a ', '<a rel="nofollow" ', $link);
		}

		// if ('json' === $this->content_type) {
		// 	// If the content is json, the link should be escaped before replacement.
		// 	$link = trim(wp_json_encode($link), '"');
		// }

		return $link;
	}

}