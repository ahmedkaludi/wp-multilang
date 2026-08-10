<?php
/**
* Class for capability with ux4g custom theme
* @since 2.4.33
*/

namespace WPM\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPM_UX4G_Theme {

	public function __construct(){
		
		add_action( 'wpm_clear_blockeditor_post_data_cache', [ $this, 'clear_cache' ], 10, 2 );

	}

	/**
	 * If there more than 2 language translation was getting overridden
	 * @param 	$is_block_editor	boolean
	 * @since 	2.4.33
	 * */
	public function clear_cache( $post_id, $is_block_editor ) {
		
		if ( $is_block_editor ) {
			clean_post_cache( $post_id );
		}

	}

}