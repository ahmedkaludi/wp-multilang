# [WP Multilang](https://wordpress.org/plugins/wp-multilang/)

Multilingual plugin for WordPress.

## Description

**WP Multilang** is a multilingual plugin for WordPress.

Translation for any post types, taxonomies, meta fields, options, text fields in miltimedia files, menus, titles and text fields in widgets.

[Home](https://wp-multilang.com/) | [Help & Tech Support](https://wp-multilang.com/contact-us/) | [Documentation](https://wp-multilang.com/docs/) | [Premium Features](https://wp-multilang.com/#features)

New Features of the plugin WP Multilang:
* Support full site editor for block based themes
* Support block based widgets
* Support different feature image for each language
* Support Smart Custom Fields Plugin
* Auto Translation ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-auto-translate-your-website-contents-using-wp-multilang/)
* Support URL Slug Translation ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-translate-url-slugs-with-selective-languages/)
* Support Base Translation ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-translate-taxonomy-bases-such-as-categories-and-tags-into-selective-languages-using-the-base-translation-option/)

Features of the plugin WP Multilang:
* 100% free.
* Translation at PHP.
* Compatible with REST.
* Support configuration for translate multidimensional arrays in options, meta fields, post content.
* Support multisite.
* Support WordPress in a sub-folder.
* Separate menu items, posts, terms, widgets, comments per language.
* Many filters for dynamic applying translation settings.
* No duplicate posts, terms, menus, widgets.
* No sub-domain for each language version.
* No additional tables in database.
* Possibility set many languages with one localization. For example, for localization in the region.
* Possibility to set custom locale for html(If installed locale is en_US, you can set locale like: en, en-UK, en-AU etc. Without installation another localization)
* Possibility for add new languages for any user with capability `manage_options`.
* Exist the role "Translator" for editing posts, terms. It can not publish or delete.
* No limits by languages or by possibilities.

**WP Multilang** compatible out of the box with the plugins:
* Elementor ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-build-a-multilingual-site-with-elementor-using-wp-multilang/)
* Divi Builder ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-build-a-multilingual-site-with-divi-builder-using-wp-multilang/)
* ACF, ACF Pro
* WooCommerce
* WooCommerce Customizer
* Gutenberg
* Yoast Seo
* Contact Form 7 (added mail tag [_language] for send user language in mail)
* WPBakery Visual Composer
* Page Builder by SiteOrigin
* NextGEN Gallery
* All in One SEO Pack
* MailChimp for WordPress
* Newsletter
* Maps Builder
* Max Mega Menu
* MasterSlider
* WP-PageNavi
* BuddyPress
* Meta Slider
* TablePress
* Download Monitor (Redefine templates for links in your theme and translate link texts)
* Better Search
* Rank Math SEO (thanks for @pratikmts)
* WPGraphQL (Add lang to the query parameters in URL. Eg: lang=en)
* Smart Custom Fields
* Team – Team Members Showcase Plugin
* Schema & Structured Data for WP & AMP
   
Supports configuration via json.   
   
Add in the root of your theme or plugin file `wpm-config.json` settings.
   
Sample configurations can be viewed in a configuration file in a folder configs in the root of the plugin.   
   
Configuration is updated after switching a theme off/on or after update any plugin.   

The plugin has filters for dynamic applying configuration for translate.   

For disabling translation set `null` into the desired configuration.
For example, you should turn off translation for a post type `post`.
There are two ways:   

1. In json.
    Create the root of the subject, or the roots of its plugin file `wpm-config.json` with:
    ```
    {
      "post_types": {
        "post": null
      }
    }
    ```

2. Through the filter.   
    Add in functions.php
    ```php
    add_filter( 'wpm_post_post_config', '__return_null' );
    ```
To enable translation pass an empty array in php `array()` or empty object in json `{}`.   

Supports translation multidimensional array of options, meta fields and post_content.   
Supports the removal of established localizations.   
Supports translation via GET parameter. Add in the GET parameter `lang` code desired language.   
Supports clean database of translations when removing the plugin. Translations are only removed from the built-in tables.   
Supports import term translations from qTranslate(by Soft79).   
Supports automatically redirect to the user's browser language, if he visits for the first time.   
   
Ideal for developers.   
   
