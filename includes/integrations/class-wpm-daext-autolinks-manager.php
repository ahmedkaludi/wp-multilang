<?php
/**
 * Class for compatibility with daext-autolinks-manager (Category & Autolink translations)
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPM_Daext_Autolinks_Manager {

	private $original_wpdb = null;

	public function __construct() {
		// Intercept form submissions on admin_init to merge translations in options instead of direct table updates
		add_action( 'admin_init', array( $this, 'intercept_form_submissions' ) );

		// Wrap $wpdb database connection on Autolink Manager admin screens
		add_action( 'current_screen', array( $this, 'admin_db_wrapper' ) );

		// Wrap $wpdb database connection during REST API requests (for React-based dashboard/options pages)
		add_action( 'rest_api_init', array( $this, 'rest_db_wrapper' ) );

		// Wrap $wpdb database connection during the front-end autolinks replacement loop
		$priority = intval( get_option( 'daextam_advanced_filter_priority' ), 10 );
		if ( ! $priority ) {
			$priority = 2147483646; // Default priority in daext-autolinks-manager
		}
		add_filter( 'the_content', array( $this, 'add_autolinks_translation_wrapper' ), $priority - 1 );
		add_filter( 'the_content', array( $this, 'remove_autolinks_translation_wrapper' ), $priority + 1 );

		// Enqueue switcher compatibility JS script
		add_action( 'admin_enqueue_scripts', array( $this, 'add_lang_switcher_script' ), 99 );
	}

	public function add_lang_switcher_script( $hook_suffix ) {
		$pages = array( 'autolinks_page_daextam-categories', 'autolinks_page_daextam-autolinks', 'autolinks_page_daextam-dashboard', 'toplevel_page_daextam-dashboard' );

		if ( in_array( $hook_suffix, $pages, true ) ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			$main_params = array(
				'plugin_url'                 => wpm()->plugin_url(),
				'ajax_url'                   => admin_url( 'admin-ajax.php' ),
				'wpm_daext_autolinks_nonce'  => wp_create_nonce( 'wpm-daext-autolinks-localization' ),
			);

			wp_register_script( 'wpm-daext-autolinks-script', wpm_asset_path( 'scripts/wpm-daext-autolinks' . $suffix . '.js' ), array( 'jquery' ), WPM_VERSION, true );
			wp_localize_script( 'wpm-daext-autolinks-script', 'wpm_daext_autolinks_params', $main_params );
			wp_enqueue_script( 'wpm-daext-autolinks-script' );
		}
	}

	/**
	 * Intercept form submissions to store translations in options
	 */
	public function intercept_form_submissions() {
		if ( ! is_admin() || ! isset( $_GET['page'] ) ) {
			return;
		}

		global $wpdb;

		$current_lang = wpm_get_language();
		$default_lang = wpm_get_default_language();

		if ( $_GET['page'] === 'daextam-categories' && ( isset( $_POST['form_submitted'] ) || isset( $_POST['update_id'] ) ) ) {
			$update_id = isset( $_POST['update_id'] ) ? intval( $_POST['update_id'], 10 ) : null;

			if ( $update_id ) {
				$existing = $wpdb->get_row( $wpdb->prepare( "SELECT name, description FROM {$wpdb->prefix}daextam_category WHERE category_id = %d", $update_id ), ARRAY_A );
				if ( $existing ) {
					if ( $current_lang !== $default_lang ) {
						$option_key = "wpm_daextam_category_{$update_id}_translate";
						$translations = get_option( $option_key, array() );
						if ( ! is_array( $translations ) ) {
							$translations = array();
						}

						if ( isset( $_POST['name'] ) ) {
							$translations['name'][ $current_lang ] = sanitize_text_field( wp_unslash( $_POST['name'] ) );
							$_POST['name'] = $existing['name']; // Revert to keep DB clean
						}
						if ( isset( $_POST['description'] ) ) {
							$translations['description'][ $current_lang ] = sanitize_text_field( wp_unslash( $_POST['description'] ) );
							$_POST['description'] = $existing['description']; // Revert to keep DB clean
						}

						update_option( $option_key, $translations );
					}
				}
			}
		}

		if ( $_GET['page'] === 'daextam-autolinks' && ( isset( $_POST['form_submitted'] ) || isset( $_POST['update_id'] ) ) ) {
			$update_id = isset( $_POST['update_id'] ) ? intval( $_POST['update_id'], 10 ) : null;

			if ( $update_id ) {
				$existing = $wpdb->get_row( $wpdb->prepare( "SELECT name, keyword, title, url FROM {$wpdb->prefix}daextam_autolink WHERE autolink_id = %d", $update_id ), ARRAY_A );
				if ( $existing ) {
					if ( $current_lang !== $default_lang ) {
						$option_key = "wpm_daextam_autolink_{$update_id}_translate";
						$translations = get_option( $option_key, array() );
						if ( ! is_array( $translations ) ) {
							$translations = array();
						}

						$fields = array( 'name', 'keyword', 'title', 'url' );
						foreach ( $fields as $field ) {
							if ( isset( $_POST[ $field ] ) ) {
								if ( $field === 'url' ) {
									$translations[ $field ][ $current_lang ] = esc_url_raw( wp_unslash( $_POST[ $field ] ) );
								} else {
									$translations[ $field ][ $current_lang ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
								}
								$_POST[ $field ] = $existing[ $field ]; // Revert to keep DB clean
							}
						}

						update_option( $option_key, $translations );
					}
				}
			}
		}
	}

	/**
	 * Wrap global $wpdb on Autolink Manager admin screens
	 */
	public function admin_db_wrapper() {
		$screen = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$pages = array( 'autolinks_page_daextam-categories', 'autolinks_page_daextam-autolinks', 'autolinks_page_daextam-dashboard', 'toplevel_page_daextam-dashboard' );

		if ( in_array( $screen_id, $pages, true ) ) {
			global $wpdb;
			$wpdb = new WPM_WPDB_Wrapper( $wpdb );
		}
	}

	/**
	 * Wrap global $wpdb during REST API requests
	 */
	public function rest_db_wrapper() {
		global $wpdb;
		$wpdb = new WPM_WPDB_Wrapper( $wpdb );
	}

	/**
	 * Enable wrapper before autolink replacement
	 */
	public function add_autolinks_translation_wrapper( $content ) {
		global $wpdb;
		$this->original_wpdb = $wpdb;
		$wpdb = new WPM_WPDB_Wrapper( $wpdb );
		return $content;
	}

	/**
	 * Disable wrapper after autolink replacement
	 */
	public function remove_autolinks_translation_wrapper( $content ) {
		global $wpdb;
		if ( $this->original_wpdb && $wpdb instanceof WPM_WPDB_Wrapper ) {
			// Copy all updated properties back to the original wpdb instance
			$wpdb->sync_properties_back();
			$wpdb = $this->original_wpdb;
			$this->original_wpdb = null;
		}
		return $content;
	}
}

