<?php
/**
 * Class for capability with Advanced Custom Fields Plugin
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class    WPM_Acf
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Valentyn Riaboshtan
 */
class WPM_Acf {

	/**
	 * Pre-save snapshot of raw postmeta values, keyed by ACF post_id.
	 *
	 * Captured at acf/save_post priority 1 — before ACF writes any data.
	 * Shape: [ $post_id => [ $meta_key => $raw_meta_value, ... ] ]
	 *
	 * Used by update_value() to retrieve the original multilingual string for a
	 * subfield that belongs to a reordered repeater / flexible-content row.
	 *
	 * @since 2.4.35
	 * @var array
	 */
	private $meta_snapshot = array();

	/**
	 * Maps each new positional meta-key prefix to the original prefix it held at
	 * page-load time, keyed by ACF post_id.
	 *
	 * Shape: [ $post_id => [ 'sections_0' => 'sections_1', 'sections_1' => 'sections_0', ... ] ]
	 * A value of false means the row is brand-new (no prior database entry).
	 *
	 * ACF encodes the original row index in the HTML input name as row-{original_index}.
	 * When rows are reordered / added / deleted the submission order changes but the
	 * row-N identifiers stay, so we can reconstruct the mapping here.
	 *
	 * @since 2.4.35
	 * @var array
	 */
	private $row_prefix_map = array();

	/**
	 * WPM_Acf constructor.
	 */
	public function __construct() {
		add_filter( 'wpm_post_acf-field-group_config', array( $this, 'add_config' ) );
		add_filter( 'acf/load_field_group', 'wpm_translate_value', 6 );
		add_filter( 'acf/get_field_label', 'wpm_translate_string', 6 );
		add_filter( 'acf/update_field', array( $this, 'update_field' ), 99 );
		add_filter( 'acf/update_value', array( $this, 'update_value' ), 99, 3 );
		add_filter( 'acf/load_field', 'wpm_translate_value', 6 );
		add_filter( 'acf/load_value', 'wpm_translate_value', 6 );
		add_filter( 'acf/validate_field', [ $this, 'translate_validate_field' ] );
		add_filter( 'wpm_acf_field_text_config', array( $this, 'add_text_field_config' ) );
		add_filter( 'wpm_acf_field_textarea_config', array( $this, 'add_text_field_config' ) );
		add_filter( 'wpm_acf_field_wysiwyg_config', array( $this, 'add_text_field_config' ) );
		add_filter( 'wpm_acf_text_config', '__return_empty_array' );
		add_filter( 'wpm_acf_textarea_config', '__return_empty_array' );
		add_filter( 'wpm_acf_wysiwyg_config', '__return_empty_array' );
		add_filter( 'wpm_acf_image_config', '__return_empty_array' );
		add_filter( 'wpm_admin_pages', array( $this, 'add_dynamic_pages' ) );
		add_filter( 'wpm_filter_xliff_data', array( $this, 'add_acf_fields_to_export' ), 10, 2 );

		// Row-reorder fix: snapshot raw meta BEFORE ACF saves, clean up AFTER.
		// Priority 1  → runs before ACF's own save at priority 10.
		// Priority 25 → runs after WPM's update_value calls at priority 99 on
		//               acf/update_value, which are triggered inside priority 10.
		add_action( 'acf/save_post', array( $this, 'pre_save_post' ), 1 );
		add_action( 'acf/save_post', array( $this, 'post_save_cleanup' ), 25 );
	}


