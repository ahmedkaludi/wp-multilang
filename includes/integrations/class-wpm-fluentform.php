<?php
/**
 * Class for capability with Fluent Forms plugin
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPM_Fluentform
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Magazine3
 * @since 	 2.4.29
 */
class WPM_Fluentform {

	const WPM_FORM_TITLE    = 'fluentform_form_title_';
	const WPM_FORM_FIELDS   = 'fluentform_form_fields_';
	const WPM_FORM_SETTINGS = 'fluentform_form_settings_';

	/**
	 * WPM_Fluentform constructor.
	 * */
	public function __construct() {

		add_action( 'fluentform/before_updating_form', array( $this, 'store_form_data' ), 10, 2 );
		add_filter( 'fluentform/form_fields_update', array( $this, 'store_form_fields' ), 10, 2 );
		add_action( 'fluentform/after_save_form_settings', array( $this, 'store_form_settings' ), 10, 2 );

		add_filter( 'fluentform/rendering_form', array( $this, 'render_form_data' ), 9, 1 );
		add_filter( 'fluentform/editor_vars', array( $this, 'render_editor_vars' ), 10, 1 );
		add_filter( 'fluentform/form_settings_ajax', array( $this, 'render_form_settings_ajax' ), 10, 2 );
		add_filter( 'fluentform/get_meta_key_settings_response', array( $this, 'render_meta_key_settings_response' ), 10, 3 );
		add_filter( 'fluentform/form_submission_confirmation', array( $this, 'render_submission_confirmation' ), 10, 4 );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_lang_switcher_script' ), 99 );
	}

	/**
	 * Load language switcher for Fluent Forms pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @since 2.4.29
	 */
	public function add_lang_switcher_script( $hook_suffix ) {

		$fluent_pages = array(
			'fluent_forms',
			'fluent_forms_all_entries',
			'fluent_forms_reports',
			'fluent_forms_payment_entries',
			'fluent_forms_settings',
			'fluent_forms_transfer',
			'fluent_forms_smtp',
			'fluent_forms_add_ons',
			'fluent_forms_docs',
		);

		$pages = array(
			'toplevel_page_fluent_forms',
			'fluent-forms_page_fluent_forms_all_entries',
			'fluent-forms_page_fluent_forms_reports',
			'fluent-forms_page_fluent_forms_payment_entries',
			'fluent-forms_page_fluent_forms_settings',
			'fluent-forms_page_fluent_forms_transfer',
			'fluent-forms_page_fluent_forms_smtp',
			'fluent-forms_page_fluent_forms_add_ons',
			'fluent-forms_page_fluent_forms_docs',
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin screen check only.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( in_array( $hook_suffix, $pages, true ) || in_array( $page, $fluent_pages, true ) ) {
			if ( count( wpm_get_languages() ) <= 1 ) {
				return;
			}

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'wpm_language_switcher' );

			if ( ! has_action( 'admin_print_footer_scripts', 'wpm_admin_language_switcher' ) ) {
				add_action( 'admin_print_footer_scripts', 'wpm_admin_language_switcher' );
			}

			wp_register_script( 'wpm-fluentform-script', wpm_asset_path( 'scripts/wpm-fluentform' . $suffix . '.js' ), array( 'jquery', 'wp-util', 'wpm_language_switcher' ), WPM_VERSION, true );
			wp_enqueue_script( 'wpm-fluentform-script' );
		}
	}

	/**
	 * Store Fluent Forms title and fields before saving into custom tables.
	 *
	 * @param object $form Fluent Forms form object.
	 * @param array  $data Data that will be saved.
	 * @since 2.4.29
	 */
	public function store_form_data( $form, $data ) {

		$form_id = isset( $form->id ) ? absint( $form->id ) : 0;

		if ( ! $form_id || ! is_array( $data ) ) {
			return;
		}

		if ( isset( $data['title'] ) ) {
			$old_title = isset( $form->title ) ? $form->title : '';
			$this->set_value( self::WPM_FORM_TITLE . $form_id, $data['title'], $old_title );
		}

		if ( isset( $data['form_fields'] ) ) {
			$old_fields = isset( $form->form_fields ) ? $form->form_fields : '';
			$this->set_value( self::WPM_FORM_FIELDS . $form_id, $data['form_fields'], $old_fields );
		}
	}

