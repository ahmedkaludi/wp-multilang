<?php
/**
 * Plugin Name:       WP Multilang
 * Plugin URI:        https://github.com/ahmedkaludi/wp-multilang
 * GitHub Plugin URI: https://github.com/ahmedkaludi/wp-multilang
 * Description:       Multilingual plugin for WordPress. Go Multilingual in minutes with full WordPress support. Translate your site easily with this localization plugin.
 * Author:            Valentyn Riaboshtan
 * Author URI: 		  https://wp-multilang.com/
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-multilang
 * Domain Path:       /languages
 * Version:           2.4.10
 * Copyright:         © 2017-2019 Valentyn Riaboshtan
 *
 * @package  WPM
 * @category Core
 * @author   Valentyn Riaboshtan
 */

use WPM\Includes\WP_Multilang;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define WPM_PLUGIN_FILE.
if ( ! defined( 'WPM_PLUGIN_FILE' ) ) {
	define( 'WPM_PLUGIN_FILE', __FILE__ );
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/wpm-widget-block-editor.php';

function wpm() {
	return WP_Multilang::instance();
}

wpm();


/**
 * Set transient on plugin activation
 * @since 1.0
 * */
function wpm_activate_plugin( $network_active ) {

	// Not network active i.e. plugin is activated on a single install (normal WordPress install) or a single site on a multisite network
	if ( ! $network_active ) {
		
		// Set transient for single site activation notice
		set_transient( 'wpm_admin_notice_activation', true, 60 );
		
		return;
	}
}
register_activation_hook( __FILE__, 'wpm_activate_plugin' );


/**
 * Admin notice on plugin activation
 *
 * @since 1.0 
 */
function wpm_activation_admin_notices() {
	
	// Notices only for admins
	if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Admin notice on plugin activation
	if ( get_transient( 'wpm_admin_notice_activation' ) ) {
		
		// Do not display link to settings UI if we are already in the UI.
		$screen = get_current_screen();
		/* translators: %s: This will get the settings admin url link */
		$wpm_ui_link_text_escaped = ( strpos( $screen->id, 'wpm' ) === false ) ? sprintf( __( '<a href="%s">Add your languages & Customize your settings &rarr;</a>', 'wp-multilang' ), admin_url( 'admin.php?page=wpm-settings' ) ) : '';
		
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all html inside this variable already escaped above in $wpm_ui_link_text_escaped variable
		echo '<div class="updated notice is-dismissible"><p>' . esc_html__( 'Thank you for installing <strong>WP Multilang!</strong> ', 'wp-multilang' ) . $wpm_ui_link_text_escaped . '</p></div>';
		
		// Delete transient
		delete_transient( 'wpm_admin_notice_activation' );
	}
}
add_action( 'admin_notices', 'wpm_activation_admin_notices' );