	/**
	 * Add config for 'acf' post types
	 *
	 * @param $config
	 *
	 * @return mixed
	 */
	public function add_config( $config ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) ) {
			$config = array(
				'post_content' => null,
				'post_excerpt' => null,
			);
		}

		return $config;
	}


	/**
	 * Save field object with translation
	 *
	 * @param $field
	 *
	 * @return array|bool|string
	 */
	public function update_field( $field ) {

		$old_field = maybe_unserialize( get_post_field( 'post_content', $field['ID'], 'edit' ) );

		if ( ! $old_field && ! is_array( $old_field ) ) {
			return $field;
		}

		if ( ! empty( $old_field ) && is_string( $old_field ) ) {
			$old_field 	=	wpm_translate_string( $old_field );
			$old_field 	=	maybe_unserialize( $old_field );
			if ( ! is_array( $old_field ) ) {
				return $field;
			}	
		}

		$old_field          = wpm_array_merge_recursive( $field, $old_field );
		$old_field          = wpm_value_to_ml_array( $old_field );
		$field_name         = get_post_field( 'post_title', $field['ID'], 'edit' );
		$old_field['label'] = wpm_value_to_ml_array( $field_name );

		$default_config = array(
			'label'        => array(),
			'placeholder'  => array(),
			'instructions' => array(),
		);

		$acf_field_config = apply_filters( "wpm_acf_field_{$field['type']}_config", $default_config );
		$acf_field_config = apply_filters( "wpm_acf_field_name_{$field['name']}_config", $acf_field_config );

		$new_field = wpm_set_language_value( $old_field, $field, $acf_field_config );
		$field     = wpm_array_merge_recursive( $field, $new_field );
		$field     = wpm_ml_value_to_string( $field );

		return $field;
	}

	/**
	 * Add translate config for text fields.
	 *
	 * @param $config
	 *
	 * @return array
	 */
	public function add_text_field_config( $config ) {
		$config['default_value'] = array();

		return $config;
	}


	/**
	 * Save value with translation.
	 *
	 * When a repeater or flexible-content field has rows that were reordered,
	 * added, or deleted, ACF re-saves subfields under new positional meta keys
	 * (e.g. sections_0_text_b).  The original code retrieved the existing
	 * multilingual string with get_field( $field['name'], ... ), which queries
	 * whatever value currently sits at that (new) key — not the value that
	 * originally belonged to the row being saved.  This caused translations from
	 * neighbouring rows to bleed across or be silently dropped.
	 *
	 * The fix uses a pre-save meta snapshot (see pre_save_post / build_row_prefix_map)
	 * to look up the original multilingual string for every shifted subfield.
	 * For rows that have not been reordered, and for all non-repeater fields, the
	 * code falls back to the original get_field() lookup — identical to the
	 * previous behaviour.
	 *
	 * @param $value
	 * @param $post_id
	 * @param $field
	 *
	 * @return array|bool|string
	 */
	public function update_value( $value, $post_id, $field ) {

		if ( wpm_is_ml_value( $value ) ) {
			return $value;
		}

		$info = acf_get_post_id_info( $post_id );

		// ACF is added as a block in post content then find that block
		if ( strpos( $post_id, 'block_' ) === 0 && is_array( $info ) ) {
    		$info['type'] = 'block';
		}
		
		switch ( $info['type'] ) {

			case 'post':
				$post_type = get_post_type( $info['id'] );
				if ( ! $post_type || null === wpm_get_post_config( $post_type ) ) {
					return $value;
				}

				break;

			case 'term':
				$term = get_term( $info['id'] );
				if ( ! $term || is_wp_error( $term ) || null === wpm_get_taxonomy_config( $term->taxonomy ) ) {
					return $value;
				}

				break;

			case 'block':
				
				$post_type = get_post_type( $info['id'] );
				if ( ! $post_type || null === wpm_get_post_config( $post_type ) ) {
					return $value;
				}

				break;

		}
		
		$acf_field_config = apply_filters( "wpm_acf_{$info['type']}_config", null, $value, $post_id, $field );
		$acf_field_config = apply_filters( "wpm_acf_{$field['type']}_config", $acf_field_config, $value, $post_id, $field );
		$acf_field_config = apply_filters( "wpm_acf_name_{$field['_name']}_config", $acf_field_config, $value, $post_id, $field );

		if ( null === $acf_field_config ) {
			return $value;
		}

		// ------------------------------------------------------------------
		// Row-reorder & multilingual fix: resolve the correct "old" raw string.
		// ------------------------------------------------------------------
		$old_value    = null;
		$resolved_key = $this->resolve_original_meta_key( $field['name'], $post_id );

		if ( false === $resolved_key ) {
			// Brand-new row: no prior translations exist for this subfield.
			$old_value = '';
		} elseif ( null !== $resolved_key ) {
			// Moved row: look up the subfield's original meta key from the pre-save snapshot.
			if ( isset( $this->meta_snapshot[ $post_id ][ $resolved_key ] ) ) {
				$old_value = maybe_unserialize( $this->meta_snapshot[ $post_id ][ $resolved_key ] );
			}
		}

		// Fallback for un-moved rows, non-repeater fields, options pages, terms, etc.:
		if ( null === $old_value ) {
			$fallback_key = ( false !== $resolved_key && null !== $resolved_key ) ? $resolved_key : $field['name'];
			remove_filter( 'acf/load_value', 'wpm_translate_value', 6 );
			$old_value = get_field( $fallback_key, $post_id, false );
			add_filter( 'acf/load_value', 'wpm_translate_value', 6 );
		}
		// ------------------------------------------------------------------

		$value = wpm_set_new_value( $old_value, $value, $acf_field_config );

		return $value;
	}

	// ======================================================================
	// Row-reorder fix — helper methods
	// ======================================================================

	/**
	 * Runs at acf/save_post priority 1, before ACF writes any data.
	 *
	 * 1. Snapshots the current raw postmeta so we can look up each subfield's
	 *    original multilingual string even after ACF has moved it to a different
	 *    meta key.
	 * 2. Builds a row prefix map from $_POST['acf'] that tells us which new
	 *    positional prefix corresponds to which original prefix.
	 *
	 * Only activates for browser form submissions that include $_POST['acf'].
	 * REST API, WP-CLI, WP All Import, and all programmatic saves are unaffected.
	 * Currently scoped to post objects; can be extended to terms/users in future.
	 *
	 * @since 2.4.35
	 * @param int|string $post_id ACF post ID (may be numeric or a string like 'options').
	 * @return void
	 */
	public function pre_save_post( $post_id ) {

		// Only handle standard admin form submissions that include ACF data.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) {
			return;
		}

		$info = acf_get_post_id_info( $post_id );

		// Scope to post objects only for this release.
		// Terms, users, and options pages continue to use the existing get_field() path.
		if ( empty( $info['type'] ) || 'post' !== $info['type'] ) {
			return;
		}

		$numeric_id = (int) $info['id'];

		// Snapshot all raw postmeta before ACF touches the database.
		//
		// IMPORTANT: We must bypass the WordPress meta-data filter stack here.
		// WPM registers a get_post_metadata filter that translates multilingual
		// strings into the current admin language on the way out of get_post_meta().
		// If we used get_post_meta() we would snapshot the already-translated
		// (single-language) value instead of the full [:en]...[:fr]...[:] string,
		// and we would have nothing useful to merge other languages into.
		//
		// Reading directly from the database guarantees we see the raw stored value.
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
			$numeric_id
		), ARRAY_A );

		$snapshot = array();
		foreach ( $rows as $row ) {
			// Keep the first occurrence of each key (ACF stores single-value meta).
			if ( ! array_key_exists( $row['meta_key'], $snapshot ) ) {
				$snapshot[ $row['meta_key'] ] = $row['meta_value'];
			}
		}
		$this->meta_snapshot[ $post_id ]    = $snapshot;
		$this->meta_snapshot[ $numeric_id ] = $snapshot;

		// Build the prefix map from the submitted form data.
		$this->row_prefix_map[ $post_id ]    = array();
		$this->row_prefix_map[ $numeric_id ] = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$this->build_row_prefix_map( $_POST['acf'], $post_id );
		$this->row_prefix_map[ $numeric_id ] = $this->row_prefix_map[ $post_id ];
	}

	/**
	 * Recursively builds the row prefix map from a slice of $_POST['acf'].
	 *
	 * ACF renders every existing repeater / flexible-content row with the HTML
	 * attribute data-id="row-{original_index}".  The JavaScript uses this as the
	 * input name prefix: sections[row-2][field_xxx].  After drag-to-reorder the
	 * DOM order changes (so the POST array order reflects the NEW sequence) but
	 * the row-N identifiers remain, allowing us to map new index → old index.
	 *
	 * Example — user moves row A from position 0 to position 2:
	 *   POST order: row-1, row-2, row-0  (positions 0, 1, 2 in submission)
	 *   Map built:  sections_0 → sections_1
	 *               sections_1 → sections_2
	 *               sections_2 → sections_0
	 *
	 * Newly added rows do not have a row-N key; they are mapped to false.
	 *
	 * @since 2.4.35
	 * @param array      $data        Slice of POST data to scan (field_key => row_data).
	 * @param int|string $post_id     ACF post ID.
	 * @param string     $new_parent  Positional prefix for the current level (new index path).
	 * @param string     $old_parent  Positional prefix for the current level (original index path).
	 * @return void
	 */
	private function build_row_prefix_map( array $data, $post_id, $new_parent = '', $old_parent = '' ) {

		foreach ( $data as $key => $value ) {

			// Only process arrays keyed by ACF field keys (field_xxxxxxxx).
			if ( ! is_array( $value ) || 'acfcloneindex' === $key || 0 !== strpos( $key, 'field_' ) ) {
				continue;
			}

			$field = acf_get_field( $key );
			if ( ! $field ) {
				continue;
			}

			if ( 'group' === $field['type'] ) {
				$group_new = '' === $new_parent ? $field['name'] : $new_parent . '_' . $field['name'];
				$group_old = '' === $old_parent ? $field['name'] : $old_parent . '_' . $field['name'];
				$this->build_row_prefix_map( $value, $post_id, $group_new, $group_old );
				continue;
			}

			if ( ! in_array( $field['type'], array( 'repeater', 'flexible_content' ), true ) ) {
				// Not a row-based field — no prefix mapping needed at this level.
				// We do NOT recurse here because non-repeater field values are plain
				// scalars; there are no nested repeaters to discover.
				continue;
			}

			// Build the full meta-key prefix for this field at the current nesting level.
			$new_field_prefix = '' === $new_parent
				? $field['name']
				: $new_parent . '_' . $field['name'];

			$old_field_prefix = '' === $old_parent
				? $field['name']
				: $old_parent . '_' . $field['name'];

			$new_i = 0;

			foreach ( $value as $row_key => $row_data ) {

				// Skip the ACF clone-index template row (never a real row).
				if ( 'acfcloneindex' === $row_key || ! is_array( $row_data ) ) {
					continue;
				}

				$new_prefix = $new_field_prefix . '_' . $new_i;

				if ( preg_match( '/^row-(\d+)$/', $row_key, $matches ) ) {

					// Existing row: the original index is encoded in the row key.
					$old_i      = (int) $matches[1];
					$old_prefix = $old_field_prefix . '_' . $old_i;

					// Only record in row_prefix_map if the row ACTUALLY shifted position.
					// If the row stayed in the exact same position ($new_prefix === $old_prefix),
					// leaving it unmapped allows update_value() to use the standard get_field()
					// path, completely eliminating any risk of regression for normal edits.
					if ( $new_prefix !== $old_prefix ) {
						$this->row_prefix_map[ $post_id ][ $new_prefix ] = $old_prefix;
					}

					// Recurse into the row's sub-fields to handle nested repeaters
					// or flexible-content fields.  Pass BOTH the new and old prefix
					// so that inner mappings correctly combine both levels of indexing.
					$this->build_row_prefix_map( $row_data, $post_id, $new_prefix, $old_prefix );

				} else {

					// Newly added row: there is no prior database entry.
					$this->row_prefix_map[ $post_id ][ $new_prefix ] = false;

					// No recursion for new rows: all nested subfields are also new,
					// so they carry no existing multilingual data to preserve.
				}

				$new_i++;
			}
		}
	}

	/**
	 * Resolves a new positional meta key to the original meta key it corresponds
	 * to, using the pre-built row prefix map.
	 *
	 * Uses longest-prefix matching so that nested repeaters are resolved against
	 * their innermost mapping rather than the outer one.
	 *
	 * @since 2.4.35
	 * @param string     $field_name  New positional meta key (e.g. 'sections_0_text_b').
	 * @param int|string $post_id     ACF post ID.
	 * @return string|false|null
	 *   string — the original meta key; fetch translations from meta_snapshot.
	 *   false  — the row is brand-new; use '' as old_value.
	 *   null   — no mapping found; fall back to the existing get_field() logic.
	 */
	private function resolve_original_meta_key( $field_name, $post_id ) {

		if ( empty( $this->row_prefix_map[ $post_id ] ) ) {
			return null;
		}

		$best_len    = -1;
		$best_prefix = null;

		foreach ( $this->row_prefix_map[ $post_id ] as $new_prefix => $old_prefix ) {
			// The field name must start with new_prefix followed by '_' to avoid
			// false positives (e.g. 'sections_10_text' matching 'sections_1').
			if ( 0 === strpos( $field_name, $new_prefix . '_' ) ) {
				$len = strlen( $new_prefix );
				if ( $len > $best_len ) {
					$best_len    = $len;
					$best_prefix = $new_prefix;
				}
			}
		}

		if ( null === $best_prefix ) {
			return null; // Not a subfield of any mapped row.
		}

		$old_prefix = $this->row_prefix_map[ $post_id ][ $best_prefix ];

		if ( false === $old_prefix ) {
			return false; // Brand-new row.
		}

		// Replace the new prefix with the old prefix to reconstruct the original
		// meta key.  Everything after the prefix (sub-field name, inner indices)
		// is left unchanged.
		return $old_prefix . substr( $field_name, strlen( $best_prefix ) );
	}

	/**
	 * Runs at acf/save_post priority 25, after all data has been written.
	 *
	 * Clears the in-memory snapshot and prefix map for the given post so that no
	 * stale state leaks to subsequent queries or requests.
	 *
	 * @since 2.4.35
	 * @param int|string $post_id
	 * @return void
	 */
	public function post_save_cleanup( $post_id ) {
		$info = acf_get_post_id_info( $post_id );
		unset( $this->meta_snapshot[ $post_id ] );
		unset( $this->row_prefix_map[ $post_id ] );
		if ( ! empty( $info['id'] ) ) {
			unset( $this->meta_snapshot[ (int) $info['id'] ] );
			unset( $this->row_prefix_map[ (int) $info['id'] ] );
		}
	}

	// ======================================================================
	// End row-reorder fix
	// ======================================================================

	/**
	 * Add dynamic option pages to config
	 * @param 	$config 	array
	 * @return 	$config 	array
	 * @since 	2.4.19
	 * */
	public function add_dynamic_pages( $config ) {
		
		$posts = get_posts([
		    'post_type'      => 'acf-ui-options-page',
		    'post_status'    => 'publish',
		    'numberposts'    => -1, // Get all published posts
		]);

		if ( ! empty( $posts ) && is_array( $posts ) ) {
			foreach ( $posts as $option_page ) {
				if ( is_object( $option_page ) && ! empty( $option_page->post_content ) ) {
					$content 	=	maybe_unserialize( wpm_translate_string( $option_page->post_content ) );
					if ( is_array( $content ) && ! empty( $content['menu_slug'] ) ) {
						$parent 	=	$content['parent_slug'];
						$page_id 	=	'toplevel_page_' . $content['menu_slug'];
						$config[] 	=	$page_id;
						
						// If page is assigned to any of the menus the get the proper base to add it into the config
						if ( $parent != 'none' ) {
							
							if ( $parent == 'options-general.php' ) {
								$page_id 	=	'settings_page_' . $content['menu_slug']; 	
							} else if( $parent == 'tools.php' ) {
								$page_id 	=	'tools_page_' . $content['menu_slug'];
							}else{

								// If parent is assigned other than settings and tools menu then get the correct base for this
								$parse_parent	=	wp_parse_url( $parent, PHP_URL_QUERY );
								parse_str( $parse_parent, $params );
								if ( is_array( $params ) && ! empty( $params['post_type'] ) ) {
									$delimiter	=	strpos( $params['post_type'], '-' ) !== false ? '-' : '_';
									if ( ! empty( $delimiter ) ) {
										$base 	=	explode( $delimiter, $params['post_type'] )[0];
										$page_id 	=	$base . '_' . 'page_' . $content['menu_slug'];
									}
								}

							}
							
						}

						$config[] 	=	$page_id;
					}
				}	
			}
		}
		
		return $config;

	}

	/**
	 * Get acf field and value and add it into data for export
	 * @param 	$data 	array
	 * @param 	$post 	WP_Post
	 * @return 	$data 	array
	 * @since 	2.4.21
	 * */
	public function add_acf_fields_to_export( $data, $post ) {
		// Get ACF fields
        if ( function_exists( 'get_fields' ) && ! empty( $data ) ) {

        	global $wpdb;
        	$default_lang = wpm_get_default_language();
        	$current_lang = wpm_get_language();

        	foreach ( $data as $key => $value ) {
        		if ( is_array( $value ) && ! empty( $value['id'] ) ) {
        			$acf_fields     	=   get_fields( $value['id'] );
		        	if( ! empty( $acf_fields ) ) {
		                foreach ( $acf_fields as $field_key => $field ) {

		                	if ( is_string( $field ) ) {

		                		$result 		=	$wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $value['id'], $field_key ), ARRAY_A );

		                		if ( is_array( $result ) && ! empty( $result[0] ) && is_array( $result[0] ) ) {
		                			if ( ! empty( $result[0]['meta_value'] ) && is_string( $result[0]['meta_value'] ) ) {

		                				$field_array 					=	array();
		                				$field_array['key'] 			=	$field_key;
		                				$field_array['source_value'] 	=	wpm_translate_string( $result[0]['meta_value'], $default_lang );
		                				$target_value 					=	wpm_translate_string( $result[0]['meta_value'], $current_lang );
		                				$field_array['target_value'] 	=	'';
		                				if ( $field_array['source_value'] !== $target_value ) {
		                					$field_array['target_value'] 	=	$target_value;	
		                				}
										$data[$key]['acf'][] 	=	$field_array; 	

		                			}
		                		}
		              
		                	} else if( is_array( $field ) && ! empty( $field[0] ) ) {
		                		// Repeater fields
		                		foreach ( $field as $r_key => $repeater ) {

		                			foreach ( $repeater as $rs_key => $sub_repeater ) {
		                				$repeater_key 	=	$field_key . '_' . $r_key . '_' . $rs_key;
		                				$result 		=	$wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $value['id'], $repeater_key ), ARRAY_A );

			                			if ( is_array( $result ) && ! empty( $result[0] ) && is_array( $result[0] ) ) {

			                				if ( is_string( $result[0]['meta_value'] ) ) {
				                				$field_array 					=	array();
				                				$field_array['key'] 			=	$repeater_key;
				                				$field_array['source_value'] 	=	wpm_translate_string( $result[0]['meta_value'], $default_lang );
				                				$target_value 					=	wpm_translate_string( $result[0]['meta_value'], $current_lang );
				                				$field_array['target_value'] 	=	'';
				                				if ( $field_array['source_value'] !== $target_value ) {
				                					$field_array['target_value'] 	=	$target_value;	
				                				}
												$data[$key]['acf'][] 	=	$field_array; 	
											}
		         	
			                			}
		                			
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

	/**
	 * Translate the validate field
	 * @param 	$field 	array
	 * @return 	$field 	array
	 * @since 	2.4.28
	 * */
	public function translate_validate_field( $field ) {
		
		if ( ! empty( $field ) && is_array( $field ) && ! empty( $field[0] ) ) {

			$translate_field_content 	= 	(array) acf_maybe_unserialize( wpm_translate_value( $field[0] ) );

			// Unset the unwanted array field generated by ACF
			unset( $field[0] );

			if ( is_array( $translate_field_content ) ) {
				$field 	=	array_merge( $field, $translate_field_content );
			}

		}
		
		return $field;

	}
}
