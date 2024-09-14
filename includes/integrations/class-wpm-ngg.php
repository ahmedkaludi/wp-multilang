<?php
/**
 * Class for capability with NextGEN Gallery
 */

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPM_NGG
 * @package  WPM/Includes/Integrations
 * @category Integrations
 * @author   Valentyn Riaboshtan
 */
class WPM_NGG {

	/**
	 * WPM_NGG constructor.
	 */
	public function __construct() {
		add_filter( 'wpm_admin_pages', array( $this, 'add_admin_pages' ) );
		add_filter( 'wpm_admin_html_tags', array( $this, 'add_admin_html_tags' ) );
		add_filter( 'ngg_manage_gallery_fields', array( $this, 'filter_fields' ), 11 );
		add_filter( 'ngg_manage_images_row', array( $this, 'translate_gallery_object' ) );
		add_action( 'admin_head', array( $this, 'save_gallery' ) );
	}


	/**
	 * Transform POST value to multilingual value before save
	 */
	public function save_gallery() {

		if ( isset( $_POST['update_album'] ) ) {

			$fields = array( 'album_name' => 'name', 'album_desc' => 'albumdesc' );
			$data   = array();

			foreach ( $fields as $key => $field ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- this is a dependent function and its all security measurament is done wherever it has been used.
				if( isset( $_POST[ $key ] ) ) {
					$data[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				}
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- this is a dependent function and its all security measurament is done wherever it has been used.
			if ( isset( $_POST['act_album'] ) &&  $album = \C_Album_Mapper::get_instance()->find( wpm_clean( $_POST['act_album'] ) ) ) {
				foreach ( $data as $key => $value ) {
					if ( ! wpm_is_ml_string( $value ) ) {
						$_POST[ $key ] = wpm_set_new_value( $album->{$fields[ $key ]}, $value );
					}
				}
			}
		}

		if ( isset( $_POST['page'] ) && 'manage-images' === $_POST['page'] ) {

			if ( isset( $_POST['updatepictures'] ) ) {

				check_admin_referer( 'ngg_updategallery' );

				if ( ! isset( $_GET['s'] ) ) {

					$fields = array( 'title', 'galdesc' );
					$data   = array();

					foreach ( $fields as $field ) {
						if( isset( $_POST[ $field ] ) ) {
							$data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
						}
					}

					// Update the gallery
					$mapper = \C_Gallery_Mapper::get_instance();
					
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reason this is a dependent function and its all security measurament is done wherever it has been used.
					if ( isset( $_GET['gid'] ) && $entity = $mapper->find( wpm_clean( $_GET['gid'] ) ) ) {
						foreach ( $data as $key => $value ) {
							if ( ! wpm_is_ml_string( $value ) ) {
								$_POST[ $key ] = wpm_set_new_value( $entity->$key, $value );
							}
						}
					}
				}

				$image_mapper = \C_Image_Mapper::get_instance();

				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason unslash not needed because data is not getting stored in database, it's just being used.
				if( isset( $_POST['images'] ) ) {
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason unslash not needed because data is not getting stored in database, it's just being used.
					foreach ( $_POST['images'] as $pid => $image ) {

						$data = array();

						if ( isset( $image['description'] ) ) {
							$data['description'] = stripslashes( $image['description'] );
						}
						if ( isset( $image['alttext'] ) ) {
							$data['alttext'] = stripslashes( $image['alttext'] );
						}

						$old_image = $image_mapper->find( $pid );

						// Update all fields
						foreach ( $data as $key => $value ) {
							if ( ! wpm_is_ml_string( $value ) ) {
								$_POST['images'][ $pid ][ $key ] = wpm_set_new_value( $old_image->$key, $value );
							}
						}
					}
				}
			}// End if().
		}// End if().
	}


	/**
	 * Add pages for display language switcher
	 *
	 * @param $pages_config
	 *
	 * @return array
	 */
	public function add_admin_pages( $pages_config ) {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$admin_pages = array(
			'_page_nggallery-manage-gallery',
			'_page_nggallery-manage-album',
			'_page_ngg_display_settings',
		);

		foreach ( $admin_pages as $admin_page ) {
			if ( strpos( $screen_id, $admin_page ) !== false ) {
				$pages_config[] = $screen_id;
			}
		}

		return $pages_config;
	}


	/**
	 * Translate some field without PHP filters by javascript for displaying
	 *
	 * @param array $admin_html_tags
	 *
	 * @return array
	 */
	public function add_admin_html_tags( $admin_html_tags ) {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$admin_pages = array(
			'_page_nggallery-manage-gallery',
			'_page_nggallery-manage-album',
		);

		foreach ( $admin_pages as $admin_page ) {
			if ( strpos( $screen_id, $admin_page ) !== false ) {

				$html_tags = array();

				if ( '_page_nggallery-manage-album' === $admin_page ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing  -- this is a dependent function and its all security measurament is done wherever it has been used.
					if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['act_album'] ) ) {
						$html_tags = array(
							'value' => array(
								'#album_name',
								'#album_desc',
							)
						);
					}
				}

				if ( '_page_nggallery-manage-gallery' === $admin_page ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- this is a dependent function and its all security measurament is done wherever it has been used.
					if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && ( isset( $_POST['page'] ) && 'manage-images' === $_POST['page'] ) && isset( $_POST['updatepictures'] ) ) {
						$html_tags = array(
							'value' => array(
								'#gallery_title',
								'#gallery_description',
							)
						);
					}
				}

				$admin_html_tags[ $screen_id ] = $html_tags;
			}
		}

		return $admin_html_tags;
	}


	/**
	 * Translate gallery fields before display
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function filter_fields( $fields ) {

		$translate_fields = array(
			'title',
			'description',
		);

		foreach ( $translate_fields as $field ) {
			if ( isset( $fields['left'][ $field ] ) ) {
				$fields['left'][ $field ]['callback'][0]->gallery = $this->translate_gallery_object( $fields['left'][ $field ]['callback'][0]->gallery );
			}
		}

		return $fields;
	}


	/**
	 * Translate gallery object for displaying
	 *
	 * @param $object
	 *
	 * @return mixed
	 */
	public function translate_gallery_object( $object ) {

		foreach ( get_object_vars( $object ) as $key => $content ) {
			switch ( $key ) {
				case 'title':
				case 'galdesc':
				case 'alttext':
				case 'description':
					$object->$key = wpm_translate_string( $content );
					break;
			}
		}

		return $object;
	}
}
