<?php
/**
 * WP Multilang Uninstall
 *
 * Uninstalling  WP Multilang deletes translations and options.
 *
 * @author   Valentyn Riaboshtan
 * @category    Core
 * @package     WPM/Uninstaller
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

require_once __DIR__ . '/wp-multilang.php';

if ( get_option( 'wpm_uninstall_translations', 'no' ) === 'yes' ) {
	
	WPM\Includes\Admin\WPM_Reset_Settings::wpm_uninstall_translations_data();

} // End if().


//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching --Reason Reason We are just cleaning the data that has been changed by our plugin
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wpm_%';" );
