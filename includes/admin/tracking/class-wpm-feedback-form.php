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
        $current_user   =   wp_get_current_user();
        $uninstall_opt  =   get_option( 'wpm_uninstall_translations', 'no' );
        ?>

        <div id="wpm-reloaded-feedback-overlay" style="display: none;">
            <div id="wpm-reloaded-feedback-content">
            <form action="" method="post">
                <p><strong><?php echo esc_html__( 'Note: ', 'wp-multilang' ); ?></strong><?php echo esc_html__( 'Because the plugin stores data in different ways, WP Multilang may display unnecessary data if you uninstall and delete it without enabling the "Delete Translation" option. If it is a temporary deactivation, you can proceed with deactivation only', 'wp-multilang' ) ?></p>
                <p><?php echo esc_html__( 'Below are a few document links that will guide you on how to clean up data during a permanent uninstallation and deletion of WP Multilang plugin', 'wp-multilang' ); ?></p>
                <ol>
                    <li>
                        <strong><a href="https://wp-multilang.com/docs/knowledge-base/how-to-uninstall-wp-multilang-plugin-properly/" target="_blank"><?php echo esc_html__( 'How to uninstall WP-Multilang plugin properly?', 'wp-multilang' ); ?></a></strong>
                    </li>
                    <li>
                        <strong><a href="https://wp-multilang.com/docs/knowledge-base/my-website-broke-after-deactivating-wp-multilang-how-to-fix-it/" target="_blank"><?php echo esc_html__( 'My website broke after deactivate, how to fix it?', 'wp-multilang' ); ?></a></strong>
                    </li>
                </ol>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <td>
                                <input name="wpm_uninstall_translations" id="wpm_uninstall_translations" type="checkbox" value="1" <?php checked( $uninstall_opt, 'yes' ); ?> />
                                <label for="wpm_uninstall_translations" class="wpm-cursor-pointer"><strong><?php echo esc_html__( 'Delete Translations', 'wp-multilang' ); ?></strong> <?php echo esc_html__( ' - Enabling this option will delete all the translation.', 'wp-multilang'); ?></label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <input id="wpm-reloaded-feedback-submit" class="button button-primary" type="submit" name="wpm_disable_submit" value="<?php esc_html_e('Submit & Deactivate', 'wp-multilang'); ?>"/>
                <a class="button"><?php esc_html_e('Only Deactivate', 'wp-multilang'); ?></a>
                <a class="wpm-feedback-not-deactivate" href="#"><?php esc_html_e('Don\'t deactivate', 'wp-multilang'); ?></a>
                <?php wp_nonce_field( 'wpm_deactivate_plugin_nonce', 'wpm_deactivate_plugin_nonce' );   ?>
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