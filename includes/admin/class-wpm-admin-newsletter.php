<?php 
/**
 * Newsletter class
 *
 * @author   Magazine3
 * @category Admin
 * @path     admin_section/newsletter
 * @Version 1.1
 */

namespace WPM\Includes\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class WPM_Admin_Newsletter {
        
	public function __construct () {
                add_filter( 'wpm_localize_filter',array($this,'wpm_add_localize_footer_data'),10,2);
                add_action( 'admin_enqueue_scripts', array($this, 'wpm_enqueue_newsletter_js') );
        }

        /**
        * Load css and js files 
        * @since 2.4.6
        * */
        public function wpm_enqueue_newsletter_js(){
                $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

                $script_data = array(
                        'using_wpm'         => esc_html__( 'Thanks for using WP Multilang!', 'wp-multilang' ),
                        'do_you_want'       => esc_html__( 'Do you want the latest on ', 'wp-multilang'  ),
                        'wpm_update'        => esc_html__( 'WP Multilang update', 'wp-multilang'  ),
                        'before_others'     => esc_html__( ' before others and some best resources on monetization in a single email? - Free just for users of WP Multilang!', 'wp-multilang'  ),
                        'wpm_security_nonce'    => wp_create_nonce( 'wpm_security_nonce' ),
                        'ajax_url'              => admin_url( 'admin-ajax.php' )
                );

                $script_data = apply_filters('wpm_localize_filter',$script_data,'wpm_localize_data');

                wp_register_script( 'wpm-newsletter-script', wpm_asset_path( 'scripts/wpm-newsletter-script' . $suffix . '.js' ), array( 'jquery' ), WPM_VERSION, true );
                wp_localize_script( 'wpm-newsletter-script', 'wpm_localize_data', $script_data );
                wp_enqueue_script( 'wpm-newsletter-script' );
        }
                
        public function wpm_add_localize_footer_data($object, $object_name){
            
        $dismissed = explode (',', get_user_meta (wp_get_current_user()->ID, 'dismissed_wp_pointers', true));
        $do_tour   = !in_array ('wpm_subscribe_pointer', $dismissed);
        
        if ($do_tour) {
                wp_enqueue_style ('wp-pointer');
                wp_enqueue_script ('wp-pointer');						
	}
                        
        if($object_name == 'wpm_localize_data'){
                        
                global $current_user;                
		$tour     = array ();
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Reason unslash not needed because data is not getting stored in database, it's just being used. -- Reason unslash not needed because data is not getting stored in database, it's just being used. 
                $tab      = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';                   
                
                if (!array_key_exists($tab, $tour)) {                
			                                           			            	
                        $object['do_tour']            = $do_tour;        
                        $object['get_home_url']       = get_home_url();                
                        $object['current_user_email'] = $current_user->user_email;                
                        $object['current_user_name']  = $current_user->display_name;        
			$object['displayID']          = '#menu-settings';                        
                        $object['button1']            = esc_html__('No Thanks', 'wp-multilang');
                        $object['button2']            = false;
                        $object['function_name']      = '';                        
		}
		                                                                                                                                                    
        }
        return $object;    
    }  
}