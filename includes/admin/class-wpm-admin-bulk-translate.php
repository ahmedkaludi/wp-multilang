<?php
/**
 * Bulk Translate
 *
 * @category Admin
 * @package  WPM/Includes/Admin
 * @since 	 2.4.17
 */

namespace WPM\Includes\Admin;

if (!defined("ABSPATH")) {
    exit();
}

class WPM_Bulk_Translate
{
    protected $current_screen;

    protected $screens_array = ["edit-post"];

    /**
     * Constructor
     * @since	2.4.17
     * */
    public function __construct()
    {
        add_action("current_screen", [$this, "init"]);
        add_action("admin_enqueue_scripts", [$this, "load_scripts"]);
        add_action("wpm_admin_field_import_translations", [
            $this,
            "render_import_translations",
        ]);
        // add_action( 'admin_post_wpm_import_translations', array( $this, 'import_translations' ) );
        add_action("admin_init", [$this, "import_translations"]);
    }

    /**
     * Load scripts and styles
     * @since 	2.4.17
     * */
    public function load_scripts()
    {
        if (
            is_object($this->current_screen) &&
            isset($this->current_screen->base) &&
            in_array($this->current_screen->base, ["edit", "upload"])
        ) {
            $suffix = defined("SCRIPT_DEBUG") && SCRIPT_DEBUG ? "" : ".min";

            wp_register_script(
                "wpm-bulk-translate",
                wpm_asset_path("scripts/wpm-bulk-translate" . $suffix . ".js"),
                ["jquery"],
                WPM_VERSION,
                true
            );

            $translator_params = [
                "ajax_url" => admin_url("admin-ajax.php"),
                "wpm_bulk_translate_security_nonce" => wp_create_nonce(
                    "wpm_bulk_translate_security_nonce"
                ),
            ];

            wp_localize_script(
                "wpm-bulk-translate",
                "wpm_bulk_translate_params",
                $translator_params
            );

            wp_enqueue_script("wpm-bulk-translate");

            wp_enqueue_style(
                "wpm-bulk-translate-style",
                wpm_asset_path(
                    "styles/admin/wpm-bulk-translate" . $suffix . ".css"
                ),
                [],
                WPM_VERSION
            );
        }
    }

    /**
     * Initialize bulk action hooks
     * $current_screen 	object
     * @since 	2.4.17
     * */
    public function init($current_screen)
    {
        $this->current_screen = $current_screen;
        add_filter("bulk_actions-{$current_screen->id}", [$this, "add_action"]);
        add_filter(
            "handle_bulk_actions-{$current_screen->id}",
            [$this, "handle_bulk_action"],
            10,
            2
        );
        add_action("admin_footer", [$this, "display_form"]);
        add_action("admin_notices", [$this, "display_notices"]);
        if ("edit" === $current_screen->base) {
            add_filter("wp_redirect", [$this, "parse_request_before_redirect"]);
        }
    }

    /**
     * Add translate action in dropdown
     * @param 	$actions 	array
     * @return 	$actions 	array
     * @since 	2.4.17
     * */
    public function add_action($actions)
    {
        $actions["wpm_translate_action"] = __("WPM Translate", "wp-multilang");
        return $actions;
    }

    /**
     * Handle translate action
     * @param 	$sendback 	string
     * @param 	$action 	string
     * @since 	2.4.17
     * */
    public function handle_bulk_action($sendback, $action)
    {
        if ("wpm_translate_action" !== $action) {
            return $sendback;
        }

        check_admin_referer("wpm_bulk_translate", "_wpm_bulk_translate_nonce");

        $query_args = $this->parse_bulk_request($_GET); // Errors returned by this method are already handled by `parse_request_before_redirect()`.
        if (!is_wp_error($query_args)) {
            $error = $this->perform_bulk_action(
                $query_args["item_ids"],
                $query_args["wpm_bt_file_format"]
            );
            $this->add_settings_error($error);
        }

        return $sendback;
    }

