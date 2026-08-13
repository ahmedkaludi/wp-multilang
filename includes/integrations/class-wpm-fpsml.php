<?php
/**
 * Class for compatibility with Frontend Post Submission Manager Lite
 *
 * Stores all non-English translations in a separate WP option (`wpm_fpsml_translations`)
 * so the custom `wp_fpsm_forms` table remains 100% clean with no multilingual delimiters.
 * If WP-Multilang is deactivated, the plugin works normally using only the DB data.
 * @since 	2.4.33
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class    WPM_Fpsml
 * @package  WPM/Includes/Integrations
 * @category Integrations
 */
class WPM_Fpsml {

	/**
	 * Temp restores data
	 */
	private $temp_restores = array();

	/**
	 * WPM_Fpsml constructor.
	 */
	public function __construct() {
		if ( ! defined( 'FPSML_PATH' ) ) {
			return;
		}

		// 1. Intercept admin form saves for non-default languages (before FPSML at priority 10).
		add_action( 'wp_ajax_fpsml_form_edit_action', array( $this, 'intercept_admin_form_save' ), 1 );

		// 2. Before admin page renders, temporarily load translations into DB, and restore after.
		add_action( 'toplevel_page_fpsm', array( $this, 'maybe_inject_translations_for_admin_render' ), 5 );
		add_action( 'toplevel_page_fpsm', array( $this, 'maybe_restore_translations_after_admin_render' ), 15 );

		// 3. Override frontend shortcode to inject translations on-the-fly.
		add_action( 'init', array( $this, 'override_shortcode' ), 20 );

	}

	// =========================================================================
	// Admin Page Render - Temporary Translation Injection
	// =========================================================================

	/**
	 * Detect if we are on the FPSM forms page in a non-default language.
	 * If so, temporarily write the saved translations to the DB row so the list
	 * and form editor load translated values.
	 */
	public function maybe_inject_translations_for_admin_render() {
		$lang             = wpm_get_language();
		$default_language = wpm_get_default_language();

		if ( $lang === $default_language ) {
			return;
		}

		$translations = get_option( 'wpm_fpsml_translations', array() );
		if ( empty( $translations ) ) {
			return;
		}

		global $wpdb;
		$form_table = $wpdb->prefix . 'fpsm_forms';
		$form_rows  = $wpdb->get_results( "SELECT * FROM {$form_table}" );

		if ( ! empty( $form_rows ) ) {
			foreach ( $form_rows as $row ) {
				if ( isset( $translations[ $row->form_id ][ $lang ] ) ) {
					$t = $translations[ $row->form_id ][ $lang ];

					// Keep original values for restore.
					$this->temp_restores[] = array(
						'form_id'      => $row->form_id,
						'form_title'   => $row->form_title,
						'form_details' => $row->form_details,
					);

					// Merge translated text fields only.
					$details_array = maybe_unserialize( $row->form_details );
					$merged        = isset( $t['form_details'] ) && is_array( $t['form_details'] )
						? $this->merge_translatable_fields( $details_array, $t['form_details'] )
						: $details_array;

					// Temporarily write translations to DB.
					$wpdb->update(
						$form_table,
						array(
							'form_title'   => isset( $t['form_title'] ) ? $t['form_title'] : $row->form_title,
							'form_details' => maybe_serialize( $merged ),
						),
						array( 'form_id' => $row->form_id )
					);
				}
			}
		}
	}

	/**
	 * Restore original English values back to the DB after the page callback renders.
	 */
	public function maybe_restore_translations_after_admin_render() {
		if ( ! empty( $this->temp_restores ) ) {
			global $wpdb;
			$form_table = $wpdb->prefix . 'fpsm_forms';
			foreach ( $this->temp_restores as $restore ) {
				$wpdb->update(
					$form_table,
					array(
						'form_title'   => $restore['form_title'],
						'form_details' => $restore['form_details'],
					),
					array( 'form_id' => $restore['form_id'] )
				);
			}
			$this->temp_restores = array();
		}
	}

	// =========================================================================
	// Admin Form Save Interception
	// =========================================================================

