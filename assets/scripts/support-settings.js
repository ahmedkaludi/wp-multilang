jQuery(document).ready(function($){
	
	$(document).on('click', '#wpm-send-support-query', function(e){
		e.preventDefault();   
		var message     = $("#wpm_query_message").val();  
		var email       = $("#wp_query_email").val();  

		if($.trim(message) !='' && $.trim(email) !='' && wpmIsEmail(email) == true){
			$(this).text('Sending...');
		 	$.ajax({
		        type: "POST",    
		        url:wpm_support_settings_params.ajax_url,                    
		        dataType: "json",
		        data:{action:"wpm_send_query_message",message:message,email:email,security:wpm_support_settings_params.support_nonce},
                success:function(response){                       
                  if(response['status'] =='t'){
                    $(".wpm-query-success").show();
                    $(".wpm-query-error").hide();
                  }else{                                  
                    $(".wpm-query-success").hide();  
                    $(".wpm-query-error").show();
                  }
                  $('#wpm-send-support-query').text('Send Support Request');
                },
                error: function(response){        
                	$('#wpm-send-support-query').text('Send Support Request');            
                }
		    });   
		}else{
		    
		    if($.trim(message) =='' && $.trim(email) ==''){
		        alert('Please enter the message and email');
		    }else{
		    
		    if($.trim(message) == ''){
		        alert('Please enter the message');
		    }
		    if($.trim(email) == ''){
		        alert('Please enter the email');
		    }
		    if(wpmIsEmail(email) == false){
		        alert('Please enter a valid email');
		    }
		        
		    }
		    
		}                        
	});

	function wpmIsEmail(email) {
	    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	    return regex.test(email);
	}

});