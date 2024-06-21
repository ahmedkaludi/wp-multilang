<?php 
/**
 * WPM_Admin_Gutenberg_Block class
 *
 * @author   Magazine3
 * @category Admin
 * @path     admin/class-wpm-admin-gutebberg-block.php
 * @Version 2.4.9
 */

namespace WPM\Includes\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class WPM_Admin_Gutenberg_Block {
	public $blocks = array(
		'language_switcher' => array(            
                'handler'      => 'wpm-language-switcher-js-reg',                
                'local_var'    => 'wpmLanguageSwitcher',
                'block_name'   => 'language-switcher',
                'render_func'  => 'render_language_switcher',
                'editor'       => 'wpm-lang-switcher-block-editor',
                'local' 	   => array()	            
            )
	);

	/**
    * This is class constructer to use all the hooks and filters used in this class
    */
    public function __construct() {
    	foreach ($this->blocks as $key => $value) {
            $this->blocks[$key]['path'] = wpm_asset_path( 'blocks/language-switcher/build/index.js' );
        }
        
        add_action( 'enqueue_block_editor_assets', array( $this, 'register_admin_assets' ) );
        // add_action('init', array($this, 'register_wpm_blocks'));
        add_action('admin_init', array($this, 'add_editor_styles'));
        $this->register_wpm_blocks(); 
    }

    public function register_wpm_blocks(){
            
        if ( !function_exists( 'register_block_type' ) ) {
                // no Gutenberg, Abort
                return;
        }		                  		    
        
        if($this->blocks){
            
            foreach($this->blocks as $block){

                register_block_type( 'wpm/'.$block['block_name'], array(
                    'editor_style'    => $block['editor']
                ) );
                
            }
                              
        }                                        
	}

	public function register_admin_assets(){
		global $pagenow;
                    
        if ( !function_exists( 'register_block_type' ) ) {
                // no Gutenberg, Abort
                return;
        }

        if(function_exists('get_current_screen')){
            $current_screen = get_current_screen();

            if(is_object($current_screen)){
                if(!empty($current_screen->post_type) && !empty($current_screen->is_block_editor) ){
                    if(($current_screen->post_type == 'page' || $current_screen->post_type == 'post') && $current_screen->is_block_editor == 1){

                        $filename = '/assets/blocks/language-switcher/css/wpm-block-style.css';
                        $css_style_path = wpm()->plugin_url().$filename;

                        wp_register_style(
                            'wpm-lang-switcher-block-editor',
                            $css_style_path,
                            array( 'wp-edit-blocks' ),
                            WPM_VERSION
                        );
                    }
                }
            }
        }

        if($this->blocks){
                    
            foreach($this->blocks as $key => $block){                        
                
                if ( $pagenow == 'widgets.php' && version_compare( $GLOBALS['wp_version'], '5.8.0', '>=' ) ) {

                    wp_register_script(
                        $block['handler'],
                        $block['path'],
                        array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-edit-widgets' )                                 
                    );

                } else {

                    wp_register_script(
                        $block['handler'],
                        $block['path'],
                        array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-editor' )                                 
                    );
                    
                }

				if($key == 'language_switcher'){
					$block['local']['languages']   = wpm_get_languages();
					$block['local']['lang']        = wpm_get_language(); 
					$block['local']['flag_url']    = wpm_get_flag_url(); 
				}                

                wp_localize_script( $block['handler'], $block['local_var'], $block['local'] );
                         
                wp_enqueue_script( $block['handler'] );
            }
        }
	}

    /**
     * Function to add style to site editor in admin panel
     * @since 2.4.9
     * */
    public function add_editor_styles(){
        $filename = '/assets/blocks/language-switcher/css/wpm-editor-style.css';
        $css_style_path = wpm()->plugin_url().$filename;
        
        add_editor_style(
            $css_style_path
        );
    }
}