    /**
     * Render bulk action form
     * @since 	2.4.17
     * */
    public function display_form()
    {
        if (
            is_object($this->current_screen) &&
            !empty($this->current_screen->id) &&
            in_array($this->current_screen->id, $this->screens_array)
        ) {
            require_once __DIR__ .
                "/views/bulk-translate/template-bulk-translate.php";
        }
    }

    /**
     * Parse the bulk translate request
     * @since 	2.4.17
     * */
    public function parse_bulk_request($request)
    {
        $args = [];

        $screens_content_keys = [
            "upload" => "media",
            "edit" => "post",
        ];

        if (
            !empty($this->current_screen) &&
            isset($screens_content_keys[$this->current_screen->base])
        ) {
            $item_key = $screens_content_keys[$this->current_screen->base];

            if (isset($request[$item_key]) && is_array($request[$item_key])) {
                $args["item_ids"] = array_filter(
                    array_map("absint", $request[$item_key])
                );
            }
        }

        if (empty($args["item_ids"])) {
            return new \WP_Error(
                "wpm_no_items_selected",
                esc_html__(
                    "No item has been selected. Please make sure to select at least one item to be translated.",
                    "wp-multilang"
                )
            );
        }

        $args["wpm_bt_file_format"] = sanitize_key(
            $request["wpm_bt_file_format"]
        );

        return $args;
    }

    /**
     * Handle the errors if no post is selected
     * @since 	2.4.17
     * */
    public function parse_request_before_redirect($sendback)
    {
        if (
            !isset($_GET["action"], $_REQUEST["_wpm_bulk_translate_nonce"]) ||
            "wpm_translate_action" !== $_GET["action"] ||
            !wp_verify_nonce(
                $_REQUEST["_wpm_bulk_translate_nonce"],
                "wpm_bulk_translate"
            )
        ) {
            return $sendback;
        }

        $error = $this->parse_bulk_request($_GET);

        if (is_wp_error($error)) {
            $this->add_settings_error($error);
        }

        return $sendback;
    }

    /**
     * Add errors to transient
     * @param 	$error 	WP_Error
     * @since 	2.4.17
     * */
    private function add_settings_error(\WP_Error $error)
    {
        if (!$error->has_errors()) {
            return;
        }

        $this->add_errors($error);

        set_transient(
            "wpm_bulk_translate_errors_" . get_current_user_id(),
            get_settings_errors("wpm_bulk_errors")
        );
    }

    /**
     * Add new settings error
     * @param 	$error 	WP_Error
     * @since 	2.4.17
     * */
    public function add_errors($error)
    {
        if (!$error->has_errors()) {
            return;
        }

        foreach ($error->get_error_codes() as $error_code) {
            // Extract the "error" type.
            $data = $error->get_error_data($error_code);
            $type = empty($data) || !is_string($data) ? "error" : $data;

            $message = wp_kses(
                implode("<br>", $error->get_error_messages($error_code)),
                [
                    "a" => ["href"],
                    "br" => [],
                    "code" => [],
                    "em" => [],
                ]
            );

            add_settings_error("wpm_bulk_errors", $error_code, $message, $type);
        }
    }

    /**
     * Display bulk translate error
     * @since 	2.4.17
     * */
    public function display_notices()
    {
        $transient_name = "wpm_bulk_translate_errors_" . get_current_user_id();

        /** @var string[][] */
        $notices = get_transient($transient_name);

        if (empty($notices)) {
            return;
        }

        foreach ($notices as $notice) {
            /*
             * Unpacking operator `...` supports string-keyed associative array only since PHP 8.0.0.
             */
            add_settings_error(...array_values($notice));
        }

        settings_errors("wpm_bulk_errors");
        delete_transient($transient_name);
    }

