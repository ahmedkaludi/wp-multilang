<?php
/**
 * Class for capability with Complianz GDPR
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class    WPM_Complianz
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @since    2.4.35
 */
class WPM_Complianz {

	/**
	 * Translatable keys in cookie banner settings
	 *
	 * @var array
	 */
	private $banner_translatable_keys = array(
		'header',
		'message_optin',
		'message_optout',
		'accept',
		'dismiss',
		'revoke',
		'settings',
		'category_functional',
		'category_prefs',
		'category_stats',
		'category_all',
		'view_preferences',
		'save_preferences',
		'functional_text',
		'statistics_text',
		'statistics_text_anonymous',
		'preferences_text',
		'marketing_text',
		'readmore_optin',
		'readmore_optout',
		'readmore_impressum',
		'tagmanager_categories',
	);

	/**
	 * WPM_Complianz constructor.
	 */
	public function __construct() {
		// Filter Complianz strings through WPML translation filter hook
		add_filter( 'wpml_translate_single_string', array( $this, 'translate_string' ), 10, 3 );

		// Cookie banner output settings translation (both frontend script localization and HTML rendering)
		add_filter( 'cmplz_cookiebanner_settings', array( $this, 'filter_cookiebanner_settings' ), 10, 2 );
		add_filter( 'cmplz_cookiebanner_settings_html', array( $this, 'filter_cookiebanner_settings' ), 10, 2 );
		add_filter( 'cmplz_cookiebanner_settings_front_end', array( $this, 'filter_cookiebanner_settings' ), 10, 2 );

		// Cookie banner HTML and revoke elements
		add_filter( 'cmplz_banner_html', 'wpm_translate_string' );
		add_filter( 'cmplz_revoke_link', 'wpm_translate_string' );
		add_filter( 'cmplz_revoke_button_text', array( $this, 'translate_string' ) );

		// Legal document HTML translation & internal URLs localization
		add_filter( 'cmplz_document_html', array( $this, 'filter_document_html' ), 10, 3 );

		// Options configuration dynamically & option value translation
		add_filter( 'wpm_option_cmplz_options_config', array( $this, 'set_options_config' ) );

		// Admin pages integration
		add_filter( 'wpm_admin_pages', array( $this, 'add_admin_pages' ) );

		// Admin REST actions: translate banner preview data and handle multilingual banner saves
		add_filter( 'cmplz_do_action', array( $this, 'filter_update_banner_data' ), 5, 3 );
		add_filter( 'cmplz_do_action', array( $this, 'filter_get_banner_data' ), 20, 3 );

		// Admin script and styling for language switcher on Complianz pages
		add_action( 'admin_enqueue_scripts', array( $this, 'add_lang_switcher_assets' ), 99 );
	}

	/**
	 * Translate single string from Complianz (supports WPM multi-language tags and gettext fallback)
	 *
	 * @param mixed  $string
	 * @param string $context
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function translate_string( $string, $context = '', $name = '' ) {
		if ( ! is_string( $string ) || empty( $string ) ) {
			return $string;
		}

		if ( wpm_is_ml_string( $string ) ) {
			return wpm_translate_string( $string );
		}

		// If string originates from Complianz and is untranslated, attempt textdomain translation for current locale
		if ( 'complianz' === $context || strpos( (string) $name, 'cmplz_' ) === 0 || strpos( (string) $name, 'complianz' ) === 0 ) {
			$translated = __( $string, 'complianz-gdpr' );
			if ( $translated === $string ) {
				$translated = __( $string, 'complianz-gdpr-premium' );
			}
			return $translated;
		}

		return $string;
	}

	/**
	 * Filter cookie banner settings to translate all banner text and URLs
	 *
	 * @param array $settings
	 * @param mixed $banner
	 *
	 * @return array
	 */
	public function filter_cookiebanner_settings( $settings, $banner = null ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		foreach ( $settings as $key => $item ) {
			if ( is_string( $item ) ) {
				$settings[ $key ] = $this->translate_string( $item, 'complianz', $key );
			} elseif ( is_array( $item ) && 'page_links' !== $key ) {
				if ( isset( $item['text'] ) && is_string( $item['text'] ) ) {
					$settings[ $key ]['text'] = $this->translate_string( $item['text'], 'complianz', $key );
				}
				if ( isset( $item['title'] ) && is_string( $item['title'] ) ) {
					$settings[ $key ]['title'] = $this->translate_string( $item['title'], 'complianz', $key );
				}
			}
		}

		// Translate page links URLs and titles
		if ( isset( $settings['page_links'] ) && is_array( $settings['page_links'] ) ) {
			$settings['page_links'] = $this->translate_page_links( $settings['page_links'] );
		}

		// Deep translation for any remaining multilingual values
		return wpm_translate_value( $settings );
	}

