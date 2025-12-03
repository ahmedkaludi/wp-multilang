/**
 * Js code for singular auto translation 
 * @since   1.10
 * */
jQuery(document).ready(function($){

    /**
     * Auto translate single post on click with batch processing
     * @since   1.10
     * */
    $(document).on('click', '#wpm-auto-translate-btn', function(e){

        if( ! wpmpro_ats_localize_data.is_pro_active && ( wpmpro_ats_localize_data.ai_settings.wpm_openai_integration.length === 0 || wpmpro_ats_localize_data.ai_settings.wpm_openai_integration === '0' ) ) {
            return false;
        } 
        if( ! wpmpro_ats_localize_data.is_pro_active && ( selectedProvider.length === 0 || wpmpro_ats_localize_data.ai_settings.model.length === 0 ) ) {
            return false
        }
        
        if ( ( wpmpro_ats_localize_data.is_pro_active ) && wpmpro_ats_localize_data.license_status !== 'active' ) {
            return false;
        }

        //ask for conformation 
        if ( !confirm( wpmpro_ats_localize_data.confirmation_message ) ) {
            return false;
        }

        let postId      =   $('#wpm-current-post-id').val();
        let $button = $(this);
        $button.prop('disabled', true).text('Analyzing content...');
        
        // First, get the total node count
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: "wpm_get_translation_node_count",
                post_id: postId,
                source: wpmpro_ats_localize_data.source_language,
                target: wpmpro_ats_localize_data.target_language,
                wpmpro_autotranslate_singular_nonce: wpmpro_ats_localize_data.wpmpro_autotranslate_singular_nonce,
            },
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            dataType: "json",
            timeout: 30000, // 30 seconds timeout
            success: function (response) {
                console.log('Node count response:', response);
                
                if (response && response.success && response.data && response.data.total_nodes) {
                    const totalNodes = response.data.total_nodes;
                    console.log(`Found ${totalNodes} text nodes to translate`);
                    
                    if (totalNodes <= 150) {
                        // Use original single translation for small content
                        console.log('Content has 150 or fewer nodes, using single translation');
                        performSingleTranslation(postId, $button);
                    } else {
                        // Use batch processing for large content
                        console.log(`Content has ${totalNodes} nodes, using batch processing`);
                        performBatchTranslation(postId, totalNodes, $button);
                    }
                } else {
                    console.warn('Could not determine node count, falling back to single translation');
                    performSingleTranslation(postId, $button);
                }
            },
            error: function (xhr, status, error) {
                console.error('Node count AJAX Error:', {xhr: xhr, status: status, error: error});
                console.warn('Could not get node count, falling back to single translation');
                performSingleTranslation(postId, $button);
            },
        });

    });
    
    /**
     * Perform single translation (original method)
     */
    function performSingleTranslation(postId, $button) {
        $button.text('Translating...');
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: "wpm_singlular_auto_translate",
                post_id: postId,
                source: wpmpro_ats_localize_data.source_language,
                target: wpmpro_ats_localize_data.target_language,
                wpmpro_autotranslate_singular_nonce: wpmpro_ats_localize_data.wpmpro_autotranslate_singular_nonce,
            },
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            dataType: "json",
            timeout: 600000, // 10 minutes timeout
            success: function (response) {
                console.log('Single translation response:', response);
                $button.prop('disabled', false).text('Auto translate');
                if (response && response.status) {
                    alert(response.message || 'Translation completed successfully');
                } else {
                    alert('Translation is taking time it will continue in background, please check the page in a few minutes.');
                }
                location.reload()
            },
            error: function (xhr, status, error) {
                console.error('Single translation AJAX Error:', {xhr: xhr, status: status, error: error});
                $button.prop('disabled', false).text('Auto translate');
                if (status === 'timeout') {
                    alert('Translation request timed out after 10 minutes. The translation may still be processing in the background. Please check the page in a few minutes.');
                } else if (xhr.status === 404) {
                    alert('Translation is taking time it will continue in background, please check the page in a few minutes.');
                } else if (xhr.status === 500) {
                    alert('Server error (500). Please check the server logs.');
                } else {
                    alert('Translation failed: ' + (xhr.responseText || error || 'Unknown error'));
                }
            },
        });
    }
    
    /**
     * Perform batch translation for large content
     */
    function performBatchTranslation(postId, totalNodes, $button) {
        const batchSize = 100; // Process 100 nodes at a time
        const totalBatches = Math.ceil(totalNodes / batchSize);
        let currentBatch = 0;
        let completedBatches = 0;
        
        console.log(`Starting batch translation: ${totalBatches} batches of ${batchSize} nodes each`);
        
        function processNextBatch() {
            if (currentBatch >= totalBatches) {
                // All batches completed
                console.log('All batches completed successfully');
                $button.prop('disabled', false).text('Auto translate');
                alert(`Translation completed successfully! Processed ${totalNodes} text nodes in ${totalBatches} batches.`);
                location.reload();
                return;
            }
            
            const batchStart = currentBatch * batchSize;
            const progress = Math.round((completedBatches / totalBatches) * 100);
            
            $button.text(`Translating batch ${currentBatch + 1}/${totalBatches} (${progress}%)`);
            
            console.log(`Processing batch ${currentBatch + 1}/${totalBatches}: nodes ${batchStart}-${Math.min(batchStart + batchSize - 1, totalNodes - 1)}`);
            
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "wpm_process_batch_translation",
                    post_id: postId,
                    source: wpmpro_ats_localize_data.source_language,
                    target: wpmpro_ats_localize_data.target_language,
                    batch_start: batchStart,
                    batch_size: batchSize,
                    wpmpro_autotranslate_singular_nonce: wpmpro_ats_localize_data.wpmpro_autotranslate_singular_nonce,
                },
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                dataType: "json",
                timeout: 120000, // 2 minutes timeout per batch
                success: function (response) {
                    console.log(`Batch ${currentBatch + 1} response:`, response);
                    completedBatches++;
                    currentBatch++;
                    
                    if (response && response.status) {
                        console.log(`Batch ${currentBatch} completed successfully`);
                        // Small delay before next batch
                        setTimeout(processNextBatch, 1000);
                    } else {
                        console.warn(`Batch ${currentBatch} failed:`, response.message || 'Unknown error');
                        // Continue with next batch even if this one failed
                        currentBatch++;
                        setTimeout(processNextBatch, 2000);
                    }
                },
                error: function (xhr, status, error) {
                    console.error(`Batch ${currentBatch + 1} AJAX Error:`, {xhr: xhr, status: status, error: error});
                    completedBatches++;
                    currentBatch++;
                    
                    if (status === 'timeout') {
                        console.warn(`Batch ${currentBatch} timed out, continuing with next batch`);
                    } else {
                        console.warn(`Batch ${currentBatch} failed with error: ${error}, continuing with next batch`);
                    }
                    
                    // Continue with next batch even on error
                    setTimeout(processNextBatch, 2000);
                },
            });
        }
        
        // Start the batch processing
        processNextBatch();
    }
    
    /**
     * Auto translate single term on click
     * @since   1.10
     * */
    $(document).on('click', '#wpm-auto-translate-term-btn', function(e){

        if( ! wpmpro_ats_localize_data.is_pro_active && ( wpmpro_ats_localize_data.ai_settings.wpm_openai_integration.length === 0 || wpmpro_ats_localize_data.ai_settings.wpm_openai_integration == '0' ) ) {
            return false;
        } 
        if( ! wpmpro_ats_localize_data.is_pro_active && ( selectedProvider.length === 0 || wpmpro_ats_localize_data.ai_settings.model.length === 0 ) ) {
            return false
        }
        
        if ( ( wpmpro_ats_localize_data.is_pro_active ) && wpmpro_ats_localize_data.license_status !== 'active' ) {
            return false;
        }

        //ask for conformation 
        if ( !confirm( wpmpro_ats_localize_data.confirmation_message ) ) {
            return false;
        }

        //ask for conformation 
        if ( !confirm( wpmpro_ats_localize_data.confirmation_message ) ) {
            return false;
        }
        
        $(this).addClass('update-message');
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: "wpm_singlular_auto_translate_term",
                tag_id: wpmpro_ats_localize_data.tag_id,
                source: wpmpro_ats_localize_data.source_language,
                target: wpmpro_ats_localize_data.target_language,
                wpmpro_autotranslate_singular_nonce: wpmpro_ats_localize_data.wpmpro_autotranslate_singular_nonce,
            },
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            dataType: "json",
            success: function (response) {
                if (response.status) {
                    alert(response.message);
                } else {
                    alert(response.message);
                }
                location.reload()
            },
            error: function (response) {
                alert(response.message);
            },
        });

    });
    

});