    /**
     * Perform bulk export action
     * @param 	$post_ids 	array
     * @param 	$file_type 	string
     * @since 	2.4.17
     * */
    public function perform_bulk_action($post_ids, $file_type)
    {
        check_admin_referer("wpm_bulk_translate", "_wpm_bulk_translate_nonce");

        $default_lang = wpm_get_default_language();
        $current_lang = wpm_get_language();
        $languages = wpm_get_languages();

        if ($default_lang == $current_lang) {
            return new \WP_Error(
                "invalid-target-languages",
                __(
                    "Error: Source and destination languages cannot be same.",
                    "wp-multilang"
                )
            );
        }

        $explode_file_type = explode("_", $file_type);
        $extension = "";
        $version = "";
        if (isset($explode_file_type[0])) {
            $extension = $explode_file_type[0];
        }
        if (isset($explode_file_type[1])) {
            $version = str_replace("-", "", $explode_file_type[1]);
        }

        $posts = get_posts([
            "post__in" => $post_ids,
            "posts_per_page" => count($post_ids),
            "orderby" => "post__in",
            "ignore_sticky_posts" => true,
            "update_post_meta_cache" => false,
            "update_post_term_cache" => false,
            "post_status" => "any",
        ]);

        if (!empty($posts) && is_array($posts)) {
            $xliff_content = "";

            // foreach ($posts as $post) {

            switch ($version) {
                case "20":
                    $xliff_content = $this->generate_xliff_20(
                        $posts,
                        $languages,
                        $default_lang,
                        $current_lang
                    );

                    break;

                case "21":
                    $xliff_content = $this->generate_xliff_21(
                        $posts,
                        $languages,
                        $default_lang,
                        $current_lang
                    );

                    break;

                default:
                    // 1.2

                    $xliff_content = $this->generate_xliff_12(
                        $posts,
                        $languages,
                        $default_lang,
                        $current_lang
                    );

                    break;

                // }
            }

            // Code to download single file

            $source_lang = $languages[$default_lang]["locale"];
            $target_lang = $languages[$current_lang]["locale"];
            $file_name =
                $source_lang .
                "_" .
                $target_lang .
                "_" .
                date("Y-m-d_H-i-s") .
                ".xliff";

            header("Content-Type: application/xml");
            header(
                'Content-Disposition: attachment; filename="' . $file_name . '"'
            );
            header("Content-Length: " . strlen($xliff_content));

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped --Reason: escaping is already done above.
            echo $xliff_content;

            exit();
        }
    }

    /**
     * Format data to use in xliff file format creation
     * @param 	$post 			WP_Post
     * @param 	$languages 		array
     * @param 	$default_lang 	string
     * @param 	$current_lang 	string
     * @since 	2.4.17
     * */
    public function format_xliff_data(
        $posts,
        $languages,
        $default_lang,
        $current_lang
    ) {
        $data = [];

        foreach ($posts as $key => $post) {
            $data[$key]["id"] = $post->ID;
            $data[$key]["post_type"] = $post->post_type;
            $data[$key]["source_lang"] = str_replace(
                "_",
                "-",
                $languages[$default_lang]["locale"]
            );
            $data[$key]["target_lang"] = str_replace(
                "_",
                "-",
                $languages[$current_lang]["locale"]
            );
            $data[$key]["original"] =
                "WP Multilang |" . WPM_VERSION . "|" . get_home_url();

            $source_post_title = wpm_translate_string(
                $post->post_title,
                $default_lang
            );
            $target_post_title = "";
            if (strpos($post->post_title, "[:]") !== false) {
                $target_post_title = wpm_translate_string(
                    $post->post_title,
                    $current_lang
                );
                if (!empty($target_post_title)) {
                    $target_post_title = "<![CDATA[{$target_post_title}]]>";
                }
            }

            $data[$key]["source_post_title"] = $source_post_title;
            $data[$key]["target_post_title"] = $target_post_title;

            $source_content = wpm_translate_string(
                $post->post_content,
                $default_lang
            );
            $target_content = "";
            if (strpos($post->post_content, "[:]") !== false) {
                $target_content = wpm_translate_string(
                    $post->post_content,
                    $current_lang
                );
                if (!empty($target_content)) {
                    $target_content = "<![CDATA[{$target_content}]]>";
                }
            } else {
            }
            $data[$key]["source_content"] = $source_content;
            $data[$key]["target_content"] = $target_content;

            $terms = [];
            $post_terms = wp_get_post_categories($post->ID);
            if (!empty($post_terms) && is_array($post_terms)) {
                foreach ($post_terms as $post_term) {
                    $term = get_term($post_term);
                    if (is_object($term) && isset($term->term_id)) {
                        $terms[] = [
                            "term_id" => $term->term_id,
                            "name" => $term->name,
                        ];
                    }
                }
            }
            $data[$key]["terms"] = $terms;
        }

        $data = apply_filters("wpm_filter_xliff_data", $data, $post);

        return $data;
    }

