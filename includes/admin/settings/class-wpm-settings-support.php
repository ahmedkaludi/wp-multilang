<?php
/**
 * WP Multilang Support Settings
 *
 * @category    Admin
 * @package     WPM/Admin
 * @author   Valentyn Riaboshtan
 */

namespace WPM\Includes\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WPM_Settings_Support.
 */
class WPM_Settings_Support extends WPM_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'support';
		$this->label = __( 'Help & Support', 'wp-multilang' );

		parent::__construct();
	}

	/**
	 * Output the settings.
	 */
	public function output() {

		$GLOBALS['hide_save_button'] = true;

		$main_params = array(
			'plugin_url'                 => wpm()->plugin_url(),
			'ajax_url'                   => admin_url( 'admin-ajax.php' ),
			'support_nonce'  => wp_create_nonce( 'support-localization' ),
		);
		wp_localize_script( 'wpm_support_settings', 'wpm_support_settings_params', $main_params );
		wp_enqueue_script( 'wpm_support_settings' );

		?>
		<div class="wpm-support-container">
			<p><?php echo esc_html_e('If you have any query, please write the query in below box or email us at', 'wp-multilang') ?> <a href="mailto:team@magazine3.in"><?php echo esc_html_e('team@magazine3.in'); ?></a>. <?php echo esc_html_e('We will reply to your email address shortly', 'wp-multilang') ?></p>

			<div class="wpm-support-div-form">
	            <ul>
	                <li>
	                  <label class="wpm-support-label"><?php echo esc_html_e('Email', 'wp-multilang') ?><span class="wpm-star-mark">*</span></label>
	                   <div class="support-input">
	                      <input type="text" id="wp_query_email" name="wp_query_email" size="47" placeholder="Enter your Email" required="">
	                   </div>
	                </li>
	                <li>
	                    <label class="wpm-support-label"><?php echo esc_html_e('Query', 'wp-multilang') ?><span class="wpm-star-mark">*</span></label>  
	                    <div class="support-input"><textarea rows="5" cols="50" id="wpm_query_message" name="wpm_query_message" placeholder="Write your query"></textarea>
	                    </div>
	                </li>
	                <li><button class="button button-primary" id="wpm-send-support-query"><?php echo esc_html_e('Send Support Request', 'wp-multilang') ?></button></li>
	            </ul>            
	            <div class="clear"> </div>
                <span class="wpm-query-success wpm-hide"><?php echo esc_html_e('Message sent successfully, Please wait we will get back to you shortly', 'wp-multilang') ?></span>
                <span class="wpm-query-error wpm-hide"><?php echo esc_html_e('Message not sent. please check your network connection', 'wp-multilang') ?></span>
	        </div>
		</div>
		<?php
	}
}
