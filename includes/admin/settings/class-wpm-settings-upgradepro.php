<?php
/**
 * WP Multilang Additional Settings
 *
 * @category    Admin
 * @package     WPM/Admin
 * @author   Magazine3
 * @since 1.0
 */

namespace WPM\Includes\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WPM_Settings_Upgrade_Pro.
 */
class WPM_Settings_Upgrade_Pro extends WPM_Settings_Page {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'upgradetopro';
		$this->label = esc_html__( 'Upgrade to Pro', 'wp-multilang' );

		parent::__construct();
	}
}