=== Fresh Forms for Gravity ===
Contributors: samuelaguilera
Tags: Gravity Forms, gravityforms, cache, caching
Requires at least: 4.9
Tested up to: 6.8.2
Stable tag: 1.5.5
Requires PHP: 7.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.en.html

Prevent supported caching and JS optimization plugins breaking Gravity Forms.

== Description ==

Caching is great for scenarios where your post or page content it's not changed frequently, but if you have a form embedded to which you do changes very often or you're using dynamic code, that doesn't run for a cached page, or using third-party solutions relaying in dynamic live data (e.g. reCAPTCHA), using caching in these cases is going to cause issues. The same applies to certain automatic JS optimizations done by caching plugins that are known to break JS execution easily.

An easy solution is to configure your caching plugin or proxy to exclude the page where the form is embedded, but you need also to remember this when you create a new page or embed a new form in an existing page...

This plugin will take care of the above automatically doing the following:

1. Flush current cache on plugin activation. This is **required** in order to allow the next step to run.
2. Dynamically check if there's a Gravity Forms for any of the supported embedding methods (see below for the list).
3. If so, it will prevent post/page from being cached by any of the supported caching plugins, browsers and CDN/Proxies.

= Embedding methods supported: =

* **WordPress default editor, shortcode or Gutenberg block**. Content of any post type, including pages and custom posts.
* **ACF** fields of type Text, Text Area, and WYSIWYG. **Disabled by default**.
* **Avada**. The following elements has been proven to work: Content Boxes, "Gravity Form", Modal, Text Block. Other elements could work too, but not tested.
* **Beaver Builder**. It will detect Gravity Forms shortcodes added to a Text Editor module.
* **Conversational Forms** add-on. It will automatically detect any form page using the **conversational_form** post type (requires Conversational Forms add-on 1.3.0+).
* **Divi**. It should work with any of the default modules where you can insert a GF shortcode into the content. e.g. Call To Action, Text, Tabs...
* **Elementor**. The following widgets added to the post content are supported: Shortcode, Text.
* [Essential Addons for Elementor](https://wordpress.org/plugins/essential-addons-for-elementor-lite/) Gravity Forms widget.
* GravityKit Gravity Forms Widget for Elementor.
* **PowerPack for Beaver Builder** Gravity Forms Styler module.
* [Ultimate Addons for Beaver Builder](https://wordpress.org/plugins/ultimate-addons-for-beaver-builder-lite/) Gravity Forms Styler module.
* Ultimate Addons for Elementor By Brainstorm Force.
* **WooCommerce Gravity Forms Product Add-ons** by Lucas Stark.
* **WPBakery Page Builder**. The following elements has been proven to work: "Gravity Form", Text Block. Other elements could work too, but not tested.
* [WP Tools Gravity Forms Divi Module](https://wordpress.org/plugins/wp-tools-gravity-forms-divi-module/).

If you're not using any of the above embedding methods you can still use Fresh Forms with a filter to pass the ID number of the posts where you want to run Fresh forms. You can also make Fresh Forms to add a cookie when a form is detected to use this cookie as a way to skip caching for hosts using Varnish based caching. Please see FAQ for more details.

There's no options page. Only the plugin version is saved to the database to be able to handle actions after an update when needed, **no other data is stored**.

It should work with any caching plugin with support for DONOTCACHEPAGE constant, and proxies respecting the use Cache-Control HTTP header.

Caching and Optimization plugins **supported**:
---------------------------------------------------------------------

* Autoptimize
* Cache Enabler
* Comet Cache
* Hummingbird
* Kinsta Cache
* LiteSpeed Cache
* [Page Optimize](https://wordpress.org/plugins/page-optimize/) (Script concatenation only)
* Perfmatters (Delay JS exclusions only)
* Speed Optimizer (by SiteGround, the plugin with more name changes in the history of WordPress!)
* Surge
* W3 Total Cache
* WP Engine System
* WP Fastest Cache
* WP Optimize
* WP Rocket
* WP Super Cache

Caching plugins **NOT supported**:
----------------------------------

* Breeze. It doesn't support DONOTCACHEPAGE constant or filters to skip caching. **Check FAQ for a workaround.**
* NitroPack. It doesn't support DONOTCACHEPAGE constant or filters to skip caching. **Check FAQ for a workaround.**

Cloudflare and other CDN/proxies:
-----------------------------

This plugin will add appropriate HTTP header to pages with a Gravity Forms form to exlude the page HTML from caching when the web host setup allows it. 

By default Cloudflare doesn't cache the page HTML, it does only when you have configured it to "Cache Everything". In this case, after activating the plugin, you need to purge cache in your Cloudflare account or wait for cache expiration to let Cloudflare know the page must be excluded from caching.

Certain hosts like **WP Engine and Kinsta don't allow HTTP headers modification from WordPress side of things**, therefore Cloudflare support will not work for these hosts.

Other proxy services should work in a similar way, but I don't have access to test any other proxy service. Feel to reach me if you want to provide me access to add support for your proxy service (documentation for the proxy would be required).

Note for these cases (caching is done by an external service), Fresh Forms can just include the HTTP header when your web host allows it. Once the header is added, it's up to the CDN/proxy being used to obey the header and skip caching for the page.

= Requirements =

* PHP 7.0 or higher.
* WordPress 4.9 or higher.
* Gravity Forms 2.3 or higher.
* Only forms embedded using classic editor shortcode or Gutenberg block are supported.

= Usage =

Just install and activate. No settings required except for ACF support (see FAQ).

== Frequently Asked Questions ==

= The plugin is not working in LiteSpeed server =

As stated on this plugin description it supports the **LiteSpeed Cache plugin**, NOT LiteSpeed server directly. So if you're using a LiteSpeed based web host, you need to install [LiteSpeed Cache plugin](https://wordpress.org/plugins/litespeed-cache/) before installing Fresh Forms for Gravity.

= I want to enable ACF support =

ACF fields of the following types are supported as standalone fields and also as subfields of a Flexible Content or Repeater field: Text, Text Area, WYSIWYG. But this is disabled by default.

To enable ACF support go to the settings page at Forms > Settings > Fresh Forms.

= I want Fresh Forms to run for certain posts where I'm embedding forms using an embed method that is not supported. =

Starting with Fresh Forms 1.5 you can add a list of pages or posts IDs where you would like to force Fresh Forms to run by going to Forms > Settings > Fresh Forms.

You could also add the freshforms_post_has_gform filter in your theme functions.php file or a custom fucntionatliy plugin to pass Fresh Forms an array containing the ID of the pages/posts where you want it to run without performing the usual automatic detection of forms.

The following example would exclude posts with ID 1 and 8:

`add_filter( 'freshforms_post_has_gform', 'fffg_fresh_these_posts' );
function fffg_fresh_these_posts(){
	// Force Fresh Forms to run for posts with id 1 and 8.
	return array( 1, 8);
}`

The following example would exclude WooCommerce products using a product category with the slug product-category-1

`add_filter( 'freshforms_post_has_gform', 'fffg_fresh_these_products' );
function fffg_fresh_these_products( $post_has_form ){
	global $post;

	// Run Fresh Forms for a WooCommerce product if it has one of the following categories slugs.
	$product_categories = array( 'product-category-1' );
	if ( is_object( $post ) && 'product' === $post->post_type && has_term( $product_categories, 'product_cat', $post->ID ) ) {
		return array( $post->ID );
	}

	// Otherwise.
	return $post_has_form;
}`

After doing the above, you need to **flush your host/plugin and browser cache**.

= Is Varnish caching supported? =

Fresh Forms targets only WordPress caching plugins, it doesn't communicate with your server directly. If you have a WordPress installed to manage Varnish cache and this plugin has support for the DONOTCACHEPAGE constant, Fresh Forms should work out of the box.

But there are some WordPress plugins, like Breeze by CloudWays and NitroPack, that while they are created to integrate WordPress with Varnish, they don't support DONOTCACHEPAGE or provide any WordPress filters to exclude content dynamically (at the time of writing this).

But both have support to exclude pages from their cache based on a cookie added. Since Fresh Forms 1.3.4, you can use the freshforms_add_cookie filter in your theme functions.php file or a custom fucntionatliy plugin to make Fresh Forms to add a cookie that you can configure in CloudWays or NitroPack account dashboard.

`add_filter( 'freshforms_add_cookie', '__return_true' );`

Once you have the filter added to your site, follow the instructions below.

CloudWays documentation: [How to Include or Exclude Cookies From Varnish](https://support.cloudways.com/how-to-include-or-exclude-cookies-from-varnish/)

Make sure you select **Exclude** for the Method, and use **FreshForms** for the cookie Value.

NitroPack documentation:  [Excluded Cookies](https://support.nitropack.io/hc/en-us/articles/1500002527202-Excluded-Cookies)  

Use **FreshForms** for the Cookie Name and **no-cache** for the Cookie Values.

After doing the above, you need to **flush your host and browser cache**.

== Changelog ==

= 1.5.5 =

* Updated data-cfasync exclusion function.

= 1.5.4 =

* Added JS handlers for Turnstile add-on.

= 1.5.3 =

* Added support for GravityKit Gravity Forms Widget for Elementor.

= 1.5.2 =

* Added support for the conversational_form post type introduced in the Conversational Forms add-on 1.3.0 version.
* Moved check for force options (setting and filter) to an earlier stage.

= 1.5 =

* Improved ACF support by requesting the field content only for supported types.
* Added a settings page for optional settings.
* Filter freshforms_acf_support is now deprecated in favor of the new settings page.

= 1.4.17 =

* Reverted ACF support back to disabled by default.

= 1.4.16 =

* Prevent fatal error if post content is not a string (as required by WordPress has_shortcode function). Which could happen for not supported embedding methods or when a third-party is altering the expected post content.

= 1.4.15 =

* ACF support is now enabled by default when the ACF plugin is enabled for the site.
* Changed ACF checks to the last position of possible checks to avoid running them when the form is already detected in any other embedding method.
* Improved logging message for the scan_content() function used for ACF and UAEL.

= 1.4.12 =

* Added support for PowerPack for Beaver Builder by IdeaBox Creations Gravity Forms Styler module.

= 1.4.11 =

* Optimized code for plugins accepting partial matches for JS files exclusion.
* Added support for Perfmatters delay JS exclusions. This plugin has other filters to investigate which are not supported yet.
* Fixed PHP 8.1+ deprecated notice. Thanks to WordPress user @artgoddess for reporting it.

= 1.4.8 =

* Added support to exclude Gravity Forms scripts from [Page Optimize](https://wordpress.org/plugins/page-optimize/) concatenation feature.

= 1.4.7 =

* Code refactor to prevent a third-party messing with the JS exclusion variables.

= 1.4.6 =

* Fixed a fatal error which can happen when purging the cache for WP-Optimize during Fresh Forms initial activation. Thanks to @castoruk for the report.
* Fixed a fatal error which can happen when purging the cache for Kinsta Cache plugin during Fresh Forms initial activation. Thanks to @squareeye for the report.
* Improved checks of existing functions for cache purge during activation.

= 1.4.3 =

* Some code refactoring.
* Prevent warning if $post->ID is not valid for some reason when the function to check for shortcodes is called. Thanks to @bozzmedia for the report.
* Added some new handlers for GF 2.7+ to the JavaScript handlers exclusion. Thanks to Richard Wawrzyniak.

= 1.4 =

* Added support for Ultimate Addons for Elementor By Brainstorm Force.

= 1.3.19 =

* Fixed array warning when `wp doctor check autoload-options-size` is used. Thanks to @bozzmedia for the report.
* Added Surge to the list of supported caching plugin. It supports Cache-Control: no-cache header.
* Added Cache Enabler to the list of supported caching plugin. It supports DONOTCACHEPAGE constant.

= 1.3.16 =

* Added support for Pantheon Cache cleaning on plugin activation. According to Pantheon Cache documentation cache exclusion is based on the Cache-Control header, which Fresh Forms already adds, so clearing cache should enable cache exclusion. https://pantheon.io/docs/cache-control

= 1.3.15 =

* Updated use of LiteSpeed Cache plugin constants to avoid issues also with optimizations (JS minification, combination, defer, etc...).

= 1.3.14 =

* Added support to exclude pages with a form from W3TC minify JS feature (files and inline). W3TC minifies inline JS as part of the HTML minify, so this affects also to HTML minification for form pages.
* Added gform.initializeOnLoaded to the list of inline scripts string matches. Thanks to Richard Wawrzyniak for pointing it.

= 1.3.12 =

* Added support to prevent issues with Hummingbird Asset Optimization breaking JS based features.
* Added support to prevent issues with WP Rocket Defer JS, Minification/Combination of files and Combine Inline JS features breaking JS based features.
* Added experimental support for Bluehost's Endurance Page Cache.
* Added missing path for SuperSignature scripts to Autoptimize exclusions.
* Added automatic flush of cache after Fresh Forms updates to allow new additions to take effect transparently. Known issue: WP-Optimize requires a manual purge from its settings page.
* Changed the value of the Fresh-Forms HTTP header to use the version number.
* Improved CloudFlare's Rocket Loader exclusions.

= 1.3.5 =

* Added support to prevent WP-Optimize minification breaking form pages.
* Added Fresh-Forms HTTP header.
* Added filter freshforms_add_cookie filter that makes Fresh Forms to add a cookie that you can use with Varnish based caching plugins like CloudWays or NitroPack. See FAQ for more details.
* Fixed WP-Optimize cache exclusion that was broken due to WP-Optimize bad practice of using PHP_INT_MAX as priority for add_filter/add_action lines.

= 1.3.1 =

* Added filter freshforms_post_has_gform to force Fresh Forms to run without checking if there's a form. See FAQ for more details.

= 1.3 =

* Added support to detect UABB Gravity Forms Styler module. Thanks to Kellen Mace for the Beaver Builder module detection code https://gist.github.com/kellenmace/add5c45e5bddcdd3271de8fc7d204a18
* Added new Signature add-on page parameter to SG Optimizer exclusions. Thanks to Richard Wawrzyniak for pointing it.
* Added additional domains for SG Optimizer external scripts exclusions.
* Fixed JS scripts exclusions for Autoptimize. Thanks to Richard Wawrzyniak for pointing it.

= 1.2.10 =

* Added another SG Optimizer exclusion to prevent SG Optimizer from breaking Stripe Checkout redirection. Thanks to Richard Wawrzyniak.

= 1.2.9 =

* Added support for forms embedded using the WP Tools Gravity Forms Divi Module plugin.

= 1.2.8 =

* Added support to detect a Gravity Forms form embedded into ACF fields of Text, Text Area, and WYSIWYG type as subfields of a Repeater field.
* Added support for Essential Addons for Elementor Gravity Forms widget.
* Fixed a warning when the post has an ACF Flexible Content field but is empty.
* Refactoring of some code.

= 1.2.4 =

* Added more SG Optimizer exclusions.
* Added support to detect a Gravity Forms form embedded into ACF fields of the following types as standalone fields and also as subfields of a Flexible Content field: Text, Text Area, WYSIWYG.
* Added support to detect a Gravity Forms form embedded into WooCommerce product using WooCommerce Gravity Forms Product Add-ons by Lucas Stark.
* Minor code changes.

= 1.2 =

* Added an exclusion for SG Optimizer to prevent an issue with form redirection inline script due to the "Combine JavaScript Files" feature, that despite of its name also affects to inline scripts. Thanks to Richard Wawrzyniak.
* Added an exclusion for SG Optimizer to prevent an issue with the Signature page not displaying the image due to the "Minify the HTML Output" feature. Thanks to Travis Lopes.
* Added an exclusion for SG Optimizer to prevent an issue with the PayPal Commerce Platform Add-On due to the "Defer Render-blocking JS" feature.
* Added additional exclusions of inline JS and files for SG Optimizer.

= 1.1.6 =

* Added additional checking for Gravity Forms shortcode and blocks when a reusable block is used. Thanks to Richard Wawrzyniak.

= 1.1.5 =

* Added support for Kinsta Cache.
* Added support for Autoptimize.
* Minor tweaks to SG Optimizer and WP Engine System support.

= 1.1.2 =

* Improved WP Engine System support.
* Added support for SG Optimizer.

= 1.1.1 =

* Fixed a few notices.

= 1.1 =

* Fixed issue with shortcode detection.
* Added support for WP Engine System.

= 1.0 =

* First public release.
