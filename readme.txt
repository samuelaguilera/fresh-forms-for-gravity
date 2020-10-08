=== Fresh Forms for Gravity ===
Contributors: samuelaguilera
Tags: gravityforms, cache, Gravity Forms, WP Super Cache, W3 Total Cache, W3TC, Autoptimize, SG Optimizer, Comet Cache, WP Rocket, LiteSpeed Cache, Hummingbird, WP Optimize, WP Fastest Cache, CloudFlare, WP Engine, Kinsta
Requires at least: 4.9
Tested up to: 5.5.1
Stable tag: 1.2.10
Requires PHP: 7.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.en.html

Automatically exclude from cache posts (any type) or pages where the content has a Gravity Forms shortcode or block. WooCommerce products and ACF are also suported.

== Description ==

Caching is great for scenarios where your post or page content it's not changed frequently, but if you have a form embedded to which you do changes very often or you're using dynamic code, that doesn't run for a cached page, or using third-party solutions relaying in dynamic live data (e.g. reCAPTCHA), using caching in these cases is going to cause issues.

An easy solution is to configure your caching plugin or proxy to exclude the page where the form is embedded from caching, but you need also to remember this when you create a new page or embed a new form in an existing page...

This plugin will take care of the above automatically doing the following:

1. Flush current cache on plugin activation. This is **required** in order to allow the next step to run.
2. Dynamically check if there's a Gravity Forms for any of the supported embedding methods (see below for the list).
3. If so, it will prevent post/page from being cached by any of the supported caching plugins, browsers and CDN/Proxies.

= Embedding methods supported: =

* WordPress default editor, shortcode or Gutenberg block. Content of any post type, including pages and custom posts.
* **Elementor**. The following widgets added to the post content are supported: Shortcode, Text.
* **Essential Addons for Elementor** Gravity Forms widget.
* **Divi**. It should work with any of the default modules where you can insert a GF shortcode into the content. e.g. Call To Action, Text, Tabs...
* **WP Tools Gravity Forms Divi Module**.
* **WooCommerce Gravity Forms Product Add-ons** by Lucas Stark.
* **ACF** fields of type Text, Text Area, and WYSIWYG. **Disabled by default**, please see FAQ for more details.
* **Beaver Builder**. It will detect Gravity Forms shortcodes added to a Text Editor module.
* **Ultimate Addons for Beaver Builder** Gravity Forms Styler module.

There's no options page, and **nothing is saved on the database**. Nothing!

It should work with any caching plugin with support for DONOTCACHEPAGE constant, and proxies respecting the use Cache-Control HTTP header.

Caching plugins **supported**:
---------------------------------------------------------------------

* Autoptimize
* Comet Cache
* Hummingbird
* Kinsta Cache
* LiteSpeed Cache
* SG Optimizer
* W3 Total Cache
* WP Engine System
* WP Fastest Cache
* WP Optimize
* WP Rocket
* WP Super Cache

Caching plugins **NOT supported**:
----------------------------------

* Breeze. It doesn't support DONOTCACHEPAGE constant or filters to skip caching.

CloudFlare and other proxies:
-----------------------------

This plugin will add appropriate HTTP header to pages with a Gravity Forms to exlude the page HTML from caching when the web host setup allows it. 

By default CloudFlare doesn't cache the page HTML, it does only when you have configured it to "Cache Everything". In this case, after activating the plugin, you need to purge cache in your CloudFlare account or wait for cache expiration to let CloudFlare know the page must be excluded from caching.

Certain hosts like **WP Engine and Kinsta don't allow HTTP headers modification from WordPress side of things**, therefore CloudFlare support will not work for these hosts.

Other proxy services should work in a similar way, but I don't have access to test any other proxy service. Feel to reach me if you want to provide me access to add support for your proxy service (documentation for the proxy would be required).

= Requirements =

* PHP 7.0 or higher.
* WordPress 4.9 or higher.
* Gravity Forms 2.3 or higher.
* Only forms embedded using classic editor shortcode or Gutenberg block are supported.

= Usage =

Just install and activate, no settings page.

== Frequently Asked Questions ==

= The plugin is not working in LiteSpeed server =

As stated on this plugin description it supports the **LiteSpeed Cache plugin**, NOT LiteSpeed server directly. So if you're using a LiteSpeed based web host, you need to install [LiteSpeed Cache plugin](https://wordpress.org/plugins/litespeed-cache/) before installing Fresh Forms for Gravity.

= I want to enable ACF support =

ACF fields of the following types are supported as standalone fields and also as subfields of a Flexible Content or Repeater field: Text, Text Area, WYSIWYG.

To enable ACF support add the following line to your theme's functions.php file or a custom functionality plugin.

`add_filter( 'freshforms_acf_support', '__return_true' );`

== Changelog ==

= 1.3 =

* Added support to detect UABB Gravity Forms Styler module. Thanks to Kellen Mace for the Beaver Builder module detection code.

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
