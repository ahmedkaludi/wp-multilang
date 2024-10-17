=== WP Multilang - Translation and Multilingual Plugin ===

Contributors: magazine3
Donate link: https://paypal.me/kaludi
Tags: localization, multilanguage, multilingual, translation, multilang
Requires at least: 4.7
Tested up to: 6.6
Stable tag: 2.4.13
Requires PHP: 5.6.20
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Multilingual plugin for WordPress. Go Multilingual in minutes with full WordPress support. Translate your site easily with this localization plugin.

== Description ==

WP Multilang is a multilingual plugin for WordPress.

Translations of post types, taxonomies, meta fields, options, text fields in miltimedia files, menus, titles and text fields in widgets.

[Home](https://wp-multilang.com/) | [Help & Tech Support](https://wp-multilang.com/contact-us/) | [Documentation](https://wp-multilang.com/docs/) | [Premium Features](https://wp-multilang.com/#features)

== New Features of the plugin WP Multilang ==
* Support full site editor for block based themes
* Support block based widgets
* Support different feature image for each language
* Auto Translation ( [available in premium version](https://wp-multilang.com/) ) - [View Tutorial](https://wp-multilang.com/docs/)
* Support URL Slug Translation ( [available in premium version](https://wp-multilang.com/) ) - [View Tutorial](https://wp-multilang.com/docs/)

== Features of the plugin WP Multilang ==

* 100% free.
* Translation at PHP.
* Compatible with REST.
* Support configuration for translate multidimensional arrays in options, meta fields, post content.
* Support multisite.
* Support WordPress in sub-folder.
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

== WP Multilang compatible with plugins ==

* Elementor ( [available in premium version](https://wp-multilang.com/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-build-a-multilingual-site-with-elementor-using-wp-multilang/)
* Divi Builder ( [available in premium version](https://wp-multilang.com/) ) - [View Tutorial](https://wp-multilang.com/docs/)
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

Manage translation settings via json.

Add in the root of your theme or plugin file `wpm-config.json`.

Sample configurations can be viewed in config files in folder 'configs' in root the plugin.

Configuration is updated after switching theme, enable or update any plugins.

The plugin has filters for dynamic application configuration for translate.

For turn off translation, set `null` into the desired configuration.
For example, you must turn off translation for a post type `post`.
There are two ways:

1. In json.
    Create in root of a theme or a plugin file `wpm-config.json` with:
    `{
       "post_types": {
         "post": null
       }
     }`

2. Through the filter.
    Add in functions.php
    `add_filter( 'wpm_post_post_config', '__return_null' );`

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
`
"admin_html_tags": {
    "admin_screen_id": {
      "attribute": [
        "selector"
      ]
    }
}
`

Where:
`admin_screen_id` - admin screen id.
`attribute` - attribute what need to translate. Available 'text' - for translate text node, 'value' - for translate form values. Or other tag attribute, like 'title', 'alt'.
`selector` - css selector for search needed tag. Each selector is a new array item.

If You need to add translation for multidimentional array for repeated elements You can use custom tag 'wpm_each' for set config to each element in array.
Example, add config for each item 'title' in custom post field array:
`
"post_fields": {
    "custom_field": {
      "wpm_each": {
        "title": {}
      }
    }
}
`

For set translation uses the syntax:
`[:en]Donec vitae orci sed dolor[:de]Cras risus ipsum faucibus ut[:]`

Added shortcode for translate text in any place:
`[wpm_translate][:en]Donec vitae orci sed dolor[:de]Cras risus ipsum faucibus ut[:][wpm_translate]`

If You translate text in established language, add lang parameter:
`[wpm_translate lang="de"][:en]Donec vitae orci sed dolor[:de]Cras risus ipsum faucibus ut[:][wpm_translate]`

Support translating from syntax qTranslate, qTranslate-X, WPGlobus etc.

Compatible with REST-API.
Support transfer the required translation through option `lang` in the GET request to REST.
Has the ability to keep recording the target language through the transmission parameter `lang` in the request.

== Migration from qTranslate-X ==

1. Before installing/uninstalling, make database backup.
2. Deactivate qTranslate-X.
3. Install and activate WP Multilang.
4. Create in root of your theme file ‘wpm-config.json’.
5. Add all needed post types, taxonomies, options, fields to ‘wpm-config.json’. Setting from qTranslate-X not importing.
6. Import term names from qTranslate.
7. Check that everything is okay.
8. If everything is okay, remove qTranslate-X. If not, make screenshots of errors, restore database from backup and add support issue with your screenshots and description of errors.

== Warning ==

Do not support different slug for each language(Yet).

Not compatible with:
- WP Maintenance

== Known issues ==

Function 'get_page_by_title' not working, because in title field are stored titles for all languages. Use function 'wpm_get_page_by_title( $title )' as solution.

NOTE: Because plugins have different ways of storing data, WP Multilang is not compatible with every single plugin out-of-the-box (mostly page builders). This may result in texts not being translatable or translations not being saved. Most of these issues can be resolved using the integration options (wpm-config.json or filters) of WP Multilang.

Please try WP Multilang in a test-environment before activating it on an existing production site and always make a backup before activating!

[Home](https://wp-multilang.com/) | [Help & Tech Support](https://wp-multilang.com/contact-us/) | [Documentation](https://wp-multilang.com/docs/) | [Premium](https://wp-multilang.com/)

== Support ==

We try our best to provide support on WordPress.org forums. However, We have a special [community support](https://wp-multilang.com/contact-us/) where you can ask us questions and get help about your WP Multilang related questions. Delivering a good user experience means a lot to us and so we try our best to reply each and every question that gets asked.

== Bug Reports ==

Bug reports for WP Multilang are [welcomed on GitHub](https://github.com/ahmedkaludi/wp-multilang/issues). Please note GitHub is not a support forum, and issues that aren't properly qualified as bugs will be closed.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-multilang` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Adjust languages on WP Multilang Settings page.

== Upgrade Notice ==

Before installing or uninstalling make the site database backup before.

== Frequently Asked Questions ==

= I add new translation, but it rewrite another translation on different language. =

If you have opened several browser tabs for editing this post in different languages, translation will be saved for the language that you opened last.

== Screenshots ==

1. Settings page
2. Post list page
3. Taxonomy list page
4. Taxonomy edit page
5. Post edit page

== Changelog ==

= 2.4.12 =
- feature URL Slug Translation #25
- fixed PHP waring #102
- fixed Yoast meta description translation issue #113

= 2.4.11 =
- fixed Product attributers are not getting translated in frontend #94
- enhancement Code improvement #96
- fixed Warning: Undefined array key #102
- feature Automatic translation #77

= 2.4.10 =
- feature Added compatibility with Divi #72
- fixed issue with canonical and href URL as per the language. #85
- fixed issue with Language switcher block in site editor #86
- fixed Compatibility with WordPress 6.6 and updated readme.txt

= 2.4.9 =
- feature WordPress full site editing support #46
- fixed Conflicts with the Newsletter plugin #61
- enhancement Changed premium tab to Premium Features in readme.txt #80
- feature Added Language Switcher Gutenberg Block #82

= 2.4.8 =
- fixed admin_html_tags leaving empty fields #41
- feature Gutenberg view post should redirect to current language post #55
- fixed Conflicts with the Newsletter plugin #61
- feature Woocommerce product attributes translation issue #70
- fixed Issue with CF7 form #73
- fixed Language switch button interface Guternberg block button  #74

= 2.4.7 =
- fixed Call to undefined function #65
- feature Admin settings page UI/UX changes #67
- feature Added Newsletter Form #68

= 2.4.6 =
- enhancement Updated website links like contact page and other info in plugin and wp.org #45
- feature Adedd feedback popup on deactivation #54
- feature Added compatibility with Elementor (Premium) 

= 2.4.5 =
- feature Different product or post images for each language #20
- fixed Widget block translate issue #29
- fixed Fatal Error with latest Yoast SEO plugin update #30
- fixed Translation of special mail tags for Contact Form 7 #31
- fixed Translation of the "title" attribute in the shortcode of Contact Form 7 #32
- fixed wpseo_og:locale:alternate to be set properly #33
- fixed Translate escaping text #34
- fixed Rank Math custom fields getting duplicated on the translated posts #35
- fixed Issue with language switcher regex for gutenberg #36

= 2.4.4 =
- fixed issue with ACF PRO #12
- fixed An error occurred when deleting a post #13
- fixed E_ERROR in class-wpm-install.php #16
- fixed Issue with Gutenberg reusable blocks and create pattern #17
- fixed No translation for Title, Meta etc. if using AIOSEO plugin #18
- fixed No Translation of URL, and social meta tags using YOAST plugin in #23
- fixed Issue with the Rank Math Seo meta field #24

= 2.4.3 =
- fixed Conflict with Yoast SEO #6
- enhancement ( ! ) Deprecated: Hook wpseo_opengraph is deprecated since version WPSEO 14.0 with no alternative available #7
- enhancement ( ! ) Deprecated: setcookie(): Passing null to parameter #5 ($domain) of type string is deprecated #8
- fixed Issue with the ACF Pro plugin #10

= 2.4.2 =
- fixed Compatibility with WordPress 6.4 and updated readme.txt #1
- fixed Code Improvement #2
- added support tab and form

= 2.4.1 =
- removed support old version of ACF lower 5.0

= 2.4.0 =
- added support for Rank Math SEO (thanks for @pratikmts)
- optimized speed
- deleted support for old translate syntax
- other fixes and improvements

All changelog available on [GitHub](https://github.com/ahmedkaludi/wp-multilang/releases).
