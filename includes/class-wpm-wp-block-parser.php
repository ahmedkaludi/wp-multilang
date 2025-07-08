<?php
namespace WPM\Includes;

/** 
 * Class to parse the navigation menu
 * @since 2.4.20
*/
class My_Custom_Block_Parser extends \WP_Block_Parser {
    
	/**
	 * Modify the parse function and translate the content if it contains navigation
	 * @param $content string
	 * @return $content string
	 * @since 2.4.20
	 */  
    public function parse($content) {

        if (str_contains($content, '<!-- wp:navigation')) {
            if ( is_string($content) ) {
                $content = wpm_translate_string($content);
            }
        }

        // Always return parsed blocks
        return parent::parse($content);
    }
}