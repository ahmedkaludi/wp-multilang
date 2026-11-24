<?php
/**
 * Black Friday / Cyber Monday Promotional Banner Class
 * All classes and IDs prefixed with wpm-
 *
 * @author   Magazine3
 * @category Admin
 * @package  WP Multilang
 * @version  2.4.36
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPM_Black_Friday_Banner {

	/**
	 * Expiry timestamp for the campaign matching adsforwp.com exactly.
	 */
	const EXPIRY_DATE = 'Dec 31, 2026 23:59:59 GMT+0530';

	/**
	 * Coupon code used in the campaign.
	 */
	const COUPON_CODE = 'BFCM2026';

	/**
	 * User meta key for tracking dismissal timestamp.
	 */
	const DISMISS_META_KEY = 'wpm_bfcm_banner_dismissed_time';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_banner' ), -99999 );
		add_action( 'wp_ajax_wpm_dismiss_black_friday_banner', array( __CLASS__, 'ajax_dismiss' ) );
	}

	/**
	 * Check if the banner should be active.
	 *
	 * @return bool
	 */
	public static function is_banner_active() {
		static $is_active = null;

		if ( null !== $is_active ) {
			return $is_active;
		}

		$current_time = time();
		$expiry_time  = strtotime( self::EXPIRY_DATE );

		// 1. Check if campaign date has passed (after 31st Dec 2026).
		if ( $current_time > $expiry_time ) {
			$is_active = false;
			return false;
		}

		// 2. Check if dismissed within the last 7 days.
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$dismissed_time = get_user_meta( $user_id, self::DISMISS_META_KEY, true );
			if ( ! empty( $dismissed_time ) ) {
				$days_passed = ( $current_time - (int) $dismissed_time ) / DAY_IN_SECONDS;
				if ( $days_passed < 7 ) {
					$is_active = false;
					return false;
				}
			}
		}

		$is_active = true;
		return true;
	}

	/**
	 * Check if current screen is the WP Multilang Settings page.
	 *
	 * @param string $hook Screen hook.
	 * @return bool
	 */
	public static function is_settings_screen( $hook = '' ) {
		if ( 'toplevel_page_wpm-settings' === $hook || 'settings_page_wpm-settings' === $hook ) {
			return true;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && ( 'toplevel_page_wpm-settings' === $screen->id || 'settings_page_wpm-settings' === $screen->id ) ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'wpm-settings' === $_GET['page'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue styles and scripts for the banner.
	 *
	 * @param string $hook Screen hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( ! self::is_settings_screen( $hook ) || ! self::is_banner_active() ) {
			return;
		}

		$suffix   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$version  = defined( 'WPM_VERSION' ) ? WPM_VERSION : '2.4.35';
		$css_file = dirname( WPM_PLUGIN_FILE ) . '/assets/styles/admin/wpm-black-friday-banner' . $suffix . '.css';
		$css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : $version;

		wp_enqueue_style(
			'wpm-bf-banner-css',
			wpm_asset_path( 'styles/admin/wpm-black-friday-banner' . $suffix . '.css' ),
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'wpm-bf-banner-js',
			wpm_asset_path( 'scripts/wpm-black-friday-banner' . $suffix . '.js' ),
			array( 'jquery' ),
			$version,
			true
		);

		wp_localize_script(
			'wpm-bf-banner-js',
			'wpm_bf_banner_data',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'action'      => 'wpm_dismiss_black_friday_banner',
				'nonce'       => wp_create_nonce( 'wpm_bf_banner_nonce' ),
				'expiry_date' => self::EXPIRY_DATE,
				'coupon_code' => self::COUPON_CODE,
			)
		);
	}

	/**
	 * AJAX handler to record banner dismissal for 7 days.
	 */
	public static function ajax_dismiss() {
		check_ajax_referer( 'wpm_bf_banner_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_translations' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'wp-multilang' ) ), 403 );
		}

		$user_id = get_current_user_id();
		if ( $user_id ) {
			update_user_meta( $user_id, self::DISMISS_META_KEY, time() );
			wp_send_json_success( array( 'message' => esc_html__( 'Banner dismissed for 7 days.', 'wp-multilang' ) ) );
		}

		wp_send_json_error( array( 'message' => esc_html__( 'Failed to record dismissal.', 'wp-multilang' ) ) );
	}

	/**
	 * Render the Black Friday / Cyber Monday promotional banner HTML.
	 */
	public static function render_banner() {
		static $rendered = false;
		if ( $rendered ) {
			return;
		}

		if ( ! self::is_settings_screen() || ! self::is_banner_active() ) {
			return;
		}

		$rendered = true;

		$current_time = time();
		$expiry_time  = strtotime( self::EXPIRY_DATE );
		$diff         = max( 0, $expiry_time - $current_time );

		$days    = str_pad( floor( $diff / DAY_IN_SECONDS ), 2, '0', STR_PAD_LEFT );
		$hours   = str_pad( floor( ( $diff % DAY_IN_SECONDS ) / HOUR_IN_SECONDS ), 2, '0', STR_PAD_LEFT );
		$minutes = str_pad( floor( ( $diff % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ), 2, '0', STR_PAD_LEFT );
		$seconds = str_pad( floor( $diff % MINUTE_IN_SECONDS ), 2, '0', STR_PAD_LEFT );

		$pricing_url = esc_url( 'https://wp-multilang.com/bfcm-2026/' );
		?>
		<div class="wrap wpm-bf-banner-container">
			<div id="wpm-bf-discount-bar" class="wpm-bf-discount-bar wpm-bf-theme-universal-luxury" role="region" aria-label="<?php esc_attr_e( 'Black Friday 60% Discount Banner', 'wp-multilang' ); ?>">
				<input id="wpm-bf-coupon-expiry" type="hidden" value="<?php echo esc_attr( self::EXPIRY_DATE ); ?>">
				<button type="button" class="wpm-bf-bar-close-btn" aria-label="<?php esc_attr_e( 'Dismiss Black Friday banner for 7 days', 'wp-multilang' ); ?>">&times;</button>
				
				<div class="wpm-bf-bar-inner">
					<!-- Zone 1: Offer Text & 60% Hook -->
					<div class="wpm-bf-col-offer">
						<div class="wpm-bf-urgency-row">
							<span class="wpm-bf-urgency-pill">
								<span class="wpm-bf-pulse-dot"></span>
								⚡ <?php esc_html_e( 'BLACK FRIDAY / CYBER MONDAY / CHRISTMAS SALE', 'wp-multilang' ); ?>
							</span>
						</div>
						<div class="wpm-bf-headline-group">
							<div class="wpm-bf-badge-60">
								<span class="wpm-bf-num"><?php esc_html_e( '60%', 'wp-multilang' ); ?></span>
								<span class="wpm-bf-off"><?php esc_html_e( 'OFF', 'wp-multilang' ); ?></span>
							</div>
							<div class="wpm-bf-text-details">
								<div class="wpm-bf-main-title"><?php esc_html_e( 'BIGGEST SALE IN OUR HISTORY', 'wp-multilang' ); ?> <span class="wpm-bf-highlight-gold"><?php esc_html_e( '— NEVER BEFORE SEEN!', 'wp-multilang' ); ?></span></div>
								<div class="wpm-bf-sub-title"><?php esc_html_e( 'Sale is here! 60% off on Everything for a very limited time!!!', 'wp-multilang' ); ?></div>
							</div>
							<div class="wpm-bf-coupon-badge" title="<?php esc_attr_e( 'Click to copy coupon code', 'wp-multilang' ); ?>">
								<span class="wpm-bf-coupon-label"><?php esc_html_e( 'CODE:', 'wp-multilang' ); ?></span>
								<strong class="wpm-bf-coupon-code"><?php echo esc_html( self::COUPON_CODE ); ?></strong>
								<span class="wpm-bf-copy-icon">📋</span>
								<span class="wpm-bf-copy-tooltip"><?php esc_html_e( 'Copied!', 'wp-multilang' ); ?></span>
							</div>
						</div>
					</div>

					<!-- Zone 2: High-Urgency Countdown Clock -->
					<div class="wpm-bf-col-clock">
						<div class="wpm-bf-clock-header">
							<span>⏳ <?php esc_html_e( 'OFFER EXPIRES IN:', 'wp-multilang' ); ?></span>
						</div>
						<div id="wpm-bf-clockdiv" class="wpm-bf-clockdiv" aria-live="polite">
							<div class="wpm-bf-clock-unit">
								<span class="wpm-bf-clock-num wpm-bf-days"><?php echo esc_html( $days ); ?></span>
								<span class="wpm-bf-clock-label"><?php esc_html_e( 'Days', 'wp-multilang' ); ?></span>
							</div>
							<span class="wpm-bf-clock-sep">:</span>
							<div class="wpm-bf-clock-unit">
								<span class="wpm-bf-clock-num wpm-bf-hours"><?php echo esc_html( $hours ); ?></span>
								<span class="wpm-bf-clock-label"><?php esc_html_e( 'Hours', 'wp-multilang' ); ?></span>
							</div>
							<span class="wpm-bf-clock-sep">:</span>
							<div class="wpm-bf-clock-unit">
								<span class="wpm-bf-clock-num wpm-bf-minutes"><?php echo esc_html( $minutes ); ?></span>
								<span class="wpm-bf-clock-label"><?php esc_html_e( 'Mins', 'wp-multilang' ); ?></span>
							</div>
							<span class="wpm-bf-clock-sep">:</span>
							<div class="wpm-bf-clock-unit">
								<span class="wpm-bf-clock-num wpm-bf-seconds"><?php echo esc_html( $seconds ); ?></span>
								<span class="wpm-bf-clock-label"><?php esc_html_e( 'Secs', 'wp-multilang' ); ?></span>
							</div>
						</div>
					</div>

					<!-- Zone 3: High-Converting CTA Button -->
					<div class="wpm-bf-col-cta">
						<a class="wpm-bf-bar-btn" href="<?php echo esc_url( $pricing_url ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="wpm-bf-btn-text"><?php esc_html_e( 'Claim The Offer Now', 'wp-multilang' ); ?></span>
							<span class="wpm-bf-btn-icon">⚡</span>
						</a>
						<span class="wpm-bf-btn-sub">🔒 <?php esc_html_e( '30 Days Money Back Guarantee', 'wp-multilang' ); ?></span>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}

// Initialize banner hooks.
WPM_Black_Friday_Banner::init();
