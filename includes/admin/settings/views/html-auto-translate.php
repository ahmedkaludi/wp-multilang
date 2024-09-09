<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @var $value array
 * @var $flags array
 */
?>
<div style="background:#fff;padding:10px">
<div>
<h2 style="border-bottom:1px solid #d7d7d7;padding-bottom:10px"><?php echo esc_html( $value['title'] ); ?></h2>
<ul>
<?php 
    $published_post_count = 0;
    $total_pages = 0;
    $total_product = 0;
    $total_post_arr = wp_count_posts('post');
    if(isset($total_post_arr->publish)){
        $published_post_count = $total_post_arr->publish;
        if($published_post_count==""){
            $published_post_count = 0;
        }
    }
    $count_pages = wp_count_posts('page'); 
    if(isset($count_pages->publish)){
        $total_pages = $count_pages->publish; 
        if($total_pages==""){
            $total_pages = 0;
        }
    }
    $count_product = wp_count_posts('product'); 
    if(isset($count_product->publish)){
        $total_product = $count_product->publish;
        if($total_product==""){
            $total_product = 0;
        }
    }
    $total_record = $published_post_count + $total_pages + $total_product;
?>
<?php 
$source_language = wpm_get_user_language();
foreach ( $languages as $code => $language ) { 
    if($source_language === $code){continue;}
?>
 
 <li>
    <h4>
        <?php if(isset($language['flag'])){?>
            <img src=<?php echo wpm_get_flags_dir().$language['flag']?> />
        <?php }?>
        <?php echo esc_html( $language['name'] ); ?>
        <input type="checkbox" class="language-cb" value="<?php echo $code?>"/>
    </h4>
</li>
<?php }?>
</div>
<div>
<h2 style="border-bottom:1px solid #d7d7d7;padding-bottom:10px">What?</h2>
<ul>
    <li>
        <h4><input type="checkbox" value="all" id="what_all_opt"/> All (<?php echo $total_record?>)</h4>
    </li>
    <li>
        <h4><input type="checkbox" class="what-list" value="post"/> Post (<?php echo $published_post_count?>)</h4>
    </li>
    <li>
        <h4><input type="checkbox" class="what-list" value="page"/> Pages (<?php echo $total_pages?>)</h4>
    </li>
    <li>
        <h4><input type="checkbox" class="what-list" value="product"/> Product Post Type  (<?php echo $total_product?>)</h4>
    </li>
</ul>
</div>
<div id="translation-success-message" style="display:none">
    <h2 style="color:green">Translation Successful</h2>
</div>
<div style="height:10px;background:#ebebeb;border-radius: 5px;margin-bottom:10px;text-align:center;display:none" id="parent-progress-bar">
    <div style="height:10px;background:green;border-radius: 5px;width:0%" id="child-progress-bar">
        
    </div>
    <b style="font-size:14px;text-transform:uppercase" id="progress_count">0%</b>
</div>
<div>
   
    <button class="button button-primary" id="wpm-translate" style="display:block" type="button"><?php echo esc_html_e('Start Translation', 'wp-multilang') ?></button>
    <button class="button button-primary" id="wpm-translate-hide" style="display:none" type="button"><?php echo esc_html_e('Translating...', 'wp-multilang') ?></button>
