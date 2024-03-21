<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * Update widget translation. Title and text field translate for all widgets.
 *
 * @param $instance
 * @param $new_instance
 * @param $old_instance
 * @param $widget
 *
 * @return array
 * @since 2.4.5
 */
add_action( 'widget_update_callback', 'wpm_translate_widget_block_content', 999, 4 );

function wpm_translate_widget_block_content( $instance, $new_instance, $old_instance, $widget ) {
        
    $widget_config = wpm_get_widget_config( $widget->id_base );

    if ( null === $widget_config ) {
      return $instance;
    }

    $instance = wpm_set_new_value( $old_instance, $new_instance, $widget_config );        

    return $instance;
}


/**
 * Update translated text in widget block editor
 * @since 2.4.5
 */
add_filter('rest_prepare_widget', 'wpm_translate_widget_block_in_editor',10,3);

function wpm_translate_widget_block_in_editor($response, $widget, $request){
  if(is_object($response) && isset($response->data)){
    $data = $response->data;
    if(is_array($data)){
      	if(isset($data['instance']) && isset($data['instance']['raw']) && isset($data['instance']['raw']['content'])){	
	    	$content = $data['instance']['raw']['content'];
	    	$content = wpm_translate_value($content);  
	    	$response->data['instance']['raw']['content'] = $content;
	    }

    }

  }
  return $response;  
}

add_action('enqueue_block_editor_assets', 'add_language_switcher_to_widget');
function add_language_switcher_to_widget()
{
	$screen    = get_current_screen();
	if(is_object($screen) && isset($screen->id)){
		$screen_id = $screen->id;
		if($screen_id == 'widgets'){
			$script = "
				(function( $ ) {
					$(window).on('pageshow',function(){
	                    if ($('#wpm-language-switcher').length === 0) {
	                        var language_switcher = wp.template( 'wpm-ls-customizer' );
	                        if($('.edit-widgets-header__title').length > 0 ){
	                        	$('.edit-widgets-header__title').before(language_switcher);
	                        }
	                    }
	                });
				})( jQuery );
			";
			wp_add_inline_script( 'wp-edit-widgets', $script );
			add_action( 'admin_footer', 'wpm_admin_language_switcher_customizer' );
		}
	}
}
