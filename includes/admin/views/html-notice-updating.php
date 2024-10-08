<?php
/**
 * Admin View: Notice - Updating
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="wpm-message notice notice-success">
	<p><strong><?php esc_html_e( 'WP Multilang data update', 'wp-multilang' ); ?></strong> &#8211; <?php esc_html_e( 'Your database is being updated in the background.', 'wp-multilang' ); ?> <a href="<?php echo esc_url( add_query_arg( 'force_update_wpm', 'true', admin_url( 'admin.php?page=wpm-settings' ) ) ); ?>"><?php esc_html_e( 'Taking a while? Click here to run it now.', 'wp-multilang' ); ?></a></p>
</div>