	/**
	 * Translate page links array recursively
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	private function translate_page_links( $links ) {
		foreach ( $links as $key => $item ) {
			if ( is_array( $item ) ) {
				if ( isset( $item['url'] ) || isset( $item['title'] ) ) {
					if ( isset( $item['url'] ) && is_string( $item['url'] ) ) {
						$links[ $key ]['url'] = wpm_translate_url( $item['url'] );
					}
					if ( isset( $item['title'] ) && is_string( $item['title'] ) ) {
						$links[ $key ]['title'] = $this->translate_string( $item['title'], 'complianz', 'page_title' );
					}
				} else {
					$links[ $key ] = $this->translate_page_links( $item );
				}
			}
		}

		return $links;
	}

	/**
	 * Filter legal document HTML: translates ML strings and localizes internal URLs
	 *
	 * @param string $html
	 * @param string $type
	 * @param int    $post_id
	 *
	 * @return string
	 */
	public function filter_document_html( $html, $type = '', $post_id = 0 ) {
		if ( ! is_string( $html ) || empty( $html ) ) {
			return $html;
		}

		$html = wpm_translate_string( $html );
		$html = $this->translate_urls_in_html( $html );

		return $html;
	}

	/**
	 * Translate internal site URLs inside HTML
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function translate_urls_in_html( $html ) {
		if ( ! is_string( $html ) || empty( $html ) ) {
			return $html;
		}

		$home_url = wpm_get_orig_home_url();
		if ( false === strpos( $html, $home_url ) ) {
			return $html;
		}

		$pattern = '/href=([\'"])(' . preg_quote( $home_url, '/' ) . '[^\'"\s>]*)\1/i';

		return preg_replace_callback( $pattern, function( $matches ) {
			$translated_url = wpm_translate_url( $matches[2] );
			return 'href=' . $matches[1] . esc_url( $translated_url ) . $matches[1];
		}, $html );
	}

	/**
	 * Set configuration for cmplz_options
	 *
	 * @param array $option_config
	 *
	 * @return array
	 */
	public function set_options_config( $option_config ) {
		$cmplz_fields = array(
			'organisation_name'             => array(),
			'address_company'               => array(),
			'country_company'               => array(),
			'email_company'                 => array(),
			'telephone_company'             => array(),
			'ca_name_accountable_person'    => array(),
			'ca_address_accountable_person' => array(),
			'contact_retain_wmy'            => array(),
			'statistics_retain_wmy'         => array(),
			'privacy-statement-title'       => array(),
			'cookie-statement-title'        => array(),
			'disclaimer-title'              => array(),
			'impressum-title'               => array(),
			'purpose_personaldata_other'    => array(),
			'data_protection_officer'       => array(),
			'thirdparties'                  => array(),
			'processors'                    => array(),
		);
		$option_config = (array) $option_config;
		return wpm_array_merge_recursive( $cmplz_fields, $option_config );
	}

	/**
	 * Add Complianz admin pages to WPM admin pages
	 *
	 * @param array $admin_pages
	 *
	 * @return array
	 */
	public function add_admin_pages( $admin_pages ) {
		$complianz_pages = array(
			'toplevel_page_complianz',
			'complianz_page_cmplz-wizard',
			'complianz_page_cmplz-cookiebanner',
			'complianz_page_cmplz-settings',
			'complianz_page_cmplz-documents',
			'complianz_page_cmplz-integrations',
			'complianz_page_cmplz-proof-of-consent',
			'complianz_page_cmplz-records-of-consent',
			'complianz_page_cmplz-dashboard',
		);

		return array_unique( array_merge( (array) $admin_pages, $complianz_pages ) );
	}

	/**
	 * Detect active language for REST request or admin page
	 *
	 * @param WP_REST_Request|null $request
	 *
	 * @return string
	 */
	private function get_request_language( $request = null ) {
		$languages = wpm_get_languages();

		if ( $request && method_exists( $request, 'get_param' ) ) {
			$lang = $request->get_param( 'edit_lang' );
			if ( $lang && isset( $languages[ $lang ] ) ) {
				return $lang;
			}
		}

		$referrer = wp_get_raw_referer();
		if ( $referrer ) {
			$query_str = wp_parse_url( $referrer, PHP_URL_QUERY );
			if ( is_string( $query_str ) ) {
				parse_str( $query_str, $query_params );
				if ( isset( $query_params['edit_lang'], $languages[ $query_params['edit_lang'] ] ) ) {
					return $query_params['edit_lang'];
				}
			}
		}

		$user_edit_lang = get_user_meta( get_current_user_id(), 'edit_lang', true );
		if ( $user_edit_lang && isset( $languages[ $user_edit_lang ] ) ) {
			return $user_edit_lang;
		}

		return wpm_get_language();
	}