    /**
     * Generate translation file version 1.2
     * @param 	$post 			WP_Post
     * @param 	$languages 		array
     * @param 	$default_lang 	string
     * @param 	$current_lang 	string
     * @since 	2.4.17
     * */
    public function generate_xliff_12(
        $posts,
        $languages,
        $default_lang,
        $current_lang
    ) {
        $original = get_home_url();
        $product_version = WPM_VERSION;
        $unit_id = 1;

        $formatted_data = $this->format_xliff_data(
            $posts,
            $languages,
            $default_lang,
            $current_lang
        );

        $xliff_template = <<<XLIFF
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
	<file datatype="plaintext" original="{$original}" product-name="WP Multilang" product-version="{$product_version}" source-language="{$formatted_data[0]["source_lang"]}" target-language="{$formatted_data[0]["target_lang"]}"> 
		<body>
XLIFF;

        foreach ($formatted_data as $key => $data) {
            $xliff_template .= <<<XLIFF

			<group restype="x-post" resname="{$data["id"]}">
				<trans-unit id="{$unit_id}" restype="x-post_title">
					<source><![CDATA[{$data["source_post_title"]}]]></source>
					<target>{$data["target_post_title"]}</target>
				</trans-unit>
				
XLIFF;

            $unit_id++;

            $xliff_template .= <<<XLIFF

				<trans-unit id="{$unit_id}" restype="x-post_content">
					<source><![CDATA[{$data["source_content"]}]]></source>
					<target>{$data["target_content"]}</target>
				</trans-unit>
XLIFF;

            $xliff_template .= <<<XLIFF

				</group>
XLIFF;

            $unit_id++;
        }

        foreach ($formatted_data as $key => $data) {
            if (!empty($data["terms"])) {
                foreach ($data["terms"] as $tkey => $term) {
                    $xliff_template .= <<<XLIFF

			<group restype="x-term" resname="{$term["term_id"]}">
				<trans-unit id="{$unit_id}" restype="x-name">
					<source><![CDATA[{$term["name"]}]]></source>
					<target><![CDATA[{$term["name"]}]]></target>
				</trans-unit>
			</group>
XLIFF;

                    $unit_id++;
                }
            }
        }

        $xliff_template .= <<<XLIFF

		</body>
	</file>
</xliff>	
XLIFF;

        return $xliff_template;
    }

    /**
     * Generate translation file version 20
     * @param 	$post 			WP_Post
     * @param 	$languages 		array
     * @param 	$default_lang 	string
     * @param 	$current_lang 	string
     * @since 	2.4.17
     * */
    public function generate_xliff_20(
        $posts,
        $languages,
        $default_lang,
        $current_lang
    ) {
        $formatted_data = $this->format_xliff_data(
            $posts,
            $languages,
            $default_lang,
            $current_lang
        );

        $xliff_template = <<<XLIFF
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="{$formatted_data[0]["source_lang"]}" trgLang="{$formatted_data[0]["target_lang"]}">
	<file id="1" original="{$formatted_data[0]["original"]}"> 
XLIFF;

        $xliff_template .= $this->render_xliff_groups($formatted_data);

        $xliff_template .= <<<XLIFF

	</file>
</xliff>	
XLIFF;

        return $xliff_template;
    }

    /**
     * Generate translation file version 21
     * @param 	$post 			WP_Post
     * @param 	$languages 		array
     * @param 	$default_lang 	string
     * @param 	$current_lang 	string
     * @since 	2.4.17
     * */
    public function generate_xliff_21(
        $posts,
        $languages,
        $default_lang,
        $current_lang
    ) {
        $formatted_data = $this->format_xliff_data(
            $posts,
            $languages,
            $default_lang,
            $current_lang
        );

        $xliff_template = <<<XLIFF
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.1" version="2.1" srcLang="{$formatted_data[0]["source_lang"]}" trgLang="{$formatted_data[0]["target_lang"]}">
	<file id="1" original="{$formatted_data[0]["original"]}"> 
XLIFF;

        $xliff_template .= $this->render_xliff_groups($formatted_data);

        $xliff_template .= <<<XLIFF

	</file>
</xliff>	
XLIFF;

        return $xliff_template;
    }

