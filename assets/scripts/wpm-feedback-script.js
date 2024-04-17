var strict;

jQuery(document).ready(function ($) {
    /**
     * DEACTIVATION FEEDBACK FORM
     */
    // show overlay when clicked on "deactivate"
    wpm_deactivate_link = $('.wp-admin.plugins-php tr[data-slug="wp-multilang"] .row-actions .deactivate a');
    wpm_deactivate_link_url = wpm_deactivate_link.attr('href');

    wpm_deactivate_link.click(function (e) {
        e.preventDefault();
        // only show feedback form once per 30 days
        var c_value = wpm_admin_get_cookie("wpm_hide_deactivate_feedback");

        if (c_value === undefined) {
            $('#wpm-reloaded-feedback-overlay').show();
        } else {
            // click on the link
            window.location.href = wpm_deactivate_link_url;
        }
    });
    // show text fields
    $('#wpm-reloaded-feedback-content input[type="radio"]').click(function () {
        // show text field if there is one
        var inputValue = $(this).attr("value");
        var targetBox = $("." + inputValue);
        $(".mb-box").not(targetBox).hide();
        $(targetBox).show();
    });
    // send form or close it
    $('#wpm-reloaded-feedback-content .button').click(function (e) {
        e.preventDefault();
        // set cookie for 30 days
        var exdate = new Date();
        exdate.setSeconds(exdate.getSeconds() + 2592000);
        document.cookie = "wpm_hide_deactivate_feedback=1; expires=" + exdate.toUTCString() + "; path=/";

        $('#wpm-reloaded-feedback-overlay').hide();
        if ('wpm-reloaded-feedback-submit' === this.id) {
            // Send form data
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: 'wpm_send_feedback',
                    data: $('#wpm-reloaded-feedback-content form').serialize()
                },
                complete: function (MLHttpRequest, textStatus, errorThrown) {
                    // deactivate the plugin and close the popup
                    $('#wpm-reloaded-feedback-overlay').remove();
                    window.location.href = wpm_deactivate_link_url;

                }
            });
        } else {
            $('#wpm-reloaded-feedback-overlay').remove();
            window.location.href = wpm_deactivate_link_url;
        }
    });
    // close form without doing anything
    $('.wpm-feedback-not-deactivate').click(function (e) {
        $('#wpm-reloaded-feedback-overlay').hide();
    });
    
    function wpm_admin_get_cookie (name) {
    var i, x, y, wpm_cookies = document.cookie.split( ";" );
    for (i = 0; i < wpm_cookies.length; i++)
    {
        x = wpm_cookies[i].substr( 0, wpm_cookies[i].indexOf( "=" ) );
        y = wpm_cookies[i].substr( wpm_cookies[i].indexOf( "=" ) + 1 );
        x = x.replace( /^\s+|\s+$/g, "" );
        if (x === name)
        {
            return unescape( y );
        }
    }
}

}); // document ready