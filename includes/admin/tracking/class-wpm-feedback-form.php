<?php
namespace WPM\Includes\Admin;

// Exit if accessed directly
if( !defined( 'ABSPATH' ) )
    exit;

/**
 * WPM_Deactivate_Feedback_Form Class.
 * @since 2.4.6q
 */

class WPM_Deactivate_Feedback_Form{

    /**
     * Constructor trigger hooks
     * */
    public function __construct()
    {
        if( is_admin() && $this->wpm_is_plugins_page()) {
            add_filter( 'admin_footer', array($this, 'wpm_add_deactivation_feedback_modal' ) );
            add_action( 'admin_enqueue_scripts', array($this, 'wpm_enqueue_deactivation_feedback_js' ));
        } 
    }

    /**
     * Check is current page is a plugin page
     * @since 2.4.6
     * */
    public function wpm_is_plugins_page()
    {
        global $pagenow;

        return ( 'plugins.php' === $pagenow );
    }

    /**
     * Feedback form modal template
     * @since 2.4.6
     * */
    public function wpm_add_deactivation_feedback_modal()
    {
        $current_user = wp_get_current_user();
        $email = '';
        if(is_object($current_user) && isset($current_user->user_email)){
            $email = trim( $current_user->user_email );
        }

        $reasons = array(
            1 => '<li><label><input type="radio" name="wpm_disable_reason" value="temporary"/>' . esc_html__('It is only temporary', 'wp-multilang') . '</label></li>',
            2 => '<li><label><input type="radio" name="wpm_disable_reason" value="stopped"/>' . esc_html__('I stopped using WP Multilang plugin on my site', 'wp-multilang') . '</label></li>',
            3 => '<li><label><input type="radio" name="wpm_disable_reason" value="missing"/>' . esc_html__('I miss a feature', 'wp-multilang') . '</label></li>
            <li><input class="mb-box missing" type="text" name="wpm_disable_text[]" value="" placeholder="' .esc_attr__( 'Please describe the feature', 'wp-multilang') . '"/></li>',
            4 => '<li><label><input type="radio" name="wpm_disable_reason" value="technical"/>' . esc_html__('Technical Issue', 'wp-multilang') . '</label></li>
            <li><textarea class="mb-box technical" name="wpm_disable_text[]" placeholder="' . esc_attr__('How Can we help? Please describe your problem', 'wp-multilang') . '"></textarea></li>',
            5 => '<li><label><input type="radio" name="wpm_disable_reason" value="another plugin"/>' . esc_html__('I switched to another plugin', 'wp-multilang') .  '</label></li>
            <li><input class="mb-box another" type="text" name="wpm_disable_text[]" value="" placeholder="' .esc_attr__( 'Name of the plugin', 'wp-multilang' ) . '"/></li>',
            6 => '<li><label><input type="radio" name="wpm_disable_reason" value="other"/>' . esc_html__('Other reason', 'wp-multilang') . '</label></li>
            <li><textarea class="mb-box other" name="wpm_disable_text[]" placeholder="' . esc_attr__('Please specify, if possible', 'wp-multilang') . '"></textarea></li>',
        );
        shuffle($reasons);

        ?>

        <div id="wpm-reloaded-feedback-overlay" style="display: none;">
            <div id="wpm-reloaded-feedback-content">
            <form action="" method="post">
                <h3><strong><?php esc_html_e('If you have a moment, please let us know why you are deactivating:', 'wp-multilang'); ?></strong></h3>
                <ul>
                        <?php 
                        foreach ($reasons as $reason_escaped){
                            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all html inside this variable already escaped above in $reasons variable
                            echo $reason_escaped;
                        }
                        ?>
                </ul>
                <?php if ($email) : ?>
                    <input type="hidden" name="wpm_disable_from" value="<?php echo esc_attr($email); ?>"/>
                <?php endif; ?>
                <input id="wpm-reloaded-feedback-submit" class="button button-primary" type="submit" name="wpm_disable_submit" value="<?php esc_html_e('Submit & Deactivate', 'wp-multilang'); ?>"/>
                <a class="button"><?php esc_html_e('Only Deactivate', 'wp-multilang'); ?></a>
                <a class="wpm-feedback-not-deactivate" href="#"><?php esc_html_e('Don\'t deactivate', 'wp-multilang'); ?></a>
                <?php wp_nonce_field( 'wpm_feedback_nonce', 'wpm_feedback_nonce' );   ?>
            </form>
            </div>
        </div>

        <?php
    }

    /**
     * Load css and js files 
     * @since 2.4.6
     * */
    public function wpm_enqueue_deactivation_feedback_js(){
        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        $main_params = array(
            'plugin_url'                 => wpm()->plugin_url(),
            'ajax_url'                   => admin_url( 'admin-ajax.php' ),
            'feedback_nonce'             => wp_create_nonce( 'feedback-localization' ),
        );
        wp_register_script( 'wpm-feedback-script', wpm_asset_path( 'scripts/wpm-feedback-script' . $suffix . '.js' ), array( 'jquery' ), WPM_VERSION, true );
        wp_localize_script( 'wpm-feedback-script', 'wpm_feedback_params', $main_params );
        wp_enqueue_script( 'wpm-feedback-script' );

        wp_enqueue_style( 'wpm-feedback-css', wpm_asset_path( 'styles/admin/wpm-feedback-style' . $suffix . '.css' ), array(), WPM_VERSION );
    }
}