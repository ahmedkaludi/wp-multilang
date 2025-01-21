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
        $('#wpm-reloaded-feedback-overlay').show();

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

        $('#wpm-reloaded-feedback-overlay').hide();
        if ('wpm-reloaded-feedback-submit' === this.id) {
            // Send form data
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: 'wpm_deactivate_plugin',
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

}); // document ready