	/**
	 * Store Fluent Forms fields before saving into custom tables.
	 *
	 * @param string $form_fields Form fields JSON.
	 * @param int    $form_id     Form ID.
	 * @return string
	 * @since 2.4.29
	 */
	public function store_form_fields( $form_fields, $form_id ) {

		$form_id = absint( $form_id );

		if ( $form_id ) {
			$this->set_value( self::WPM_FORM_FIELDS . $form_id, $form_fields, $this->get_form_column( $form_id, 'form_fields' ) );
		}

		return $form_fields;
	}

	/**
	 * Store Fluent Forms settings before saving into custom tables.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $data    Request data.
	 * @since 2.4.29
	 */
	public function store_form_settings( $form_id, $data ) {

		$form_id = absint( $form_id );

		if ( ! $form_id || ! is_array( $data ) ) {
			return;
		}

		if ( isset( $data['formSettings'] ) ) {
			$value = $data['formSettings'];
			if ( is_string( $value ) ) {
				$decoded = json_decode( $value, true );
				$value   = is_array( $decoded ) ? $decoded : $value;
			}
			$this->set_value( self::WPM_FORM_SETTINGS . $form_id, $value, $this->get_form_meta_value( $form_id, 'formSettings' ) );
		}

		if ( isset( $data['meta_key'], $data['value'] ) && 'formSettings' === $data['meta_key'] ) {
			$value = $data['value'];
			if ( is_string( $value ) ) {
				$decoded = json_decode( $value, true );
				$value   = is_array( $decoded ) ? $decoded : $value;
			}
			$this->set_value( self::WPM_FORM_SETTINGS . $form_id, $value, $this->get_form_meta_value( $form_id, 'formSettings' ) );
		}
	}

	/**
	 * Render Fluent Forms data in current language.
	 *
	 * @param object $form Fluent Forms form object.
	 * @return object
	 * @since 2.4.29
	 */
	public function render_form_data( $form ) {

		$form_id = isset( $form->id ) ? absint( $form->id ) : 0;

		if ( ! $form_id ) {
			return $form;
		}

		$title = $this->get_value( self::WPM_FORM_TITLE . $form_id, '' );
		if ( '' !== $title ) {
			$form->title = $title;
		} elseif ( isset( $form->title ) ) {
			$form->title = wpm_translate_string( $form->title );
		}

		$fields = $this->get_value( self::WPM_FORM_FIELDS . $form_id, '' );
		if ( is_string( $fields ) && '' !== $fields ) {
			$fields = json_decode( $fields, true );
		}

		if ( is_array( $fields ) ) {
			$form->fields = $fields;
		} elseif ( isset( $form->fields ) && is_array( $form->fields ) ) {
			$form->fields = wpm_translate_value( $form->fields );
		}

		$settings = $this->get_value( self::WPM_FORM_SETTINGS . $form_id, '' );
		if ( is_array( $settings ) ) {
			$form->settings = $settings;
		} elseif ( isset( $form->settings ) && is_array( $form->settings ) ) {
			$form->settings = wpm_translate_value( $form->settings );
		}

		return $form;
	}

	/**
	 * Render Fluent Forms editor vars in current language.
	 *
	 * @param array $data Editor vars.
	 * @return array
	 * @since 2.4.29
	 */
	public function render_editor_vars( $data ) {

		$form_id = isset( $data['form_id'] ) ? absint( $data['form_id'] ) : 0;

		if ( ! $form_id || empty( $data['form'] ) || ! is_object( $data['form'] ) ) {
			return $data;
		}

		$form = $this->render_form_data( $data['form'] );

		if ( isset( $form->fields ) && is_array( $form->fields ) ) {
			$form->form_fields = wp_json_encode( $form->fields );
		}

		$data['form'] = $form;

		return $data;
	}