For display language switcher in any place add the code to your template `if ( function_exists ( 'wpm_language_switcher' ) ) wpm_language_switcher ();`   
Function accepts two parameters:   
$type - 'list', 'dropdown', 'select'. Default - 'list'.   
$show - 'flag', 'name', 'both'. Default - 'both'.   
   
Or using the shortcode `wpm_lang_switcher`. It accept two not necessary parameters 'type' and 'show'.
   
Available features for translation:   
`wpm_translate_url( $url, $language = '' );` - translate url   
`wpm_translate_string( $string, $language = '' );` - translate multilingual string   
`wpm_translate_value( $value, $language = '' );` - translate multidimensional array with multilingual strings   
   
Update translation occurs at PHP. Therefore plugin has high adaptability, compatibility and easily integrates with other plugins. This is what distinguishes it among similar.   
   
Available translation html tags by JS for strings what do not have WP filters before output.   
   
Add your tags in config:   
```
"admin_html_tags": {
    "admin_screen_id": {
      "attribute": [
        "selector"
      ]
    }
}
```
Where:   
`admin_screen_id` - admin screen id.   
`attribute` - attribute what need to translate. Available 'text' - for translate text node, 'value' - for translate form values. Or other tag attribute, like 'title', 'alt'.   
`selector` - css selector for search needed tag. Each selector is a new array item.   
   
If You need to add translation for multidimentional array for repeated elements You can use custom tag 'wpm_each' for set config to each element in array.
Example, add config for each item 'title' in custom post field array:
```
"post_fields": {
    "custom_field": {
      "wpm_each": {
        "title": {}
      }
    }
}
```

For set translation uses the syntax:   
`[:en]Donec vitae orci sed dolor[:de]Cras risus ipsum faucibus ut[:]`

Added shortcode for translate text in any place:
`[wpm_translate][:en]Donec vitae orci sed dolor[:de]Cras risus ipsum faucibus ut[:][wpm_translate]`

If You translate text in established language, add lang parameter:
`[wpm_translate lang="de"][:en]Donec vitae orci sed dolor[:de]Cras risus ipsum faucibus ut[:][wpm_translate]`

Support translating from syntax qTranslate-X, WPGlobus etc.

Compatible with REST-API.   
Support transfer the required translation through option `lang` in the GET request to REST.   
Has the ability to keep recording the target language through the transmission parameter `lang` in the request.

Migration from qTranslate-X

1. Before installing/uninstalling, make database backup.
2. Deactivate qTranslate-X.
3. Install and activate WP Multilang.
4. Create in root of your theme file ‘wpm-config.json’.
5. Add all needed post types, taxonomies, options, fields to ‘wpm-config.json’. Setting from qTranslate-X not importing.
6. Import term names from qTranslate.
7. Check that everything is okay.
8. If everything is okay, remove qTranslate-X. If not, make screenshots of errors, restore database from backup and add support issue with your screenshots and description of errors.

Warning
Not compatible with:
- WP Maintenance

Known issues
Function 'get_page_by_title' not working, because in title field are stored titles for all languages. Use function 'wpm_get_page_by_title( $title )' as solution.

NOTE: Because plugins have different ways of storing data, WP Multilang is not compatible with every single plugin out-of-the-box (mostly page builders). This may result in texts not being translatable or translations not being saved. Most of these issues can be resolved using the integration options (wpm-config.json or filters) of WP Multilang.

Please try WP Multilang in a test-environment before activating it on an existing production site and always make a backup before activating!

[Home](https://wp-multilang.com/) | [Help & Tech Support](https://wp-multilang.com/contact-us/) | [Documentation](https://wp-multilang.com/docs/) | [Premium](https://wp-multilang.com/)

Support

We try our best to provide support on WordPress.org forums. However, We have a special [community support](https://wp-multilang.com/contact-us/) where you can ask us questions and get help about your WP Multilang related questions. Delivering a good user experience means a lot to us and so we try our best to reply each and every question that gets asked.

Bug Reports

Bug reports for WP Multilang are [welcomed on GitHub](https://github.com/ahmedkaludi/wp-multilang/issues). Please note GitHub is not a support forum, and issues that aren't properly qualified as bugs will be closed.

Installation
1. Upload the plugin files to the `/wp-content/plugins/wp-multilang` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Adjust languages on WP Multilang Settings page.

Upgrade Notice

Before installing or uninstalling make the site database backup before.