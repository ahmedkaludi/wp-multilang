<?php
/**
 * Class for capability with WPBakery Visual Composer
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPM_VC
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Valentyn Riaboshtan
 */
class WPM_VC {

	const WPM_WPB_POST_CUSTOM_SEO_SETTINGS 			=	'_wpb_post_custom_seo_settings';

	private $object_id = 0;

	/**
	 * WPM_VC constructor.
	 */
	public function __construct() {
		add_action( 'vc_frontend_editor_render', array( $this, 'enqueue_js_frontend' ) );
		add_filter( 'vc_frontend_editor_iframe_url', array( $this, 'append_lang_to_url' ) );
		add_filter( 'vc_nav_front_controls', array( $this, 'nav_controls_frontend' ) );
		if ( function_exists( 'vc_is_frontend_editor' ) && ! vc_is_frontend_editor() ) {
			add_filter( 'vc_get_inline_url', array( $this, 'render_edit_button_link' ) );
		}
		
		$meta_keys = array(
			self::WPM_WPB_POST_CUSTOM_SEO_SETTINGS => array(
				'set_wpb_post_seo_value',
				'get_wpb_post_seo_value'
			),
		);

		//Install meta Filters
		foreach ($meta_keys as $meta_key => $callbacks) {

			add_filter( "wpm_{$meta_key}_meta_config", 			array($this, 'config'), 10, 3 );
			add_filter( "wpm_add_{$meta_key}_meta_value", 		array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_update_{$meta_key}_meta_value", 	array($this, $callbacks[0]), 10, 1 );
			add_filter( "wpm_get_{$meta_key}_meta_value", 		array($this, $callbacks[1]), 10, 1 );
		}
	}


	/**
	 * Add lang param to url
	 *
	 * @param $link
	 *
	 * @return string
	 */
	public function append_lang_to_url( $link ) {
		return add_query_arg( 'lang', wpm_get_language(), $link );
	}

	public function enqueue_js_frontend() {
		wpm_enqueue_js( "
			$( '#vc_vendor_wpm_langs_front' ).change( function () {
				vc.closeActivePanel();
				$( '#vc_logo' ).addClass( 'vc_ui-wp-spinner' );
				window.location.href = $( this ).val();
			} );
			
			var nativeGetContent = vc.ShortcodesBuilder.prototype.getContent;
			vc.ShortcodesBuilder.prototype.getContent = function () {
				var content = nativeGetContent();
				$( '#content' ).val( content );
				return content;
			};
		" );
	}

	/**
	 * Generate language switcher
	 *
	 * @return string
	 */
	public function generate_select_frontend() {
		$output          = '';
		$output          .= '<select id="vc_vendor_wpm_langs_front" class="vc_select vc_select-navbar">';
		$inline_url      = vc_frontend_editor()->getInlineUrl();
		$active_language = wpm_get_language();
		$languages       = wpm_get_languages();
		foreach ( $languages as $code => $language ) {
			$output .= '<option value="' . add_query_arg( 'edit_lang', $code, $inline_url ) . '" ' . selected( $code, $active_language, false ) . ' >' . esc_attr( $language['name'] ) . '</option >';
		}
		$output .= '</select >';

		return $output;
	}

	/**
	 * Add menu item
	 *
	 * @param $list
	 *
	 * @return array
	 */
	public function nav_controls_frontend( $list ) {
		if ( is_array( $list ) ) {
			$list[] = array( 'wpm', '<li class="vc_pull-right" > ' . $this->generate_select_frontend() . '</li > ' );
		}

		return $list;
	}

	/**
	 * Generate edit link
	 *
	 * @param $link
	 *
	 * @return string
	 */
	public function render_edit_button_link( $link ) {
		return add_query_arg( 'edit_lang', wpm_get_language(), $link );
	}

	/**
	 * Config meta keys
	 *
	 * @param $config
	 * @param $meta_value
	 * @param $object_id
	 * @return mixed
	 * @since 2.4.15
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
	 * @since 2.4.15
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
	 * @since 2.4.15
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
	 * @since 2.4.15
	 */
	public function set_wpb_post_seo_value( $value ) {

		$key = self::WPM_WPB_POST_CUSTOM_SEO_SETTINGS;

		return $this->set_value( $key, $value );
	}

		/**
	 * Get meta value data
	 *
	 * @param $value
	 * @return false|string
	 * @since 2.4.15
	 */
	public function get_wpb_post_seo_value( $value ) {

		$key = self::WPM_WPB_POST_CUSTOM_SEO_SETTINGS;

		return $this->get_value( $key, $value );
	}
}