</div>
</div>
<script>
    let total_loop = 1;
    jQuery(document).ready(function($){
        $('#what_all_opt').change(() => {
            let qs = document.querySelectorAll('.what-list');
            if($('#what_all_opt').is(':checked')) {
                for (let index = 0; index < qs.length; index++) {
                    const element = qs[index];
                    element.checked = true;
                }
            } else {
                for (let index = 0; index < qs.length; index++) {
                    const element = qs[index];
                    element.checked = false;
                }
            }
        });
        $('.what-list').change(() => {
            let total_len = 0;
            $(".what-list").each(function(e){
                total_len = total_len+1;
            })
            let checked_len=0;
            $(".what-list:checked").each(function(e){
                checked_len = checked_len+1;
            })
            if(total_len==checked_len){
                if(document.getElementById('what_all_opt')){
                    document.getElementById('what_all_opt').checked = true;
                }
                
            }else{
                if(document.getElementById('what_all_opt')){
                    document.getElementById('what_all_opt').checked = false;
                }
            }
        });
        function progressDone(){
            let cal_per = 100;
            $("#child-progress-bar").css({'width':cal_per+'%'}); 
            $("#progress_count").html(cal_per+'%');
            $("#wpm-translate").css({'display':'block'});
            $("#wpm-translate-hide").css({'display':'none'});
            $("#wpm-translate-hide").html('Translating (0%)');
            $("#parent-progress-bar").css({'display':'none'});
            $("#translation-success-message").css({'display':'block'});
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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
                // weeks: allocate(604800000), // Uncomment for weeks
                //days: allocate(86400000),
                hours: allocate(3600000),
                minutes: allocate(60000),
                seconds: allocate(1000),
                ms: ms // remainder
            }
        }
        function processTime(newtime){
            let hours = newtime.hours;
            let minutes = newtime.minutes;
            let seconds = newtime.seconds;
            let ms = newtime.ms;
            let build_time_format = '';
            if(hours>0){
                build_time_format = hours+' hour(s) ';
            }
            if(minutes>0){
                build_time_format += minutes+' minute(s) ';
            }
            if(minutes==0){
                build_time_format = '';
            }
            if(minutes==0 || seconds>0 || ms>0){
                build_time_format = ' Less than a minute';
            }
            build_time_format +=' remaing...';
            return build_time_format;
        }
        function handleProcessTranslation(page,target_langs,post_type,what_arr){
            let _total_count = 0;
            let total_record = <?php echo $total_record?>;
            let post_count = <?php echo $published_post_count?>;
            let page_count = <?php echo $total_pages?>;
            let product_count = <?php echo $total_product?>;
           
            if(what_arr.indexOf(post_type)>=0){
                if(what_arr.indexOf('post')>=0){
                    _total_count = _total_count + post_count;
                }
                if(what_arr.indexOf('page')>=0){
                    _total_count = _total_count + page_count;
                }
                if(what_arr.indexOf('product')>=0){
                    _total_count = _total_count + product_count;
                }
            }else{
                return false;
            }
            var ajaxTime= new Date().getTime();
            $.ajax({
                type : "POST",
                url : "<?php echo admin_url( 'admin-ajax.php' )?>",
                data : {action: "wpm_do_auto_translate",post_type:post_type,offset:page,source:"<?php echo $source_language?>",target:target_langs,what_arr:what_arr,total_loop:total_loop},
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                success: function(response) {
                    var totalTime = new Date().getTime()-ajaxTime;
                    let total_calculated_time = totalTime * (_total_count-page);
                    let newtime = timeUnits(total_calculated_time);
                    let time_msg = processTime(newtime);
                   if(parseInt(response)==1){
                    let cal_per = (page/_total_count)*100;
                    cal_per = Math.round(cal_per);
                    $("#child-progress-bar").css({'width':cal_per+'%'}); 
                    let progress_msg = 'Translating '+post_type+' '+time_msg+'('+cal_per+')% ';
                    $("#progress_count").html(progress_msg);
                    $("#wpm-translate-hide").html('Translating ('+cal_per+'%)');
                    let is_done = false;
                    
                    let next_page = page+1;
                    if(post_type==='post' && next_page>post_count && what_arr.indexOf('page')>=0 && page_count>0){
                        next_page = 1;
                        post_type = 'page';
                    }else if(post_type==='page' && next_page>page_count && what_arr.indexOf('product')>=0 && product_count>0){
                        next_page = 1;
                        post_type = 'product';
                    }
                    console.log(_total_count+' - '+total_loop);
                    if(_total_count==total_loop){
                        progressDone();
                    }else{
                        total_loop = total_loop+1;
                        handleProcessTranslation(next_page,target_langs,post_type,what_arr);
                    }
                   }else{
                    progressDone();
                   }
                }
            });
        }
        $( "#wpm-translate" ).on( "click", function() {
            
            let target_langs = [];
            $(".language-cb").each(function(e){
                let is_checked = $(this).is(":checked");
                if(is_checked===true){
                    let value = $(this).val();
                    target_langs.push(value)
                }
            })
            let what_arr = [];
            $(".what-list").each(function(e){
                let is_checked = $(this).is(":checked");
                if(is_checked===true){
                    let value = $(this).val();
                    what_arr.push(value)
                }
            })
            
            if(target_langs.length==0){
                alert('Please select language to translate');
                return false;
            }
            if(what_arr.length==0){
                alert('Please select what you want to translate');
                return false;
            }
            $("#parent-progress-bar").css({'display':'block'});
            $("#wpm-translate").css({'display':'none'});
            $("#wpm-translate-hide").css({'display':'block'});
            let process_type = '';
            if(what_arr.indexOf('post')>=0){
                process_type = 'post';
            }else if(what_arr.indexOf('page')>=0){
                process_type = 'page';
            }else if(what_arr.indexOf('product')>=0){
                process_type = 'product';
            }
            handleProcessTranslation(1,target_langs,process_type,what_arr);
        } );
    });
</script>