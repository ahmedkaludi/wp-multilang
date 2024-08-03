<?php
/**
 * Admin View: Settings
 *
 * @package wpm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab_exists        = isset( $tabs[ $current_tab ] ) || has_action( 'wpm_sections_' . $current_tab ) || has_action( 'wpm_settings_' . $current_tab ) || has_action( 'wpm_settings_tabs_' . $current_tab );
$current_tab_label = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';

if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'options-general.php?page=wpm-settings' ) );
	exit;
}
?>
<div class="wrap wpm">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form method="<?php echo esc_attr( apply_filters( 'wpm_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
		<nav class="nav-tab-wrapper wpm-nav-tab-wrapper">
			<?php

			foreach ( $tabs as $slug => $label ) {
				if($slug == 'upgradetopro'){
					echo '<a href="https://wp-multilang.com/pricing/" class="nav-tab wpm-upgrade-pro-btn ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '" class="wpm-upgrade-to-pro" style="background-color: #0099E7; color:#fff; border-color: #0099E7; font-weight: 500;" target="_blank">' . esc_html( $label ) . '</a>';
				}else{
					echo '<a href="' . esc_html( admin_url( 'options-general.php?page=wpm-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
				}
			}

			do_action( 'wpm_settings_tabs' );

			?>
		</nav>
		<h2 class="screen-reader-text"><?php echo esc_html( $current_tab_label ); ?></h2>
		<?php
			do_action( 'wpm_sections_' . $current_tab );

			self::show_messages();

			do_action( 'wpm_settings_' . $current_tab );
		?>
		<p class="submit">
			<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
				<?php 
				echo '<style>.submit{float:left;}</style>';
				submit_button(); 
				if(!defined('WP_MULTILANG_PRO_VERSION')){
					echo '<a class="button wpm-upgrade-pro-btn" style="background: #0099E7;color: #fff; border-color: #0099E7; font-weight: 500; margin: 30px 0px 0px 25px;" href="https://wp-multilang.com/pricing/" target="_blank">'.esc_html__( 'Upgrade to PRO', 'wp-multilang').'</a>';
				}
				?>
			<?php endif; ?>
			<?php wp_nonce_field( 'wpm-settings' ); ?>
		</p>

	</form>

	<?php wpm_newsletter_form(); ?>

</div>

<?php
/**
 * Display newsletter form on settings page
 * */
function wpm_newsletter_form(){
	
	$hide_form = get_option('wpm_hide_newsletter');

	// Newsletter marker. Set this to false once newsletter subscription is displayed.
		$multilang_newsletter = true;

	if ( $multilang_newsletter === true && $hide_form !== 'yes') { ?>
	  <div class="wpm-newsletter-wrapper">
		<div class="plugin-card plugin-card-wpm-newsletter" style="color: #fff; background: #0099E7 url('<?php echo esc_attr( wpm_asset_path('img/email.png' ) ); ?>') no-repeat right top;">
						
					<div class="plugin-card-top" style="min-height: 135px;">
					     <span class="dashicons dashicons-dismiss wpm_newsletter_hide" style="float: right;cursor: pointer;"></span>
					    <span style="clear:both;"></span>
						<div class="name column-name" style="margin: 0px 10px;">
							<h3 style="color: #fff;"><?php esc_html_e( 'WP Multilang Newsletter', 'wp-multilang' ); ?></h3>
						</div>
						<div class="column-description" style="margin: 0px 10px;">
							<p><?php esc_html_e( 'Learn more about WP Multilang and get latest updates', 'wp-multilang' ); ?></p>
						</div>
						
						<div class="wpm-newsletter-form" style="margin: 18px 10px 0px;">
						
							<form method="post" action="https://wp-multilang.com/newsletter" target="_blank" id="wpm_settings_newsletter">
								<fieldset>
									<input name="newsletter-email" value="<?php $user = wp_get_current_user(); echo esc_attr( $user->user_email ); ?>" placeholder="<?php esc_html_e( 'Enter your email', 'wp-multilang' ); ?>" style="width: 60%; margin-left: 0px;" type="email">		
									<input name="source" value="wpmultilang-plugin" type="hidden">
									<input type="submit" class="button" value="<?php esc_html_e( 'Subscribe', 'wp-multilang' ); ?>" style="background: linear-gradient(to right, #174e6a, #05161f) !important; box-shadow: unset; color: #fff;">
									<span class="wpm_newsletter_hide" style="box-shadow: unset;cursor: pointer;margin-left: 10px;">
									<?php esc_html_e( 'No thanks', 'wp-multilang' ); ?>
									</span>
									<small style="display:block; margin-top:8px;"><?php esc_html_e( 'We\'ll share our <code>root</code> password before we share your email with anyone else.', 'wp-multilang' ); ?></small>
									
								</fieldset>
							</form>
							
						</div>
						
					</div>
								
				</div>
		</div>
	<?php }
			// Set newsletter marker to false
			  $multilang_newsletter = false;
}