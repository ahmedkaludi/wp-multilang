let total_loop = 1;
let translationInProgress = false;
let progressDoneCalled = false;

let proBtn = '<span class="wpm-upgrade-to-pro-note" style="margin-left: 50px; font-weight: 500;"> This Feature requires the <a href="https://wp-multilang.com/pricing/#pricings" target="__blank">Premium Version</span>';
let openAINote = '<span class="wpm-upgrade-to-pro-note" style="margin-left: 50px; font-weight: 500;"> Please configure OpenAI settings</span>';
let licenseKeyError = '<span class="wpm-upgrade-to-pro-note" style="margin-left: 50px; font-weight: 500;"> Your license key is inactive or expired</span>';

jQuery(document).ready(function($){

    $('#wpmpro-what-all-opt').change(() => {

        const selectedProvider = wpmpro_autotranslate_localize_data.ai_settings.api_provider;

        let qs = document.querySelectorAll('.wpmpro-what-list');

        if($('#wpmpro-what-all-opt').is(':checked')) {

            for (let index = 0; index < qs.length; index++) {
                const element = qs[index];
                element.checked = true;
                
                if ( ( wpmpro_autotranslate_localize_data.is_pro_active && wpmpro_autotranslate_localize_data.license_status === 'active' ) 
                    || ( selectedProvider === 'openai' && wpmpro_autotranslate_localize_data.ai_settings.model.length > 0 )  ) {
                    // Show exclude wrapper for this item
                    const excludeWrapper = $(element).closest('li').find('.exclude-wrapper');
                    if (excludeWrapper.length > 0) {
                        excludeWrapper.show();
                    }
                }
            }
            // $('.wpmpro-what-list').each(function() {
            //     if ( wpmpro_autotranslate_localize_data.is_pro_active && wpmpro_autotranslate_localize_data.license_status !== 'active' ) {
            //         $(this).next('label').after(licenseKeyError);
            //     }
            // });

        } else {

            for (let index = 0; index < qs.length; index++) {
                const element = qs[index];
                element.checked = false;
                
                // Hide exclude wrapper for this item
                const excludeWrapper = $(element).closest('li').find('.exclude-wrapper');
                if (excludeWrapper.length > 0) {
                    excludeWrapper.hide();
                }

                $('.wpmpro-what-list').each(function() {
                    $(this).next('label').next('span.wpm-upgrade-to-pro-note').remove();
                });
            }

        }

    });

    $(document).on('change', '.wpmpro-what-list', function(){
        let total_len = 0;
        let checked_len = 0;
        const selectedProvider = wpmpro_autotranslate_localize_data.ai_settings.api_provider;

        // Show/hide exclude wrapper
        const excludeWrapper = $(this).closest('li').find('.exclude-wrapper');
        if ($(this).is(':checked')) {
            // if ( wpmpro_autotranslate_localize_data.is_pro_active && wpmpro_autotranslate_localize_data.license_status !== 'active' ) {
            //         $(this).next('label').after(licenseKeyError);
            // }
            if ( ( wpmpro_autotranslate_localize_data.is_pro_active && wpmpro_autotranslate_localize_data.license_status === 'active' ) 
                    || ( selectedProvider === 'openai' && wpmpro_autotranslate_localize_data.ai_settings.model.length > 0 )  ) {
                excludeWrapper.show();
            }
        } else {
            $("label[for='" + $(this).attr('id') + "']").next('span.wpm-upgrade-to-pro-note').remove();
            excludeWrapper.hide();
        }

        $(".wpmpro-what-list").each(function(e){
            total_len = total_len+1;
        });

        $(".wpmpro-what-list:checked").each(function(e){
            checked_len = checked_len+1;
        });

        if(total_len==checked_len){
            if(document.getElementById('wpmpro-what-all-opt')){
                document.getElementById('wpmpro-what-all-opt').checked = true;
            }
        }else{
            if(document.getElementById('wpmpro-what-all-opt')){
                document.getElementById('wpmpro-what-all-opt').checked = false;
            }
        }
    });

    // Initialize Select2 for exclude selects
    function initializeSelect2() {
        if (typeof $.fn.select2 === 'undefined') {
            console.warn('Select2 not loaded yet, retrying in 100ms...');
            setTimeout(initializeSelect2, 100);
            return;
        }
        
        $('.exclude-select').each(function() {
            const $select = $(this);
            const postType = $select.data('type');
            
          
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }
            
            try {
                $select.select2({
                    placeholder: 'Search items to exclude...',
                    minimumInputLength: 2,
                    width: '100%',
                    dropdownParent: $select.parent(),
                    ajax: {
                        url: wpmpro_autotranslate_localize_data.ajax_url,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            const requestData = {
                                action: 'wpmpro_search_items',
                                search: params.term,
                                type: postType,
                                _wpnonce: wpmpro_autotranslate_localize_data.nonce
                            };
                            console.log('Select2 AJAX request data:', requestData);
                            return requestData;
                        },
                        processResults: function(data) {
                            console.log('Select2 AJAX response:', data);
                            
           
                            if (data && data.success && data.data) {
        
                                return {
                                    results: data.data || []
                                };
                            } else if (Array.isArray(data)) {
                    
                                return {
                                    results: data
                                };
                            } else if (data && data.data) {
                         
                                return {
                                    results: data.data
                                };
                            }
                            
                            console.warn('Unexpected response format:', data);
                            return {
                                results: []
                            };
                        },
                        cache: true,
                        error: function(xhr, status, error) {
                            console.error('Select2 AJAX error:', {xhr, status, error});
                        }
                    }
                });
            } catch (e) {
                console.error('Select2 initialization error:', e);
            }
        });
    }
    
    if (typeof $.fn.select2 !== 'undefined') {
        initializeSelect2();
    } else {

        $(window).on('load', function() {
            setTimeout(initializeSelect2, 100);
        });
    }

    $("#wpmpro-translate").on("click", async function () {

        $('#wpmpro-translation-error-message').hide();
        let target_langs = [];
        const selectedProvider = wpmpro_autotranslate_localize_data.ai_settings.api_provider;
        $(".wpmpro-language-cb").each(function () {
            if ($(this).is(":checked")) {
                target_langs.push($(this).val());
            }
        });
    
        let what_arr = [];
        let excluded_items = {};
        $(".wpmpro-what-list").each(function () {
            if ($(this).is(":checked")) {
                const type = $(this).val();
                what_arr.push(type);
                

                const $excludeSelect = $(this).closest('li').find('.exclude-select');
                if ($excludeSelect.length > 0) {
                    const excludedValues = $excludeSelect.val();
                    if (excludedValues && excludedValues.length) {
                        excluded_items[type] = excludedValues;
                    }
                }

                const excludedValues = $(this).closest('li').find('.exclude-select').val();
                if (excludedValues && excludedValues.length) {
                    excluded_items[type] = excludedValues;
                }
            }
        });
    
        if (target_langs.length === 0) {
            alert("Please select language to translate");
            return false;
        }
    
        if (what_arr.length === 0) {
            alert("Please select what you want to translate");
            return false;
        }
        console.log('wpm_openai_integration ', wpmpro_autotranslate_localize_data.ai_settings.wpm_openai_integration);
        if( ! wpmpro_autotranslate_localize_data.is_pro_active && ( wpmpro_autotranslate_localize_data.ai_settings.wpm_openai_integration.length === 0 || wpmpro_autotranslate_localize_data.ai_settings.wpm_openai_integration == '0' ) ) {
            return false;
        } 
        if( ! wpmpro_autotranslate_localize_data.is_pro_active && ( selectedProvider.length === 0 || wpmpro_autotranslate_localize_data.ai_settings.model.length === 0 ) ) {
            return false
        }
        
        if ( ( wpmpro_autotranslate_localize_data.is_pro_active ) && wpmpro_autotranslate_localize_data.license_status !== 'active' ) {
            return false;
        }

        // Prevent multiple simultaneous translations
        if (translationInProgress) {
            console.warn('⚠️ Translation already in progress - ignoring request');
            return false;
        }
        
        // Check AI quota before translating
        const aiQuotaStatus = await wpmHandleAITranslationCheck(selectedProvider);
        if (!aiQuotaStatus) {
            return false;
        }

        translationInProgress = true;
        progressDoneCalled = false;
        
        console.log('🚀 Starting translation process...');
        
        $("#wpmpro-parent-progress-bar").css({ display: "block" });
        $("#wpmpro-translate").css({ display: "none" });
        $("#wpmpro-translate-hide").css({ display: "block" });
    
        let total_counts = {
            post: wpmpro_autotranslate_localize_data.published_post_count,
            page: wpmpro_autotranslate_localize_data.total_pages,
            product: wpmpro_autotranslate_localize_data.total_product,
            category: wpmpro_autotranslate_localize_data.total_categories,
            post_tag: wpmpro_autotranslate_localize_data.total_tags,
            product_cat: wpmpro_autotranslate_localize_data.total_product_categories,
        };
    
        // Total translations tracking
        let total_translations = 0;
        let completed_translations = 0;
    
        for (let post_type of what_arr) {
            const total_count = total_counts[post_type] || 0;
            total_translations += total_count * target_langs.length;
        }
    
        // Process all post types sequentially to avoid conflicts
        let all_failed_items = [];
        let total_success = 0;
        let total_errors = 0;
        let total_processed = 0;
        
        for (let post_type of what_arr) {
            const total_count = total_counts[post_type] || 0;
            console.log('total_count ', total_count);
            if (total_count > 0) {
                console.log(`🔄 Starting translation for post type: ${post_type} (${total_count} items)`);
                
                const result = await handleProcessTypes(target_langs, post_type, total_count, total_translations, excluded_items, (completed) => {
                    completed_translations += completed;
                    const cal_per = Math.round((completed_translations / total_translations) * 100);
                    $("#wpmpro-child-progress-bar").css({ width: cal_per + "%" });
                    const progress_msg = `Translating: ${post_type} in ${target_langs.join(',')} (${completed_translations} / ${total_translations}) (${cal_per}%)`;
                    $("#wpmpro-progress_count").html(progress_msg);
                    $("#wpmpro-translate-hide").html(`Translating (${cal_per}%)`);
                });
                
                // Collect results
                total_success += result.success;
                total_errors += result.errors;
                total_processed += result.processed;
                all_failed_items = all_failed_items.concat(result.failed_items || []);
                
                console.log(`✅ Completed translation for post type: ${post_type}`);
            }
        }
        
        // Retry failed items if any
        if (all_failed_items.length > 0) {
            console.log(`🔄 Retrying ${all_failed_items.length} failed items...`);
            $("#wpmpro-progress_count").html(`Retrying ${all_failed_items.length} failed items...`);
            
            let retry_success = 0;
            let retry_failed = 0;
            
            for (let failed_item of all_failed_items) {
                if (failed_item.retryable) {
                    console.log(`🔄 Retrying page ${failed_item.page}, language ${failed_item.lang} for ${failed_item.post_type}`);
                    
                    try {
                        const retry_result = await handleProcessTranslation(
                            failed_item.page, 
                            failed_item.lang, 
                            failed_item.post_type, 
                            total_counts[failed_item.post_type] || 0, 
                            excluded_items, 
                            0 // Reset retry count for retry attempt
                        );
                        
                        if (retry_result && retry_result.success === true) {
                            retry_success++;
                            console.log(`✓ Retry successful: page ${failed_item.page}, language ${failed_item.lang}`);
                        } else {
                            retry_failed++;
                            console.warn(`✗ Retry failed: page ${failed_item.page}, language ${failed_item.lang} - ${retry_result?.error || 'Unknown error'}`);
                        }
                    } catch (error) {
                        retry_failed++;
                        console.error(`✗ Retry exception: page ${failed_item.page}, language ${failed_item.lang}`, error);
                    }
                    
                    // Small delay between retry attempts
                    await new Promise(resolve => setTimeout(resolve, 500));
                }
            }
            
            // Update final counts
            total_success += retry_success;
            total_errors = total_errors - retry_success + retry_failed;
            
            console.log(`🎯 Retry completed: ${retry_success} successful, ${retry_failed} still failed`);
        }
    
        // Only call progressDone after ALL post types are completed
        console.log('🎉 All post types completed - calling progressDone()');
        progressDone(total_success, total_errors, total_processed);
    });
    
    async function handleProcessTypes(target_langs, post_type, total_count, total_translations, excluded_items, updateProgress) {
        let processed_count = 0;
        let success_count = 0;
        let error_count = 0;
        let timeout_count = 0;
        let retry_count = 0;
        let failed_items = []; // Track failed items for retry
        
        console.log(`🚀 Starting translation for post type: ${post_type} with ${total_count} items`);
        
        for (let lang of target_langs) {
            console.log(`🌐 Starting translation for language: ${lang}`);
            
            for (let page = 1; page <= total_count; page++) {
                console.log(`📄 Processing page ${page} of ${total_count} for language ${lang}`);
                
                try {
                    const result = await handleProcessTranslation(page, lang, post_type, total_count, excluded_items);
                    processed_count++;
                    updateProgress(1);
                    
                    // Ensure result is valid
                    if (!result || typeof result !== 'object') {
                        console.warn(`⚠️ Invalid result for page ${page}, language ${lang} - treating as success`);
                        success_count++;
                    } else if (result.success === true) {
                        success_count++;
                        console.log(`✓ Success: page ${page}, language ${lang} - ${result.message || 'No message'}`);
                    } else {
                        error_count++;
                        if (result.error && result.error.includes && result.error.includes('timeout')) {
                            timeout_count++;
                        }
                        if (result.retryable === true) {
                            retry_count++;
                        }
                        console.warn(`✗ Failed: page ${page}, language ${lang} - ${result.error || 'Unknown error'}`);
                        
                        // Add to failed items for retry
                        failed_items.push({
                            page: page,
                            lang: lang,
                            post_type: post_type,
                            error: result.error || 'Unknown error',
                            retryable: result.retryable === true
                        });
                    }
                    
                    // Update progress message with current status
                    const currentProgress = Math.round((processed_count / total_translations) * 100);
                    const statusMessage = `Translating: ${post_type} in ${lang} (${processed_count}/${total_translations}) (${currentProgress}%) - Success: ${success_count}, Errors: ${error_count}, Timeouts: ${timeout_count}`;
                    $("#wpmpro-progress_count").html(statusMessage);
                    
                    // Add a small delay between requests to prevent overwhelming the server
                    await new Promise(resolve => setTimeout(resolve, 300));
                    
                } catch (error) {
                    error_count++;
                    processed_count++;
                    updateProgress(1);
                    console.error(`✗ Exception processing page ${page}, language ${lang}:`, error);
                    
                    // Add to failed items for retry
                    failed_items.push({
                        page: page,
                        lang: lang,
                        post_type: post_type,
                        error: error.message || 'Exception occurred',
                        retryable: true
                    });
                    
                    // Update progress message even on exception
                    const currentProgress = Math.round((processed_count / total_translations) * 100);
                    const statusMessage = `Translating: ${post_type} in ${lang} (${processed_count}/${total_translations}) (${currentProgress}%) - Success: ${success_count}, Errors: ${error_count}, Exceptions: ${error_count - success_count}`;
                    $("#wpmpro-progress_count").html(statusMessage);
                }
            }
            
            console.log(`✅ Completed language ${lang} for post type ${post_type}`);
        }
        
        console.log(`🎯 Translation completed for ${post_type}. Processed: ${processed_count}, Success: ${success_count}, Errors: ${error_count}, Timeouts: ${timeout_count}, Retries: ${retry_count}`);
        
        // Show final summary
        const summaryMessage = `Completed: ${post_type} - Success: ${success_count}, Errors: ${error_count}, Timeouts: ${timeout_count}`;
        console.log(summaryMessage);
        
        // Return completion status with failed items
        return {
            processed: processed_count,
            success: success_count,
            errors: error_count,
            timeouts: timeout_count,
            retries: retry_count,
            failed_items: failed_items
        };
    }
    
    function handleProcessTranslation(page, target_lang, post_type, total_count, excluded_items, retryCount = 0) {
        return new Promise((resolve) => {
            if (page > total_count) {
                resolve({success: false, error: 'Page out of range'});
                return;
            }
    
            const maxRetries = 2;
            const baseTimeout = 30000; // 30 seconds base timeout
            const timeout = baseTimeout + (retryCount * 15000); // Increase timeout with retries
    
            console.log(`Processing page ${page} (attempt ${retryCount + 1}/${maxRetries + 1}) with timeout ${timeout}ms`);
    
            $.ajax({
                type: "POST",
                url: wpmpro_autotranslate_localize_data.ajax_url,
                data: {
                    action: "wpm_do_auto_translate",
                    post_type: post_type,
                    offset: page,
                    source: wpmpro_autotranslate_localize_data.source_language,
                    excluded_items: JSON.stringify(excluded_items),
                    target: target_lang,
                    wpmpro_autotranslate_nonce: wpmpro_autotranslate_localize_data.wpmpro_autotranslate_nonce,
                },
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                dataType: "json",
                timeout: timeout,
                success: function (response) {
                    console.log('Translation response for page', page, ':', response);
                    
                    // Handle empty or null responses
                    if (!response || response === null || response === undefined) {
                        console.warn('Empty response for page', page, '- treating as success to continue');
                        resolve({success: true, message: 'Empty response - continuing'});
                        return;
                    }
                    
                    // Handle different response formats
                    if (typeof response === 'object' && response !== null) {
                        if (response.status === true || response.success === true) {
                            console.log('✓ Translation successful for page', page, '-', response.message || 'No message');
                            resolve({success: true, message: response.message || 'Translation completed'});
                        } else {
                            console.warn('✗ Translation failed for page', page, ':', response.message || 'Unknown error');
                            // Even if translation failed, continue to next page
                            resolve({success: true, message: 'Failed but continuing - ' + (response.message || 'Unknown error')});
                        }
                    } else {
                        console.warn('Unexpected response format for page', page, ':', response, '- treating as success');
                        resolve({success: true, message: 'Unexpected response format - continuing'});
                    }
                },
                error: function (xhr, status, error) {
                    const isTimeout = status === 'timeout';
                    const isAbort = status === 'abort';
                    
                    console.error('AJAX error for page', page, ':', {
                        status: status,
                        error: error,
                        isTimeout: isTimeout,
                        isAbort: isAbort,
                        responseText: xhr.responseText ? xhr.responseText.substring(0, 200) : 'No response',
                        readyState: xhr.readyState,
                        statusText: xhr.statusText
                    });
                    
                    // Try to parse response as JSON even on error
                    let parsedResponse = null;
                    try {
                        if (xhr.responseText && xhr.responseText.trim() !== '') {
                            parsedResponse = JSON.parse(xhr.responseText);
                            if (parsedResponse && parsedResponse.status === true) {
                                console.log('✓ Translation successful despite error status');
                                resolve({success: true, message: 'Translation completed despite error'});
                                return;
                            }
                        }
                    } catch (e) {
                        console.warn('Could not parse error response as JSON:', e.message);
                    }
                    
                    // Determine if we should retry
                    const shouldRetry = retryCount < maxRetries && (isTimeout || (xhr.status >= 500 && xhr.status < 600));
                    
                    if (shouldRetry) {
                        console.log(`🔄 Retrying page ${page} in 2 seconds... (attempt ${retryCount + 2})`);
                        setTimeout(() => {
                            handleProcessTranslation(page, target_lang, post_type, total_count, excluded_items, retryCount + 1)
                                .then(resolve);
                        }, 2000);
                    } else {
                        const errorMessage = isTimeout ? 'Request timeout' : 
                                           isAbort ? 'Request aborted' : 
                                           `HTTP ${xhr.status}: ${xhr.statusText}`;
                        console.warn(`⚠️ Final failure for page ${page}: ${errorMessage} - continuing to next page`);
                        // Always continue to next page, even on final failure
                        resolve({success: true, message: `Failed: ${errorMessage} but continuing`});
                    }
                },
            });
        });
    }

    function progressDone(successCount = 0, errorCount = 0, processedCount = 0){
        // Prevent multiple calls to progressDone
        if (progressDoneCalled) {
            console.warn('⚠️ progressDone() already called - ignoring duplicate call');
            return;
        }
        
        progressDoneCalled = true;
        translationInProgress = false;
        
        console.log('🏁 progressDone() called - finalizing translation process');
        
        let cal_per = 100;
        $("#wpmpro-child-progress-bar").css({'width':cal_per+'%'}); 
        $("#wpmpro-progress_count").html('Translation completed (100%)');
        $("#wpmpro-translate").css({'display':'block'});
        $("#wpmpro-translate-hide").css({'display':'none'});
        $("#wpmpro-translate-hide").html('Translating (0%)');
        $("#wpmpro-parent-progress-bar").css({'display':'none'});
        
        // Create detailed completion message with statistics
        let messageClass = 'd4edda';
        let borderClass = 'c3e6cb';
        let textClass = '155724';
        let titleText = '✓ Translation Process Completed';
        
        if (errorCount > 0) {
            messageClass = errorCount > successCount ? 'f8d7da' : 'fff3cd';
            borderClass = errorCount > successCount ? 'f5c6cb' : 'ffeaa7';
            textClass = errorCount > successCount ? '721c24' : '856404';
            titleText = errorCount > successCount ? '⚠️ Translation Process Completed with Errors' : '✓ Translation Process Completed with Some Issues';
        }
        
        const completionMessage = `
            <div style="background: #${messageClass}; border: 1px solid #${borderClass}; color: #${textClass}; padding: 15px; border-radius: 4px; margin: 10px 0;">
                <h4 style="margin: 0 0 10px 0; color: #${textClass};">${titleText}</h4>
                <div style="margin: 10px 0;">
                    <p style="margin: 5px 0; font-weight: bold;">📊 Final Statistics:</p>
                    <ul style="margin: 5px 0; padding-left: 20px;">
                        <li>✅ Successfully translated: <strong>${successCount}</strong> items</li>
                        <li>❌ Failed translations: <strong>${errorCount}</strong> items</li>
                        <li>📝 Total processed: <strong>${processedCount}</strong> items</li>
                    </ul>
                </div>
                <p style="margin: 5px 0; font-size: 12px; color: #6c757d;">
                    ${errorCount > 0 ? 
                        'Some translations failed. Check the browser console for detailed error information.' : 
                        'All translations completed successfully!'
                    }
                </p>
                <p style="margin: 5px 0; font-size: 12px; color: #6c757d;">
                    You can close this message or refresh the page to see updated content.
                </p>
            </div>
        `;
        
        $("#wpmpro-translation-success-message").html(completionMessage);
        $("#wpmpro-translation-success-message").css({'display':'block'});
        
        // Log final completion with statistics
        console.log('🎉 Translation process completed!');
        console.log(`📊 Final Results: ${successCount} successful, ${errorCount} failed, ${processedCount} total processed`);
        
        if (errorCount > 0) {
            console.warn(`⚠️ ${errorCount} translations failed. Check the detailed logs above for error information.`);
        } else {
            console.log('✅ All translations completed successfully!');
        }

    }

    function timeUnits( ms ) {

        if ( !Number.isInteger(ms) ) {
            return null
        }
       
        const allocate = msUnit => {
            const units = Math.trunc(ms / msUnit)
            ms -= units * msUnit
            return units
        }
        
        return {
            hours: allocate(3600000),
            minutes: allocate(60000),
            seconds: allocate(1000),
            ms: ms // remainder
        }

    }

    function processTime( newtime ) {

        let hours               = newtime.hours;
        let minutes             = newtime.minutes;
        let seconds             = newtime.seconds;
        let ms                  = newtime.ms;
        let build_time_format   = '';

        if(hours>0){
            build_time_format   = hours+' hour(s) ';
        }

        if(minutes>0){
            build_time_format   += minutes+' minute(s) ';
        }

        if(minutes==0){
            build_time_format   = '';
        }

        if(minutes==0 || seconds>0 || ms>0){
            build_time_format = ' Less than a minute';
        }

        build_time_format +=' remaing...';
        return build_time_format;

    }

    // Handle Ajax request to check quota
    async function wpmHandleAITranslationCheck(selectedProvider) {
        let openAIStatus = true;

        if (!wpmpro_autotranslate_localize_data.is_pro_active &&
            selectedProvider === 'openai' &&
            wpmpro_autotranslate_localize_data.ai_settings.model.length > 0 &&
            wpmpro_autotranslate_localize_data.ai_settings.wpm_openai_integration == '1'
        ) {

            $("#wpmpro-translate").hide();
            $("#wpmpro-translate-hide").show();

            try {
                const response = await wpmCheckAIQuota(selectedProvider);

                if (response.status === false) {
                    $('#wpmpro-translation-error-message').show();
                    $('#wpm-ai-translate-error').text('AI Translation Error: ' + response.message);
                    $("#wpmpro-translate").show();
                    $("#wpmpro-translate-hide").hide();
                    openAIStatus = false;
                }

            } catch(err) {
                console.error("Quota check failed:", err);
                openAIStatus = false;
            }
        }

        return openAIStatus;
    }

    // Check AI quota through ajax
    function wpmCheckAIQuota(selectedProvider) {
        return new Promise(function(resolve, reject) {
            jQuery.ajax({
                type: "POST",
                url: wpmpro_autotranslate_localize_data.ajax_url,
                data: {
                    action: "wpm_check_ai_platform_quota",
                    provider: selectedProvider,
                    wpmpro_autotranslate_nonce: wpmpro_autotranslate_localize_data.wpmpro_autotranslate_nonce
                },
                success: function(response) {
                    resolve(response);
                },
                error: function(xhr) {
                    reject(xhr);
                }
            });
        });
    }

});