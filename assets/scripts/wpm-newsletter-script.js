jQuery(document).ready(function($){
	if(wpm_localize_data.do_tour){
   	var content = '<h3>'+wpm_localize_data.using_wpm+'</h3>';
        content += '<p>'+wpm_localize_data.do_you_want+' <b>'+wpm_localize_data.wpm_update+'</b> '+wpm_localize_data.before_others+'</p>';
        content += '<style type="text/css">';
        content += '.wp-pointer-buttons{ padding:0; overflow: hidden; }';
        content += '.wp-pointer-content .button-secondary{  left: -25px;background: transparent;top: 5px; border: 0;position: relative; padding: 0; box-shadow: none;margin: 0;color: #0085ba;} .wp-pointer-content .button-primary{ display:none}  #wpm_mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif; }';
        content += '</style>';                        
        content += '<div id="wpm_mc_embed_signup">';
        content += '<form method="POST" accept-charset="utf-8" id="wpm-news-letter-form">';
        content += '<div id="wpm_mc_embed_signup_scroll">';
        content += '<div class="wpm-mc-field-group" style="    margin-left: 15px;    width: 195px;    float: left;">';
        content += '<input type="text" name="wpm_subscriber_name" class="form-control" placeholder="Name" hidden value="'+wpm_localize_data.current_user_name+'" style="display:none">';
        content += '<input type="text" value="'+wpm_localize_data.current_user_email+'" name="wpm_subscriber_email" class="form-control" placeholder="Email*"  style="      width: 180px;    padding: 6px 5px;">';                        
        content += '<input type="text" name="wpm_subscriber_website" class="form-control" placeholder="Website" hidden style=" display:none; width: 168px; padding: 6px 5px;" value="'+wpm_localize_data.get_home_url+'">';
        content += '<input type="hidden" name="ml-submit" value="1" />';
        content += '</div>';
        content += '<div id="mce-responses">';                                                
        content += '</div>';
        content += '<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_a631df13442f19caede5a5baf_c9a71edce6" tabindex="-1" value=""></div>';
        content += '<input type="submit" value="Subscribe" name="subscribe" id="pointer-close" class="button mc-newsletter-sent" style=" background: #0085ba; border-color: #006799; padding: 0px 16px; text-shadow: 0 -1px 1px #006799,1px 0 1px #006799,0 1px 1px #006799,-1px 0 1px #006799; height: 30px; margin-top: 1px; color: #fff; box-shadow: 0 1px 0 #006799;">';
        content += '<p id="wpm-news-letter-status"></p>';
        content += '</div>';
        content += '</form>';
        content += '</div>';

	    $(document).on("submit", "#wpm-news-letter-form", function(e){
	      e.preventDefault(); 
	      
	      var $form = $(this),
	      name = $form.find('input[name="wpm_subscriber_name"]').val(),
	      email = $form.find('input[name="wpm_subscriber_email"]').val();
	      website = $form.find('input[name="wpm_subscriber_website"]').val();                          
	      
	      $.post(wpm_localize_data.ajax_url,
	                 {action:'wpm_subscribe_to_news_letter',
	                 wpm_security_nonce:wpm_localize_data.wpm_security_nonce,
	                 name:name, email:email, website:website },
	        function(data) {
	          
	            if(data)
	            {
	              if(data=="Some fields are missing.")
	              {
	                $("#wpm-news-letter-status").text("");
	                $("#wpm-news-letter-status").css("color", "red");
	              }
	              else if(data=="Invalid email address.")
	              {
	                $("#wpm-news-letter-status").text("");
	                $("#wpm-news-letter-status").css("color", "red");
	              }
	              else if(data=="Invalid list ID.")
	              {
	                $("#wpm-news-letter-status").text("");
	                $("#wpm-news-letter-status").css("color", "red");
	              }
	              else if(data=="Already subscribed.")
	              {
	                $("#wpm-news-letter-status").text("");
	                $("#wpm-news-letter-status").css("color", "red");
	              }
	              else
	              {
	                $("#wpm-news-letter-status").text("You're subscribed!");
	                $("#wpm-news-letter-status").css("color", "green");
	              }
	            }
	            else
	            {
	              alert("Sorry, unable to subscribe. Please try again later!");
	            }
	        }
	      );
	    });      
        
        var setup;                
        var wp_pointers_tour_opts = {
            content:content,
            position:{
                edge:"top",
                align:"left"
            }
        };
                        
        wp_pointers_tour_opts = $.extend (wp_pointers_tour_opts, {
                buttons: function (event, t) {
                        button= jQuery ('<a id="pointer-close" class="button-secondary">' + wpm_localize_data.button1 + '</a>');
                        button_2= jQuery ('#pointer-close.button');
                        button.bind ('click.pointer', function () {
                                t.element.pointer ('close');
                        });
                        button_2.on('click', function() {
                          setTimeout(function(){ 
                              t.element.pointer ('close');
                         }, 3000);
                              
                        } );
                        return button;
                },
                close: function () {
                        $.post (wpm_localize_data.ajax_url, {
                                pointer: 'wpm_subscribe_pointer',
                                action: 'dismiss-wp-pointer'
                        });
                },
                show: function(event, t){
                 t.pointer.css({'left':'170px', 'top':'360px'});
              }                                               
        });
        setup = function () {
                $(wpm_localize_data.displayID).pointer(wp_pointers_tour_opts).pointer('open');
                 if (wpm_localize_data.button2) {
                        jQuery ('#pointer-close').after ('<a id="pointer-primary" class="button-primary">' + wpm_localize_data.button2+ '</a>');
                        jQuery ('#pointer-primary').click (function () {
                                wpm_localize_data.function_name;
                        });
                        jQuery ('#pointer-close').click (function () {
                                $.post (wpm_localize_data.ajax_url, {
                                        pointer: 'wpm_subscribe_pointer',
                                        action: 'dismiss-wp-pointer'
                                });
                        });
                 }
        };
        if (wp_pointers_tour_opts.position && wp_pointers_tour_opts.position.defer_loading) {
                $(window).bind('load.wp-pointers', setup);
        }
        else {
                setup ();
        }
        
    }
});