/**
 * Custom wpdb wrapper class to translate Category & Autolink results dynamically from options
 */
class WPM_WPDB_Wrapper extends \wpdb {
	private $real_wpdb;
	private static $autolink_translations_cache = null;
	private static $category_translations_cache = null;

	public function __construct( $real_wpdb ) {
		$this->real_wpdb = $real_wpdb;
		$this->sync_properties();
	}

	/**
	 * Synchronize properties from real_wpdb to this wrapper
	 */
	private function sync_properties() {
		foreach ( get_object_vars( $this->real_wpdb ) as $key => $val ) {
			if ( $key !== 'result' ) {
				$this->$key = $val;
			}
		}

		// Translate last_result if it is set
		if ( is_array( $this->last_result ) && ! empty( $this->last_result ) ) {
			$current_lang = wpm_get_language();
			$default_lang = wpm_get_default_language();

			if ( $current_lang !== $default_lang ) {
				$query = $this->last_query;
				if ( stripos( $query, 'daextam_category' ) !== false ) {
					foreach ( $this->last_result as $key => $row ) {
						$has_id = is_array( $row ) ? isset( $row['category_id'] ) : isset( $row->category_id );
						if ( ! $has_id ) {
							continue;
						}
						$id = is_array( $row ) ? $row['category_id'] : $row->category_id;
						$translations = $this->get_category_translations( $id );
						if ( is_array( $translations ) && ! empty( $translations ) ) {
							if ( is_array( $row ) ) {
								if ( isset( $translations['name'][ $current_lang ] ) && ! empty( $translations['name'][ $current_lang ] ) ) {
									$this->last_result[ $key ]['name'] = $translations['name'][ $current_lang ];
								}
								if ( isset( $translations['description'][ $current_lang ] ) && ! empty( $translations['description'][ $current_lang ] ) ) {
									$this->last_result[ $key ]['description'] = $translations['description'][ $current_lang ];
								}
							} else if ( is_object( $row ) ) {
								if ( isset( $translations['name'][ $current_lang ] ) && ! empty( $translations['name'][ $current_lang ] ) ) {
									$row->name = $translations['name'][ $current_lang ];
								}
								if ( isset( $translations['description'][ $current_lang ] ) && ! empty( $translations['description'][ $current_lang ] ) ) {
									$row->description = $translations['description'][ $current_lang ];
								}
							}
						}
					}
				} elseif ( stripos( $query, 'daextam_autolink' ) !== false ) {
					foreach ( $this->last_result as $key => $row ) {
						$has_id = is_array( $row ) ? isset( $row['autolink_id'] ) : isset( $row->autolink_id );
						if ( ! $has_id ) {
							continue;
						}
						$id = is_array( $row ) ? $row['autolink_id'] : $row->autolink_id;
						$translations = $this->get_autolink_translations( $id );
						if ( is_array( $translations ) && ! empty( $translations ) ) {
							$fields = array( 'name', 'keyword', 'title', 'url' );
							foreach ( $fields as $field ) {
								if ( is_array( $row ) ) {
									if ( isset( $translations[ $field ][ $current_lang ] ) && ! empty( $translations[ $field ][ $current_lang ] ) ) {
										$this->last_result[ $key ][ $field ] = $translations[ $field ][ $current_lang ];
									}
								} else if ( is_object( $row ) ) {
									if ( isset( $translations[ $field ][ $current_lang ] ) && ! empty( $translations[ $field ][ $current_lang ] ) ) {
										$row->$field = $translations[ $field ][ $current_lang ];
									}
								}
							}
						}
					}
				}
			}

			// Post titles in daextam_statistic must always be cleaned/translated, even for the default language
			$query = $this->last_query;
			if ( stripos( $query, 'daextam_statistic' ) !== false ) {
				foreach ( $this->last_result as $key => $row ) {
					if ( is_array( $row ) ) {
						if ( isset( $row['post_title'] ) ) {
							$this->last_result[ $key ]['post_title'] = wpm_translate_string( $row['post_title'] );
						}
					} else if ( is_object( $row ) ) {
						if ( isset( $row->post_title ) ) {
							$row->post_title = wpm_translate_string( $row->post_title );
						}
					}
				}
			}
		}
	}