	/**
	 * Translate banner data for Complianz React admin preview
	 *
	 * @param array           $data
	 * @param string          $action
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function filter_get_banner_data( $data, $action, $request = null ) {
		if ( 'get_banner_data' !== $action || empty( $data['banners'] ) ) {
			return $data;
		}

		$lang = $this->get_request_language( $request );

		foreach ( $data['banners'] as &$banner ) {
			foreach ( $this->banner_translatable_keys as $key ) {
				if ( isset( $banner->{$key} ) ) {
					if ( is_array( $banner->{$key} ) ) {
						if ( isset( $banner->{$key}['text'] ) ) {
							$banner->{$key}['text'] = wpm_translate_string( $banner->{$key}['text'], $lang );
							if ( isset( $banner->{$key . '_x'} ) && is_array( $banner->{$key . '_x'} ) ) {
								$banner->{$key . '_x'}['text'] = $banner->{$key}['text'];
							}
						}
					} elseif ( is_string( $banner->{$key} ) ) {
						$banner->{$key} = wpm_translate_string( $banner->{$key}, $lang );
						$banner->{$key . '_x'} = $banner->{$key};
					}
				}
			}
		}

		if ( isset( $data['banner_html'] ) && is_string( $data['banner_html'] ) ) {
			$data['banner_html'] = wpm_translate_string( $data['banner_html'], $lang );
		}

		if ( isset( $data['manage_consent_html'] ) && is_string( $data['manage_consent_html'] ) ) {
			$data['manage_consent_html'] = wpm_translate_string( $data['manage_consent_html'], $lang );
		}

		if ( isset( $data['page_links'] ) && is_array( $data['page_links'] ) ) {
			$data['page_links'] = $this->translate_page_links( $data['page_links'] );
		}

		return $data;
	}

	/**
	 * Intercept banner update to merge edits into multilingual strings in the database
	 *
	 * @param array           $data
	 * @param string          $action
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function filter_update_banner_data( $data, $action, $request = null ) {
		if ( 'update_banner_data' !== $action || ! $request || ! method_exists( $request, 'get_json_params' ) ) {
			return $data;
		}

		$params    = $request->get_json_params();
		$fields    = $params['fields'] ?? array();
		$banner_id = (int) ( $params['banner_id'] ?? 0 );

		if ( empty( $fields ) || $banner_id <= 0 ) {
			return $data;
		}

		global $wpdb;
		$raw_banner = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cmplz_cookiebanners WHERE ID = %d", $banner_id ), ARRAY_A );
		if ( ! $raw_banner ) {
			return $data;
		}

		$lang    = $this->get_request_language( $request );
		$changed = false;

		foreach ( $fields as $index => $field ) {
			$field_id = $field['id'] ?? '';
			if ( ! in_array( $field_id, $this->banner_translatable_keys, true ) ) {
				continue;
			}

			$old_raw = $raw_banner[ $field_id ] ?? '';
			$val     = $field['value'] ?? '';

			if ( is_array( $val ) && isset( $val['text'] ) ) {
				$old_text = $old_raw;
				if ( is_serialized( $old_raw ) ) {
					$unserialized = @unserialize( $old_raw, array( 'allowed_classes' => false ) );
					if ( is_array( $unserialized ) && isset( $unserialized['text'] ) ) {
						$old_text = $unserialized['text'];
					}
				}
				$ml = wpm_is_ml_string( $old_text ) ? wpm_string_to_ml_array( $old_text ) : array_fill_keys( array_keys( wpm_get_languages() ), $old_text );
				$ml[ $lang ] = $val['text'];
				$fields[ $index ]['value']['text'] = wpm_ml_array_to_string( $ml );
				$changed = true;
			} elseif ( is_string( $val ) ) {
				$ml = wpm_is_ml_string( $old_raw ) ? wpm_string_to_ml_array( $old_raw ) : array_fill_keys( array_keys( wpm_get_languages() ), $old_raw );
				$ml[ $lang ] = $val;
				$fields[ $index ]['value'] = wpm_ml_array_to_string( $ml );
				$changed = true;
			}
		}

		if ( $changed ) {
			$params['fields'] = $fields;
			$request->set_body( json_encode( $params ) );
		}

		return $data;
	}

	/**
	 * Load scripts and styles for Complianz plugin admin pages
	 *
	 * @param string $hook_suffix
	 * @since 2.4.35
	 */
	public function add_lang_switcher_assets( $hook_suffix ) {
		$complianz_pages = array(
			'toplevel_page_complianz',
			'complianz_page_cmplz-wizard',
			'complianz_page_cmplz-cookiebanner',
			'complianz_page_cmplz-settings',
			'complianz_page_cmplz-documents',
			'complianz_page_cmplz-integrations',
			'complianz_page_cmplz-proof-of-consent',
			'complianz_page_cmplz-records-of-consent',
			'complianz_page_cmplz-dashboard',
		);

		$is_complianz = in_array( $hook_suffix, $complianz_pages, true );
		if ( ! $is_complianz && isset( $_GET['page'] ) && ( 'complianz' === $_GET['page'] || 0 === strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'cmplz' ) ) ) {
			$is_complianz = true;
		}

		if ( $is_complianz ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'wpm-complianz-script', wpm_asset_path( 'scripts/wpm-complianz' . $suffix . '.js' ), array( 'jquery', 'wp-util' ), WPM_VERSION, true );
			wp_enqueue_script( 'wpm-complianz-script' );
		}
	}
}