	/**
	 * Render settings ajax response in current language.
	 *
	 * @param array $settings Settings response.
	 * @param int   $form_id  Form ID.
	 * @return array
	 * @since 2.4.29
	 */
	public function render_form_settings_ajax( $settings, $form_id ) {

		$value = $this->get_value( self::WPM_FORM_SETTINGS . absint( $form_id ), '' );

		if ( is_array( $value ) && isset( $settings['generalSettings'] ) ) {
			$settings['generalSettings'] = $value;
		} elseif ( isset( $settings['generalSettings'] ) && is_array( $settings['generalSettings'] ) ) {
			$settings['generalSettings'] = wpm_translate_value( $settings['generalSettings'] );
		}

		return $settings;
	}

	/**
	 * Render meta key response in current language.
	 *
	 * @param array  $result  Meta response.
	 * @param int    $form_id Form ID.
	 * @param string $meta_key Meta key.
	 * @return array
	 * @since 2.4.29
	 */
	public function render_meta_key_settings_response( $result, $form_id, $meta_key ) {

		if ( 'formSettings' !== $meta_key || ! is_array( $result ) ) {
			return $result;
		}

		$value = $this->get_value( self::WPM_FORM_SETTINGS . absint( $form_id ), '' );

		foreach ( $result as $item ) {
			if ( is_object( $item ) && isset( $item->value ) ) {
				$item->value = is_array( $value ) ? $value : wpm_translate_value( $item->value );
			}
		}

		return $result;
	}

	/**
	 * Render confirmation in current language.
	 *
	 * @param array  $confirmation Confirmation data.
	 * @param array  $form_data    Form data.
	 * @param object $form         Form object.
	 * @param int    $insert_id    Entry ID.
	 * @return array
	 * @since 2.4.29
	 */
	public function render_submission_confirmation( $confirmation, $form_data, $form, $insert_id = 0 ) {

		if ( is_array( $confirmation ) ) {
			$confirmation = wpm_translate_value( $confirmation );
		}

		return $confirmation;
	}

	/**
	 * Set translate value in base64.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 * @return mixed
	 * @since 2.4.29
	 */
	private function set_value( $key, $value, $old_value = '' ) {

		$current_value = get_option( "{$key}_translate", '' );

		if ( empty( $current_value ) ) {
			$current_value = $old_value ? $old_value : get_option( $key, '' );

			if ( ! empty( $current_value ) ) {
				if ( is_array( $current_value ) || is_object( $current_value ) ) {
					$current_value = maybe_serialize( $current_value );
				}
				$current_value = base64_encode( $current_value );
			}
		}

		$db_value = $value;
		if ( is_array( $db_value ) || is_object( $db_value ) ) {
			$db_value = maybe_serialize( $db_value );
		}

		update_option( $key, $db_value );
		update_option( "{$key}_translate", wpm_set_new_value( $current_value, base64_encode( $db_value ) ) );

		return $value;
	}

	/**
	 * Get value from Fluent Forms table.
	 *
	 * @param int    $form_id Form ID.
	 * @param string $column  Column name.
	 * @return string
	 * @since 2.4.29
	 */
	private function get_form_column( $form_id, $column ) {

		global $wpdb;

		if ( ! in_array( $column, array( 'title', 'form_fields' ), true ) ) {
			return '';
		}

		$table = $wpdb->prefix . 'fluentform_forms';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason Fluent Forms stores form data in a custom table.
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT {$column} FROM {$table} WHERE id = %d", $form_id ) );
	}

	/**
	 * Get value from Fluent Forms meta table.
	 *
	 * @param int    $form_id  Form ID.
	 * @param string $meta_key Meta key.
	 * @return mixed
	 * @since 2.4.29
	 */
	private function get_form_meta_value( $form_id, $meta_key ) {

		global $wpdb;

		$table = $wpdb->prefix . 'fluentform_form_meta';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason Fluent Forms stores form meta in a custom table.
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT value FROM {$table} WHERE form_id = %d AND meta_key = %s LIMIT 1", $form_id, $meta_key ) );

		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return $value;
	}

	/**
	 * Get translate value from base64.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 * @since 2.4.29
	 */
	private function get_value( $key, $default = '' ) {

		$tr_value = base64_decode( wpm_translate_value( get_option( "{$key}_translate", '' ) ), true );

		if ( ! empty( $tr_value ) && is_string( $tr_value ) ) {
			return maybe_unserialize( $tr_value );
		}

		return $default;
	}
}