	/**
	 * Synchronize properties back to the real_wpdb instance
	 */
	public function sync_properties_back() {
		foreach ( get_object_vars( $this ) as $key => $val ) {
			if ( $key !== 'real_wpdb' && $key !== 'result' && $key !== 'dbh' ) {
				$this->real_wpdb->$key = $val;
			}
		}
	}

	public function prepare( $query, ...$args ) {
		return $this->real_wpdb->prepare( $query, ...$args );
	}

	public function query( $query ) {
		$result = $this->real_wpdb->query( $query );
		$this->sync_properties();
		return $result;
	}

	private function get_category_translations( $id ) {
		if ( self::$category_translations_cache === null ) {
			$real_wpdb = $this->real_wpdb;
			$results = $real_wpdb->get_results(
				"SELECT option_name, option_value FROM {$real_wpdb->prefix}options WHERE option_name LIKE 'wpm_daextam_category_%_translate'",
				ARRAY_A
			);
			self::$category_translations_cache = array();
			if ( is_array( $results ) ) {
				foreach ( $results as $row ) {
					if ( preg_match( '/wpm_daextam_category_(\d+)_translate/', $row['option_name'], $matches ) ) {
						$category_id = intval( $matches[1] );
						self::$category_translations_cache[ $category_id ] = maybe_unserialize( $row['option_value'] );
					}
				}
			}
		}
		return isset( self::$category_translations_cache[ $id ] ) ? self::$category_translations_cache[ $id ] : array();
	}

