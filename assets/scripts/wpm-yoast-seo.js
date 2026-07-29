/**
 * @since 2.4.31
 * */
(function ($) {
  "use strict";

  $(function () {
    if ($('#wpm-language-switcher').length === 0) {
      var language_switcher = wp.template('wpm-ls');
      $('#wpbody-content').prepend(language_switcher);
      document.getElementById('wpm-language-switcher').style.setProperty('position', 'static', 'important');
    }
  });
})(jQuery, wp);
