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
        <h2 style="border-bottom:1px solid #d7d7d7;padding-bottom:10px"><?php echo esc_html($value['title']); ?></h2>
        <ul>
        <?php 

        $published_post_count       =   0;
        $total_pages                =   0;
        $total_product              =   0;
        $total_categories           =   0;
        $total_tags                 =   0;
        $total_product_categories   =   0;
        $total_post_arr             =   wp_count_posts( 'post' );

        if ( isset( $total_post_arr->publish ) ) {
            $published_post_count   =   $total_post_arr->publish;
            if ( $published_post_count == "" ) {
                $published_post_count = 0;
            }
        }

        $count_pages                =   wp_count_posts( 'page' ); 
        if ( isset( $count_pages->publish ) ) {
            $total_pages            =   $count_pages->publish; 
            if ( $total_pages == "" ) {
                $total_pages        =   0;
            }
        }

        $count_product = wp_count_posts( 'product' ); 
        if( isset( $count_product->publish ) ) {
            $total_product          =   $count_product->publish;
            if($total_product == "" ) {
                $total_product      =   0;
            }
        }

        $count_categories           =   wp_count_terms('category');
        if ( is_string( $count_categories ) && intval( $count_categories > 0 ) ) {
            $total_categories       =   intval( $count_categories );       
        }

        $count_tags                 =   wp_count_terms('post_tag');
        if ( is_string( $count_tags ) && intval( $count_tags > 0 ) ) {
            $total_tags             =   intval( $count_tags );       
        }

        $count_pro_cats             =   wp_count_terms('product_cat');
        if ( is_string( $count_pro_cats ) && intval( $count_pro_cats > 0 ) ) {
            $total_product_categories =   intval( $count_pro_cats );       
        }

        $total_record               =   $published_post_count + $total_pages + $total_product + $total_categories + $total_tags + $total_product_categories;
        $source_language            =   wpm_get_user_language();

        foreach ( $languages as $code => $language ) { 
            if ( $source_language === $code ) {
                continue;
            }
            $input_id               =   'wpmpro-autotranslate-cb-'.$code;
            ?>
         
                <li>
                    <h4>
                        <input type="checkbox" class="wpmpro-language-cb wpm-free-translation-cb" value="<?php echo esc_attr( $code ); ?>" id="<?php echo esc_attr( $input_id ); ?>" />
                        <label for="<?php echo esc_attr( $input_id ); ?>" class="wpm-cursor-pointer" style="">
                            <?php if ( isset( $language['flag'] ) ) { ?>
                                <img src=<?php echo esc_url( wpm_get_flags_dir() . $language['flag'] ); // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- Reason Using built in function doesn't work in our case, so created custom function ?> />
                            <?php }?>
                            <?php echo esc_html( $language['name'] ); ?>
                        </label>
                    </h4>
                </li>
        <?php
        } ?>
    </div>

    <div>
        <h2 style="border-bottom:1px solid #d7d7d7;padding-bottom:10px"> <?php echo esc_html__( 'What?', 'wp-multilang' ); ?></h2>
        <ul>
            <li>
                <h4>
                    <input type="checkbox" value="all" id="wpmpro-what-all-opt" class="wpm-free-translation-cb" /> 
                    <label for="wpmpro-what-all-opt" class="wpm-cursor-pointer"> <?php echo esc_html__( 'All', 'wp-multilang' ); ?> (<?php echo esc_html( $total_record) ; ?>) </label>
                </h4>
            </li>
            <li>
                <div class="wpm-auto-translate-item">
                    <div class="wpm-checkbox-section">
                        <h4>
                            <input type="checkbox" class="wpmpro-what-list wpm-free-translation-cb" value="post" id="wpmpro-at-post-cb"/> 
                            <label for="wpmpro-at-post-cb" class="wpm-cursor-pointer"> <?php echo esc_html__( 'Post', 'wp-multilang' ); ?> (<?php echo esc_html( $published_post_count ); ?>) </label>
                        </h4>
                    </div>
                    <div class="wpm-exclude-section">
                        <div class="exclude-wrapper" style="display:none;">
                            <label for="wpmpro-at-post-exclude" class="wpm-exclude-label"><?php echo esc_html__( 'Exclude Items:', 'wp-multilang' ); ?></label>
                            <select class="exclude-select" id="wpmpro-at-post-exclude" data-type="post" style="width: 200px;" multiple="multiple" placeholder="<?php echo esc_attr__( 'Select items to exclude...', 'wp-multilang' ); ?>"></select>
                        </div>
                    </div>
                </div>
            </li>
            <li>
                <div class="wpm-auto-translate-item">
                    <div class="wpm-checkbox-section">
                        <h4>
                            <input type="checkbox" class="wpmpro-what-list wpm-free-translation-cb" value="page" id="wpmpro-at-page-cb"/> 
                            <label for="wpmpro-at-page-cb" class="wpm-cursor-pointer"> <?php echo esc_html__( 'Pages', 'wp-multilang' );?> (<?php echo esc_html( $total_pages ); ?>) </label>
                        </h4>
                    </div>
                    <div class="wpm-exclude-section">
                        <div class="exclude-wrapper" style="display:none;">
                            <label for="wpmpro-at-page-exclude" class="wpm-exclude-label"><?php echo esc_html__( 'Exclude Items:', 'wp-multilang' ); ?></label>
                            <select class="exclude-select" id="wpmpro-at-page-exclude" data-type="page" style="width: 200px;" multiple="multiple" placeholder="<?php echo esc_attr__( 'Select items to exclude...', 'wp-multilang' ); ?>"></select>
                        </div>
                    </div>
                </div>
            </li>
            <li>
                <div class="wpm-auto-translate-item">
                    <div class="wpm-checkbox-section">
                        <h4>
                            <input type="checkbox" class="wpmpro-what-list wpm-free-translation-cb" value="product" id="wpmpro-at-product-cb"/> 
                            <label for="wpmpro-at-product-cb" class="wpm-cursor-pointer"> <?php echo esc_html__( 'Product Post Type', 'wp-multilang' ); ?>  (<?php echo esc_html( $total_product ); ?>) </label>
                        </h4>
                    </div>
                    <div class="wpm-exclude-section">
                        <div class="exclude-wrapper" style="display:none;">
                            <label for="wpmpro-at-product-exclude" class="wpm-exclude-label"><?php echo esc_html__( 'Exclude Items:', 'wp-multilang' ); ?></label>
                            <select class="exclude-select" id="wpmpro-at-product-exclude" data-type="product" style="width: 200px;" multiple="multiple" placeholder="<?php echo esc_attr__( 'Select items to exclude...', 'wp-multilang' ); ?>"></select>
                        </div>
                    </div>
                </div>
            </li>
            <li>
                <div class="wpm-auto-translate-item">
                    <div class="wpm-checkbox-section">
                        <h4>
                            <input type="checkbox" class="wpmpro-what-list wpm-free-translation-cb" value="category" id="wpmpro-at-category-cb"/> 
                            <label for="wpmpro-at-category-cb" class="wpm-cursor-pointer"> <?php echo esc_html__( 'Categories', 'wp-multilang' ); ?>  (<?php echo esc_html( $total_categories ); ?>) </label>
                        </h4>
                    </div>
                    <div class="wpm-exclude-section">
                        <div class="exclude-wrapper" style="display:none;">
                            <label for="wpmpro-at-category-exclude" class="wpm-exclude-label"><?php echo esc_html__( 'Exclude Items:', 'wp-multilang' ); ?></label>
                            <select class="exclude-select" id="wpmpro-at-category-exclude" data-type="category" style="width: 200px;" multiple="multiple" placeholder="<?php echo esc_attr__( 'Select items to exclude...', 'wp-multilang' ); ?>"></select>
                        </div>
                    </div>
                </div>
            </li>
            <li>
                <div class="wpm-auto-translate-item">
                    <div class="wpm-checkbox-section">
                        <h4>
                            <input type="checkbox" class="wpmpro-what-list wpm-free-translation-cb" value="post_tag" id="wpmpro-at-post_tag-cb"/> 
                            <label for="wpmpro-at-post_tag-cb" class="wpm-cursor-pointer"> <?php echo esc_html__( 'Post Tags', 'wp-multilang' ); ?>  (<?php echo esc_html( $total_tags ); ?>) </label>
                        </h4>
                    </div>
                    <div class="wpm-exclude-section">
                        <div class="exclude-wrapper" style="display:none;">
                            <label for="wpmpro-at-post_tag-exclude" class="wpm-exclude-label"><?php echo esc_html__( 'Exclude Items:', 'wp-multilang' ); ?></label>
                            <select class="exclude-select" id="wpmpro-at-post_tag-exclude" data-type="post_tag" style="width: 200px;" multiple="multiple" placeholder="<?php echo esc_attr__( 'Select items to exclude...', 'wp-multilang' ); ?>"></select>
                        </div>
                    </div>
                </div>
            </li>
            <li>
                <div class="wpm-auto-translate-item">
                    <div class="wpm-checkbox-section">
                        <h4>
                            <input type="checkbox" class="wpmpro-what-list wpm-free-translation-cb" value="product_cat" id="wpmpro-at-product_cat-cb"/> 
                            <label for="wpmpro-at-product_cat-cb" class="wpm-cursor-pointer"> <?php echo esc_html__( 'Product Categories', 'wp-multilang' ); ?>  (<?php echo esc_html( $total_product_categories ); ?>) </label>
                        </h4>
                    </div>
                    <div class="wpm-exclude-section">
                        <div class="exclude-wrapper" style="display:none;">
                            <label for="wpmpro-at-product_cat-exclude" class="wpm-exclude-label"><?php echo esc_html__( 'Exclude Items:', 'wp-multilang' ); ?></label>
                            <select class="exclude-select" id="wpmpro-at-product_cat-exclude" data-type="product_cat" style="width: 200px;" multiple="multiple" placeholder="<?php echo esc_attr__( 'Select items to exclude...', 'wp-multilang' ); ?>"></select>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>

    <div id="wpmpro-translation-success-message" style="display:none">
        <h2 style="color:green"><?php echo esc_html__( 'Translation Successful', 'wp-multilang' ); ?></h2>
    </div>

    <div style="height:10px;background:#ebebeb;border-radius: 5px;margin-bottom:10px;text-align:center;display:none" id="wpmpro-parent-progress-bar">
        <div style="height:10px;background:green;border-radius: 5px;width:0%" id="wpmpro-child-progress-bar">
            
        </div>
        <b style="font-size:14px;text-transform:uppercase" id="wpmpro-progress_count">0%</b>
    </div>

    <div>
        <button class="button button-primary" id="wpmpro-translate" style="display:block" type="button"><?php echo esc_html__('Start Translation', 'wp-multilang') ?></button>
        <button class="button button-primary" id="wpmpro-translate-hide" style="display:none" type="button"><?php echo esc_html__('Translating...', 'wp-multilang') ?></button>
    </div>
    <?php do_action( 'wpm_display_license_status_msg' ); ?>

</div>

<?php

// Handle autotranslation in pro version
$main_params = array(
    'ajax_url'                          => admin_url( 'admin-ajax.php' ),
    'total_record'                      => $total_record,
    'published_post_count'              => $published_post_count,
    'total_pages'                       => $total_pages,
    'total_product'                     => $total_product,
    'total_categories'                  => $total_categories,
    'total_tags'                        => $total_tags,
    'total_product_categories'          => $total_product_categories,
    'source_language' => function_exists('wpm_get_user_language') ? wpm_get_user_language() : 'en',
    'target_language' => function_exists('wpm_get_language') ? wpm_get_language() : 'en',
    'is_pro_active'                 =>  wpm_is_pro_active(),
    'wpmpro_autotranslate_nonce'        => wp_create_nonce( 'wpmpro-autotranslate-nonce' ),
);
$main_params    =   apply_filters( 'wpm_localize_autotranslate_params', $main_params );
do_action( 'wpmpro_autotranslate_enqueue_script', $main_params );