	/**
	 * Intercept the FPSML admin AJAX form save for non-default languages.
	 *
	 * For the default language: return early - let the original FPSML handler
	 * write everything to the DB as normal.
	 *
	 * For non-default languages: save only the translated text content into the
	 * `wpm_fpsml_translations` option. Update only non-translatable DB columns
	 * (alias, status) so the DB table row stays clean in default-language values.
	 */
	public function intercept_admin_form_save() {
		if ( empty( $_POST['form_data'] ) ) {
			return;
		}

		$form_data = array();
		parse_str( wp_unslash( $_POST['form_data'] ), $form_data );

		// Read the language from the hidden `edit_lang` input WP-Multilang injects.
		$lang             = isset( $form_data['edit_lang'] ) ? sanitize_text_field( $form_data['edit_lang'] ) : wpm_get_language();
		$default_language = wpm_get_default_language();
		$languages        = wpm_get_languages();

		// Bail if the language code is not registered in WP-Multilang.
		if ( ! isset( $languages[ $lang ] ) ) {
			return;
		}

		// Default language: let original FPSML handler run.
		if ( $lang === $default_language ) {
			return;
		}

		// --- Non-default language save ---
		$form_id     = intval( $form_data['form_id'] ?? 0 );
		$form_title  = sanitize_text_field( $form_data['form_title'] ?? '' );
		$form_alias  = sanitize_key( $form_data['form_alias'] ?? '' );
		$form_status = ! empty( $form_data['form_status'] ) ? 1 : 0;

		if ( ! $form_id ) {
			return;
		}

		// Persist all form_details (contains all translatable text fields).
		$all_translations                      = get_option( 'wpm_fpsml_translations', array() );
		$all_translations[ $form_id ][ $lang ] = array(
			'form_title'   => $form_title,
			'form_details' => $form_data['form_details'] ?? array(),
		);
		update_option( 'wpm_fpsml_translations', $all_translations );

		// Update only non-translatable structural columns in DB.
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'fpsm_forms',
			array(
				'form_alias'  => $form_alias,
				'form_status' => $form_status,
			),
			array( 'form_id' => $form_id )
		);

