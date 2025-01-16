/**
 * This script is only for forminator plugin
 * @since 2.4.16
 * */
(function ($) {
  "use strict";

  $(function () {
    if ($('#wpm-language-switcher').length === 0) {
      var language_switcher = wp.template('wpm-ls');
      if ( $('#wpbody-content').length > 0 ) {
        	// This change is for Forminator plugin as .wrap class is not present inside #wpbody-content id
        	$('#wpbody-content').prepend(language_switcher);
      }
    }
  });
})(jQuery, wp);