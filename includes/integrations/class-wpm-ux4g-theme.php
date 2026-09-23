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
		add_filter( 'rest_pre_dispatch', [ $this, 'clean_cache_on_rest_request' ], 10, 3 );

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

	/**
	 * Clean post cache during REST request to load fresh delimiters on edit screen
	 *
	 * @param mixed $result
	 * @param mixed $server
	 * @param \WP_REST_Request $request
	 * @return mixed
	 */
	public function clean_cache_on_rest_request( $result, $server, $request ) {
		$route = $request->get_route();
		if ( preg_match( '#^/wp/v2/(?P<post_type>[^/]+)/(?P<id>\d+)$#', $route, $matches ) ) {
			$post_id = (int) $matches['id'];
			if ( $post_id > 0 ) {
				clean_post_cache( $post_id );
			}
		}

		return $result;
	}

}