		// Return success response and stop further processing.
		die( wp_json_encode( array(
			'status'  => 200,
			'message' => esc_html__( 'Form updated successfully.', 'frontend-post-submission-manager-lite' ),
		) ) );
	}

	// =========================================================================
	// Frontend Shortcode - Translation Injection
	// =========================================================================

	/**
	 * Replace the default `[fpsm]` shortcode with our translation-aware wrapper.
	 */
	public function override_shortcode() {
		remove_shortcode( 'fpsm' );
		add_shortcode( 'fpsm', array( $this, 'fpsm_shortcode_with_translations' ) );
	}

	/**
	 * Render the `[fpsm]` shortcode, merging translated text values into the
	 * form_details before passing them to the FPSML view layer.
	 *
	 * @param  array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function fpsm_shortcode_with_translations( $atts ) {
		if ( empty( $atts['alias'] ) ) {
			return '';
		}

		global $fpsml_library_obj;

		if ( ! isset( $fpsml_library_obj ) || ! is_object( $fpsml_library_obj ) ) {
			return '';
		}

		$alias    = sanitize_text_field( $atts['alias'] );
		$form_row = $fpsml_library_obj->get_form_row_by_alias( $alias );

		if ( empty( $form_row ) ) {
			return esc_html__( 'Form not available for this alias.', 'frontend-post-submission-manager-lite' );
		}

		$lang             = wpm_get_language();
		$default_language = wpm_get_default_language();

		// Merge translations for non-default languages.
		if ( $lang !== $default_language ) {
			$all_translations = get_option( 'wpm_fpsml_translations', array() );
			if ( isset( $all_translations[ $form_row->form_id ][ $lang ] ) ) {
				$t       = $all_translations[ $form_row->form_id ][ $lang ];
				$details = maybe_unserialize( $form_row->form_details );

				if ( ! empty( $t['form_title'] ) ) {
					$form_row->form_title = $t['form_title'];
				}

				if ( isset( $t['form_details'] ) && is_array( $t['form_details'] ) ) {
					$details                = $this->merge_translatable_fields( $details, $t['form_details'] );
					$form_row->form_details = maybe_serialize( $details );
				}
			}
		}

		$form_details                  = maybe_unserialize( $form_row->form_details );
		$GLOBALS['fpsml_form_details'] = $form_details;
		$GLOBALS['fpsml_form_alias']   = $alias;

		// Enqueue assets using the existing FPSML_Shortcode instance.
		global $fpsml_shortcode_obj;
		if ( isset( $fpsml_shortcode_obj ) && is_object( $fpsml_shortcode_obj ) ) {
			$fpsml_shortcode_obj->register_frontend_assets();
		} elseif ( class_exists( 'FPSML_Shortcode' ) ) {
			$sc = new \FPSML_Shortcode();
			$sc->register_frontend_assets();
		}

		ob_start();
		include FPSML_PATH . '/includes/views/frontend/form-shortcode.php';
		return ob_get_clean();
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Merge only the user-visible (translatable) text fields from a language
	 * translation array over the original form_details array.
	 *
	 * Structural/system fields (post_status, post_author, redirection_type,
	 * show_on_form, required, layout templates, captcha keys, etc.) are
	 * intentionally excluded so only human-readable text is overwritten.
	 *
	 * @param  array $original    Original form_details array from the DB.
	 * @param  array $translation Translated form_details array for the target language.
	 * @return array              Merged result.
	 */
	private function merge_translatable_fields( array $original, array $translation ) {
		// --- basic tab text fields ---
		foreach ( array( 'validation_error_message', 'form_success_message' ) as $key ) {
			if ( ! empty( $translation['basic'][ $key ] ) ) {
				$original['basic'][ $key ] = $translation['basic'][ $key ];
			}
		}

		// --- form tab: per-field text keys ---
		$field_text_keys = array(
			'field_label',
			'field_note',
			'required_error_message',
			'character_limit_error_message',
			'upload_button_label',
			'file_extension_error_message',
			'max_size_error_message',
			'first_option_label',
		);

		if ( isset( $translation['form']['fields'] ) && is_array( $translation['form']['fields'] ) ) {
			foreach ( $translation['form']['fields'] as $field_key => $field_trans ) {
				if ( ! is_array( $field_trans ) ) {
					continue;
				}
				foreach ( $field_text_keys as $key ) {
					if ( ! empty( $field_trans[ $key ] ) ) {
						$original['form']['fields'][ $field_key ][ $key ] = $field_trans[ $key ];
					}
				}
			}
		}

		// --- form tab: submit button label ---
		if ( ! empty( $translation['form']['submit_button_label'] ) ) {
			$original['form']['submit_button_label'] = $translation['form']['submit_button_label'];
		}

		// --- security tab text fields ---
		foreach ( array( 'captcha_label', 'error_message' ) as $key ) {
			if ( ! empty( $translation['security'][ $key ] ) ) {
				$original['security'][ $key ] = $translation['security'][ $key ];
			}
		}

		// --- login tab text fields ---
		$login_text_keys = array(
			'login_form_title',
			'username_label',
			'password_label',
			'login_button_label',
			'remember_me_label',
			'login_error_message',
			'login_note',
			'login_message',
			'login_link_label',
		);

		foreach ( $login_text_keys as $key ) {
			if ( ! empty( $translation['login'][ $key ] ) ) {
				$original['login'][ $key ] = $translation['login'][ $key ];
			}
		}

		// --- notification tab text fields ---
		$notification_types = array( 'admin', 'post_publish' );
		$notification_text_keys = array( 'subject', 'from_name', 'notification_message' );

		foreach ( $notification_types as $type ) {
			if ( isset( $translation['notification'][ $type ] ) && is_array( $translation['notification'][ $type ] ) ) {
				foreach ( $notification_text_keys as $key ) {
					if ( ! empty( $translation['notification'][ $type ][ $key ] ) ) {
						$original['notification'][ $type ][ $key ] = $translation['notification'][ $type ][ $key ];
					}
				}
			}
		}

		return $original;
	}
}
