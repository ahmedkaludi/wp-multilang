/**
 * This script is only for Autolink Manager plugin page layout compatibility
 */
(function ($) {
  "use strict";

  $(function () {
    if ($('#wpm-language-switcher').length === 0) {
      var language_switcher = wp.template('wpm-ls');
      $('#wpbody-content').prepend(language_switcher);
      var switcher = $('#wpm-language-switcher');
      if (switcher.length > 0) {
        switcher.css({
          'position': 'static',
          'margin-bottom': '20px'
        });
      }
    }
  });
})(jQuery);