    /**
     * Prepare xliff group content
     * @param 	$formatted_data 	array
     * @return 	$xliff_template 	string
     * @since 	2.4.17
     * */
    public function render_xliff_groups($formatted_data)
    {
        $group_id = 1;
        $unit_id = 1;
        $xliff_template = "";

        foreach ($formatted_data as $key => $data) {
            $xliff_template .= <<<XLIFF

		<group id="{$group_id}" type="x:post" name="{$data["id"]}">
XLIFF;
            $xliff_template .= <<<XLIFF

			<unit id="{$unit_id}" type="x:post_title">
				<segment>
					<source><![CDATA[{$data["source_post_title"]}]]></source>
					<target>{$data["target_post_title"]}</target>
				</segment>
			</unit>
XLIFF;

            $unit_id++;

            $xliff_template .= <<<XLIFF

			<unit id="{$unit_id}" type="x:post_content">
				<segment>
					<source><![CDATA[{$data["source_content"]}]]></source>
					<target>{$data["target_content"]}</target>
				</segment>
			</unit>
		</group> 
XLIFF;

            $unit_id++;
            $group_id++;
        }

        foreach ($formatted_data as $key => $data) {
            if (!empty($data["terms"])) {
                foreach ($data["terms"] as $tkey => $term) {
                    $xliff_template .= <<<XLIFF

		<group id="{$group_id}" type="x:term" name="{$term["term_id"]}">
			<unit id="{$unit_id}" type="x:name">
				<segment>
					<source><![CDATA[{$term["name"]}]]></source>
					<target><![CDATA[{$term["name"]}]]></target>
				</segment>
			</unit>
		</group>
XLIFF;
                    $group_id++;
                    $unit_id++;
                }
            }
        }

        return $xliff_template;
    }

