=== Fresh Forms for Gravity ===
Contributors: samuelaguilera
Tags: gravityforms, cache, Gravity Forms, WP Super Cache, W3 Total Cache, W3TC, Autoptimize, SG Optimizer, Comet Cache, WP Rocket, LiteSpeed Cache, Hummingbird, WP Optimize, WP Fastest Cache, CloudFlare, WP Engine, Kinsta
Requires at least: 4.9
Tested up to: 5.4.1
Stable tag: 1.1.5
Requires PHP: 7.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.en.html

Prevents supported caching plugins, browsers and proxies from caching post/pages where a Gravity Forms shortcode or Gutenberg block is used.

== Description ==

Caching is great for scenarios where your post or page content it's not changed frequently, but if you have a form embedded to which you do changes very often or you're using dynamic code, that doesn't run for a cached page, or using third-party solutions relaying in dynamic live data (e.g. reCAPTCHA), using caching in these cases is going to cause issues.

An easy solution is to configure your caching plugin or proxy to exclude the page where the form is embedded from caching, but you need also to remember this when you create a new page or embed a new form in an existing page...

This plugin will take care of the above automatically doing the following:

1. Flush current cache on plugin activation. This is **required** in order to allow the next step to run.
2. Dynamically check if there's a Gravity Forms shortcode or Gutenberg block in your post (any post type) or page **content** using WordPress core functions for it.
If so, it will prevent post/page caching for supported caching plugins, browsers and CDN/Proxies.

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

This plugin will add appropriate HTTP header to pages with a Gravity Forms to exlude the page HTML from caching. By default CloudFlare doesn't cache the page HTML, it does only when you have configured it to "Cache Everything". In this case, after activating the plugin, you need to purge cache in your CloudFlare account or wait for cache expiration to let CloudFlare know the page must be excluded from caching.

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

As stated on this plugin description it supports the LiteSpeed Cache plugin, not LiteSpeed server directly. So if you're using a LiteSpeed based web host, you need to install LiteSpeed Cache plugin before installing Fresh Forms for Gravity.

== Changelog ==

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
