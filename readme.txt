=== WP Multilang - Translation and Multilingual Plugin ===

Contributors: magazine3
Donate link: https://paypal.me/kaludi
Tags: localization, multilanguage, multilingual, translation, translate
Requires at least: 4.7
Tested up to: 6.9
Stable tag: 2.4.25
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
* Support Smart Custom Fields Plugin
* Auto Translation ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-auto-translate-your-website-contents-using-wp-multilang/)
* Support URL Slug Translation ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-translate-url-slugs-with-selective-languages/)
* Support Base Translation ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-translate-taxonomy-bases-such-as-categories-and-tags-into-selective-languages-using-the-base-translation-option/)
* Export and import content in XLIFF or xml format to translate outside
* Activate Multilingual Support for Post Types
* Support Auto URL Slug Translation ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-translate-url-slugs-with-selective-languages/)

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

* Elementor ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-build-a-multilingual-site-with-elementor-using-wp-multilang/)
* Divi Builder ( [available in premium version](https://wp-multilang.com/pricing/) ) - [View Tutorial](https://wp-multilang.com/docs/knowledge-base/how-to-build-a-multilingual-site-with-divi-builder-using-wp-multilang/)
* ACF, ACF Pro
* WooCommerce
* WooCommerce Customizer
* Gutenberg
* Yoast Seo
* SEOPress 
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
* Forminator Forms
* Gravity Forms
* Ultimate Member Form

== Advance Woocommerce Support ==
* Send emails in customer's selected language
* REST API Support
* Import and export products in customer's selected language
* Translate products (simple products, variable products, grouped products), categories, tags, global attributes
* Cart synchronization across multiple languages 

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

= 2.4.25 =
- Fixed: Other language content is not updating in Elementor #215
- Fixed: Promotion Banner BFCM #221
- Fixed: Yoast SEO meta tags not translating #225

= 2.4.24 =
- Added: Promotion Banner BFCM #221
- Added: OpenAI Integration for Automatic Neural Network Translation #207
- fixed: Wrong url was added in switcher #220
- Tested: WordPress version upto 6.9.

= 2.4.23 =
- feature Compatibility Ultimate Member Forms #208
- fixed Error while adding ACF pro repeater fields #209
- fixed Critical error while translation #217

= 2.4.22 =
- enhancement Fixed woocommerce attribute translation issue for third language #175
- fixed Auto Translate style issue on product taxanomies/terms #196
- enhancement Added re-translate option for taxonomies #197
- feature Added an option to exclude page/post from auto translation #200 (Pro)
- fixed Featured image issue on block editor #203

= 2.4.21 =
- fixed Rankmath title translation improvement #177
- fixed Flag translation issue on products #185
- fixed Memory size limit improvement #189
- feature XLIFF export compatibility with ACF fields on post/pages #190
- feature Added Bulk Translation Option for Tag and Categories #191
- fixed Retranslation issue with Elementor pages #192
- fixed Code improvement for duplicate queries #194

= 2.4.20 =
- feature Compatibility with SEOpress #170
- feature Auto url slug translation #171
- fixed Divi builder content translation improvement #173
- fixed Conflict with latest pro 1.12 version #178
- feature Added option to retranslate #180
- fixed Yoast default title issue #181
- feature Added support for exporting and importing content in XLIFF or XML format for pages #182
- fixed Code improvement for custom post taxonomy description #183

= 2.4.19.1 =
- fixed Vulnerability fix reported by patchstack

= 2.4.19 =
- fixed Code-profiler plugin execution time issue #149
- fixed Woocommerce settings translation improvement #161
- feature Compatibility with ACF Pro Pages Option #162
- fixed Conflict issue with pinnacle theme #163
- fixed Code improvement of translation #164
- fixed Compatibility with WordPress 6.8 and updated readme.txt #165
- feature Added compatibility with Cyr-To-Lat plugin #169

= 2.4.18 =
- feature Feature to enable support for any custom post type #143
- fixed Language Switcher issue on gutenberg editor #147
- fixed Navigation links are not translatable #151
- fixed Code improvement for bulk translation #153
- fixed Code improvement of on language switcher on custom post type editor #155
- fixed code improvement for Category base translation #157
- fixed Elementor css file creation for respective language #158
- enhancement Auto translation code improvement when some specials characters are present #159

= 2.4.17 =
- feature Comments translation compatibility #38
- feature Export and import content in XLIFF or xml format to translate outside #48
- enhancement Made uninstall easy #138
- feature Advance woocommerce support #139
- feature Auto-translate single post/product #142
- enhancement Checked license key for autotranslation feature #144
- feature Added compatibility with Gravity form #148
- fixed Execution timing issue with the code-profiler plugin #149
- feature Auto-Translation for Reviews and Collections in Schema Plugin #150

= 2.4.16 =
- feature Rankmath multilingual schema and structured data support #56
- feature Yoast multilingual schema and structured data support #57
- feature AIOSEO multilingual schema and structured data support #58
- feature Added a new feature that helps users select different logos as per the language of the site #71
- feature Compatibility with Forminator form Plugin #132

= 2.4.15 =
- feature Added compatibility with Schema & Structured Data for WP & AMP Plugin #59
- feature Added compatibility with Team – Team Members Showcase Plugin #75
- feature Added option to reset the translation languages #122
- fixed WPBakery Builder Meta Descriptions and Meta Titles Not Translating #128
- fixed Auto translate not working when adding post via elementor #130

= 2.4.14 =
- fixed Woocommerce product attributes are not getting translated #93
- feature Added compatibility with the WP Githuber plugin #99
- enhancement Use 'translate' keyword for search the plugin from add new plugin section #103
- feature Added compatibility with Smart Custom Fields Plugin #116
- feature Added eature to translate slug for 2nd level of hierarchy of urls #121
- fixed Code improvement #125
- fixed Compatibility with WordPress 6.7 and updated readme.txt #126

= 2.4.13 =
- feature WP GraphQL support #44
- fixed Post Title translation issue for multiple languages #78
- enhancement Language switcher disappears too early in Wordpress 6.6 #91
- enhancement Code improvement #96
- fixed Out of memory error after update to 2.4.11 #111
- fixed Issue with yoast meta description #113
- fixed License key wrong link issue #118
- fixed Error after the update 2.4.11 #109

= 2.4.12 =
- feature URL Slug Translation #25
- fixed PHP waring #102
- fixed Yoast meta description translation issue #113

= 2.4.11 =
- fixed Product attributers are not getting translated in frontend #94
- enhancement Code improvement #96
- fixed Warning: Undefined array key #102
- feature Automatic translation #77

All changelog available on [GitHub](https://github.com/ahmedkaludi/wp-multilang/releases).