    /**
     * Import translation from xliff file
     * @param 	$value 	array
     * @since 	2.4.17
     * */
    public function render_import_translations($value)
    {
        ?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<h4><?php echo esc_html($value["title"]); ?></h4>
			</th>
			<td class="forminp">
				<p>
					<form enctype="multipart/form-data" type="post" id="wpm-import-xliff-form" action="<?php echo esc_url(
         admin_url("admin-post.php")
     ); ?>">
						<input type="file" name="wpm_import_xliff_file" id="wpm-import-xliff-file">
						<button type="submit" id="wpm-import-xliff-btn" class="button js-wpm-action" name="wpm_import_xliff_btn" value="Import xliff data"><?php echo esc_html__(
          "Import File",
          "wp-multilang"
      ); ?></button>
						<input type="hidden" name="action" value="wpm_import_translations">
						<?php wp_nonce_field("wpm-xliff-nonce", "wpm_xliff_security"); ?>
					</form>
				</p>
				<p class="description"><?php echo esc_html__(
        "Import translated xliff file.",
        "wp-multilang"
    ); ?></p>
			</td>
		</tr>
		<?php
    }

    /**
     * Get uploaded xliff file contents
     * @since 	2.4.17
     * */
    public function import_translations()
    {
        if (!current_user_can("manage_options")) {
            return;
        }

        if (!isset($_POST["wpm_xliff_security"])) {
            return;
        }

        if (!wp_verify_nonce($_POST["wpm_xliff_security"], "wpm-xliff-nonce")) {
            $error = new \WP_Error(
                "wpm_nonce_error",
                __("Unauthorized to import the file", "wp-multilang")
            );
            if (is_wp_error($error)) {
                $this->add_settings_error($error);
            }
            return;
        }

        if (isset($_POST["wpm_import_xliff_btn"])) {
            $btn_text = sanitize_text_field(
                wp_unslash($_POST["wpm_import_xliff_btn"])
            );
            if ($btn_text == "Import xliff data") {
                if (
                    !empty($_FILES["wpm_import_xliff_file"]) &&
                    !empty($_FILES["wpm_import_xliff_file"]["tmp_name"])
                ) {
                    $file_name = sanitize_text_field(
                        $_FILES["wpm_import_xliff_file"]["name"]
                    );
                    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $filepath = sanitize_text_field(
                        $_FILES["wpm_import_xliff_file"]["tmp_name"]
                    );

                    if ($extension === "xliff") {
                        $error = $this->import_data_from_file($filepath);
                        if (is_wp_error($error)) {
                            $this->add_settings_error($error);
                        }
                    }
                }
            }
        }
    }

    /**
     * Import data from xliff file
     * @param 	$filepath 	string
     * @since 	2.4.17
     * */
    public function import_data_from_file($filepath)
    {
        $post_data = [];
        $target_lang = "";
        $file_contents = file_get_contents($filepath);
        if (false === $file_contents) {
            return new \WP_Error(
                "wpm_import_file_contents_error",
                __(
                    "Something went wrong during the file import.",
                    "wp-multilang"
                )
            );
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument("1.0", "UTF-8");
        $isxml = $dom->loadHTML(
            $file_contents,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        if ($isxml) {
            $dom->load($filepath);
            $xliff_tag = $dom->getElementsByTagName("xliff");
            if (is_object($xliff_tag) && !empty($xliff_tag->length)) {
                foreach ($xliff_tag as $xliff) {
                    $version = $xliff->getAttribute("version");
                    $target_lang = $xliff->getAttribute("trgLang");
                    $file_tags = $xliff->getElementsByTagName("file");

                    foreach ($file_tags as $file_tag) {
                        if (is_object($file_tag) && !empty($file_tag)) {
                            if (empty($target_lang)) {
                                $target_lang = $file_tag->getAttribute(
                                    "target-language"
                                );
                            }

                            switch ($version) {
                                case "1.2":
                                    $post_data = $this->xml_to_array_1_2(
                                        $file_tag
                                    );
                                    break;

                                case "2.0":
                                case "2.1":
                                    $post_data = $this->xml_to_array_2_1(
                                        $file_tag
                                    );
                                    break;
                            }
                        }
                    }
                }
            }
        }

        if (
            is_array($post_data) &&
            !empty($post_data) &&
            !empty($target_lang)
        ) {
            $lang = explode("-", $target_lang)[0];
            $languages = wpm_get_languages();

            if (array_key_exists($lang, $languages)) {
                foreach ($post_data as $post_id => $post) {
                    $title = "";
                    $content = "";
                    $get_post = get_post($post_id);
                    if (!empty($post["post_title"])) {
                        $post_title = $get_post->post_title;
                        $title = wpm_set_new_value(
                            $post_title,
                            $post["post_title"],
                            [],
                            $lang
                        );
                    }
                    if (!empty($post["post_content"])) {
                        $post_content = $get_post->post_content;
                        $content = wpm_set_new_value(
                            $post_content,
                            $post["post_content"],
                            [],
                            $lang
                        );
                    }

                    if (!empty($title) && !empty($content)) {
                        $update_data = [
                            "ID" => $post_id,
                            "post_title" => $title,
                            "post_content" => $content,
                        ];

                        wp_update_post($update_data);
                    }
                }
                return new \WP_Error(
                    "wpm_import_posts_success",
                    __("Post Translation updated", "wp-multilang"),
                    "success"
                );
            } else {
                return new \WP_Error(
                    "wpm_import_file_contents_error",
                    __(
                        'Error: You are trying to import a file in a language which doesn\'t exist on your site',
                        "wp-multilang"
                    )
                );
            }
        } else {
            return new \WP_Error(
                "wpm_import_file_contents_error",
                __(
                    'Error: You are trying to import a file in a language which doesn\'t exist on your site',
                    "wp-multilang"
                )
            );
        }
    }

    /**
     * Convert xml data into an array for xliff 1.2 version
     * @param 	$file_tag 	Document object
     * @since 	2.4.17
     * @return 	$post_data 	array
     * */
    public function xml_to_array_1_2($file_tag)
    {
        $post_data = [];
        $post_id = "";

        $body_tag = $file_tag->getElementsByTagName("body");
        if (is_object($body_tag) && !empty($body_tag->length)) {
            foreach ($body_tag as $body) {
                $group_tag = $body->getElementsByTagName("group");
                if (is_object($group_tag) && !empty($group_tag->length)) {
                    foreach ($group_tag as $group) {
                        $group_attr = [];
                        foreach ($group->attributes as $attr) {
                            $group_attr[$attr->name] = $attr->value;
                        }

                        if (
                            is_array($group_attr) &&
                            !empty($group_attr["restype"])
                        ) {
                            if (
                                $group_attr["restype"] == "x-post" &&
                                !empty($group_attr["resname"])
                            ) {
                                $post_id = intval($group_attr["resname"]);
                                if ($post_id > 0) {
                                    $trans_unit = $group->getElementsByTagName(
                                        "trans-unit"
                                    );
                                    if (
                                        is_object($trans_unit) &&
                                        !empty($trans_unit->length)
                                    ) {
                                        foreach ($trans_unit as $trans) {
                                            $restype = $trans->getAttribute(
                                                "restype"
                                            );
                                            if ($restype == "x-post_title") {
                                                $target = $trans->getElementsByTagName(
                                                    "target"
                                                );
                                                if (
                                                    is_object($target) &&
                                                    !empty($target->item(0))
                                                ) {
                                                    $post_data[$post_id][
                                                        "post_title"
                                                    ] = sanitize_text_field(
                                                        $target->item(0)
                                                            ->nodeValue
                                                    );
                                                }
                                            } elseif (
                                                $restype == "x-post_content"
                                            ) {
                                                $target = $trans->getElementsByTagName(
                                                    "target"
                                                );
                                                if (
                                                    is_object($target) &&
                                                    !empty($target->item(0))
                                                ) {
                                                    $post_data[$post_id][
                                                        "post_content"
                                                    ] = sanitize_text_field(
                                                        $target->item(0)
                                                            ->nodeValue
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $post_data;
    }

    /**
     * Convert xml data into an array for xliff 2.0 & 2.1 version
     * @param 	$file_tag 	Document object
     * @since 	2.4.17
     * @return 	$post_data 	array
     * */
    public function xml_to_array_2_1($file_tag)
    {
        $post_data = [];
        $post_id = "";

        $group_tag = $file_tag->getElementsByTagName("group");
        if (is_object($group_tag) && !empty($group_tag->length)) {
            foreach ($group_tag as $group) {
                $group_attr = [];
                foreach ($group->attributes as $attr) {
                    $group_attr[$attr->name] = $attr->value;
                }

                if (
                    !empty($group_attr["type"]) &&
                    $group_attr["type"] == "x:post"
                ) {
                    $post_id = intval($group_attr["name"]);
                    $units = $group->getElementsByTagName("unit");
                    if (is_object($units) && !empty($units->length)) {
                        foreach ($units as $unit) {
                            $unit_type = $unit->getAttribute("type");
                            $segments = $unit->getElementsByTagName("segment");
                            if (is_object($segments) && !empty($segments)) {
                                if ($unit_type == "x:post_title") {
                                    foreach ($segments as $segment) {
                                        $target = $segment->getElementsByTagName(
                                            "target"
                                        );
                                        if (
                                            is_object($target) &&
                                            !empty($target->item(0))
                                        ) {
                                            $post_data[$post_id][
                                                "post_title"
                                            ] = sanitize_text_field(
                                                $target->item(0)->nodeValue
                                            );
                                        }
                                    }
                                } elseif ($unit_type == "x:post_content") {
                                    foreach ($segments as $segment) {
                                        $target = $segment->getElementsByTagName(
                                            "target"
                                        );
                                        if (
                                            is_object($target) &&
                                            !empty($target->item(0))
                                        ) {
                                            $post_data[$post_id][
                                                "post_content"
                                            ] = $target->item(0)->nodeValue;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $post_data;
    }
}
