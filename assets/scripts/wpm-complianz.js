/**
 * Language switcher handling for Complianz GDPR admin pages
 *
 * @package WP Multilang
 * @since 2.4.35
 */
(function ($) {
  "use strict";

  $(function () {
    // Check if Complianz admin container exists and language switcher is not already added
    if ($('#wpm-language-switcher').length === 0 && typeof wp !== 'undefined' && wp.template && $('#tmpl-wpm-ls').length) {
      var language_switcher = $(wp.template('wpm-ls')());
      var $complianz = $('#complianz');

      // Apply isolated inline layout styles directly to this instance
      language_switcher.css({
        'position': 'relative',
        'top': 'auto',
        'left': 'auto',
        'right': 'auto',
        'margin': '15px 20px 15px 0',
        'z-index': '99',
        'display': 'inline-block'
      });

      if ($complianz.length > 0) {
        $complianz.before(language_switcher);
      } else if ($('#wpbody-content').length > 0) {
        $('#wpbody-content').prepend(language_switcher);
      }
    }

    // Preserve URL hash (e.g. #banner, #wizard, #settings) when switching languages
    $(document).on('click', '#wpm-language-switcher a', function () {
      if (window.location.hash && this.href && this.href.indexOf('#') === -1) {
        this.href += window.location.hash;
      }
    });
  });
})(jQuery, wp);
