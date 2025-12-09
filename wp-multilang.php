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
 * Version:           2.4.24
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
		?>
		<div class="updated notice is-dismissible">
			<p>
				<?php echo esc_html__( 'Thank you for installing', 'wp-multilang' ); ?> 
				<strong>WP Multilang!</strong>
				<?php
				if( strpos( $screen->id, 'wpm' ) === false ) {
				?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpm-settings' ) ); ?>"><?php echo esc_html__( 'Add your languages & Customize your settings &rarr;', 'wp-multilang');?> </a>
				<?php
				}
				?>
			</p>
		</div>';
		<?php
		// Delete transient
		delete_transient( 'wpm_admin_notice_activation' );
	}
}
add_action( 'admin_notices', 'wpm_activation_admin_notices' );


/* * BFCM Banner Integration
 * Loads assets from assets/css and assets/js
 */
add_action('admin_enqueue_scripts', 'wp_multilang_enqueue_bfcm_assets');

function wp_multilang_enqueue_bfcm_assets($hook) { 
 
    //var_dump($hook);
    if ( $hook !== 'toplevel_page_wpm-settings' ) {
        return;
    }
    
    /*if ( ! isset($_GET['page']) || $_GET['page'] !== 'setting_page_check-email-dashboard' ) {
        return;
    }*/

    // 2. define settings
    $expiry_date_str = '2025-12-25 23:59:59'; 
    $offer_link      = 'https://wp-multilang.com/bfcm-2025/';

    // 3. Expiry Check (Server Side)
    if ( current_time('timestamp') > strtotime($expiry_date_str) ) {
        return; 
    }

    // 4. Register & Enqueue CSS    
    wp_enqueue_style(
        'wpm-bfcm-style', 
        plugin_dir_url(__FILE__) . 'assets/styles/bfcm-style.css', 
        array(), 
        WPM_VERSION
    );

    // 5. Register & Enqueue JS
    wp_enqueue_script(
        'wpm-bfcm-script', 
        plugin_dir_url(__FILE__) . 'assets/scripts/bfcm-script.js', 
        array('jquery'), // jQuery dependency
        WPM_VERSION, 
        true // Footer me load hoga
    );

    // 6. Data Pass (PHP to JS)
    wp_localize_script('wpm-bfcm-script', 'bfcmData', array(
        'targetDate' => $expiry_date_str,
        'offerLink'  => $offer_link
    ));
}