	private function get_autolink_translations( $id ) {
		if ( self::$autolink_translations_cache === null ) {
			$real_wpdb = $this->real_wpdb;
			$results = $real_wpdb->get_results(
				"SELECT option_name, option_value FROM {$real_wpdb->prefix}options WHERE option_name LIKE 'wpm_daextam_autolink_%_translate'",
				ARRAY_A
			);
			self::$autolink_translations_cache = array();
			if ( is_array( $results ) ) {
				foreach ( $results as $row ) {
					if ( preg_match( '/wpm_daextam_autolink_(\d+)_translate/', $row['option_name'], $matches ) ) {
						$autolink_id = intval( $matches[1] );
						self::$autolink_translations_cache[ $autolink_id ] = maybe_unserialize( $row['option_value'] );
					}
				}
			}
		}
		return isset( self::$autolink_translations_cache[ $id ] ) ? self::$autolink_translations_cache[ $id ] : array();
	}

	public function get_results( $query = null, $output = OBJECT ) {
		if ( $query === null ) {
			return $this->last_result;
		}

		$results = $this->real_wpdb->get_results( $query, $output );
		$this->sync_properties();

		// Translate $results directly to return it in the correct format
		if ( is_array( $results ) && ! empty( $results ) ) {
			$current_lang = wpm_get_language();
			$default_lang = wpm_get_default_language();

			if ( $current_lang !== $default_lang ) {
				if ( stripos( $query, 'daextam_category' ) !== false ) {
					foreach ( $results as $key => $row ) {
						$has_id = is_array( $row ) ? isset( $row['category_id'] ) : isset( $row->category_id );
						if ( ! $has_id ) {
							continue;
						}
						$id = is_array( $row ) ? $row['category_id'] : $row->category_id;
						$translations = $this->get_category_translations( $id );
						if ( is_array( $translations ) && ! empty( $translations ) ) {
							if ( is_array( $row ) ) {
								if ( isset( $translations['name'][ $current_lang ] ) && ! empty( $translations['name'][ $current_lang ] ) ) {
									$results[ $key ]['name'] = $translations['name'][ $current_lang ];
								}
								if ( isset( $translations['description'][ $current_lang ] ) && ! empty( $translations['description'][ $current_lang ] ) ) {
									$results[ $key ]['description'] = $translations['description'][ $current_lang ];
								}
							} else if ( is_object( $row ) ) {
								if ( isset( $translations['name'][ $current_lang ] ) && ! empty( $translations['name'][ $current_lang ] ) ) {
									$row->name = $translations['name'][ $current_lang ];
								}
								if ( isset( $translations['description'][ $current_lang ] ) && ! empty( $translations['description'][ $current_lang ] ) ) {
									$row->description = $translations['description'][ $current_lang ];
								}
							}
						}
					}
				} elseif ( stripos( $query, 'daextam_autolink' ) !== false ) {
					foreach ( $results as $key => $row ) {
						$has_id = is_array( $row ) ? isset( $row['autolink_id'] ) : isset( $row->autolink_id );
						if ( ! $has_id ) {
							continue;
						}
						$id = is_array( $row ) ? $row['autolink_id'] : $row->autolink_id;
						$translations = $this->get_autolink_translations( $id );
						if ( is_array( $translations ) && ! empty( $translations ) ) {
							$fields = array( 'name', 'keyword', 'title', 'url' );
							foreach ( $fields as $field ) {
								if ( is_array( $row ) ) {
									if ( isset( $translations[ $field ][ $current_lang ] ) && ! empty( $translations[ $field ][ $current_lang ] ) ) {
										$results[ $key ][ $field ] = $translations[ $field ][ $current_lang ];
									}
								} else if ( is_object( $row ) ) {
									if ( isset( $translations[ $field ][ $current_lang ] ) && ! empty( $translations[ $field ][ $current_lang ] ) ) {
										$row->$field = $translations[ $field ][ $current_lang ];
									}
								}
							}
						}
					}
				}
			}

			if ( stripos( $query, 'daextam_statistic' ) !== false ) {
				foreach ( $results as $key => $row ) {
					if ( is_array( $row ) ) {
						if ( isset( $row['post_title'] ) ) {
							$results[ $key ]['post_title'] = wpm_translate_string( $row['post_title'] );
						}
					} else if ( is_object( $row ) ) {
						if ( isset( $row->post_title ) ) {
							$row->post_title = wpm_translate_string( $row->post_title );
						}
					}
				}
			}
		}

		return $results;
	}
}
