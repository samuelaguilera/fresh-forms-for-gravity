Description
---------------------------------------------------------------------

Caching is great for scenarios where your post or page content it's not changed frequently, but if you have a form embedded to which you do changes very often or you're using dynamic code, that doesn't run for a cached page, or using third-party solutions relaying in dynamic live data (e.g. reCAPTCHA), using caching in these cases is going to cause issues.

An easy solution is to configure your caching plugin or proxy to exclude the page where the form is embedded from caching, but you need also to remember this when you create a new page or embed a new form in an existing page...

This plugin will take care of the above automatically doing the following:

1. Flush current cache on plugin activation. This is **required** in order to allow the next step to run.
2. Dynamically check if there's a Gravity Forms for any of the supported embedding methods (see below for the list).
3. If so, it will prevent post/page from being cached by any of the supported caching plugins, browsers and CDN/Proxies.

Embedding methods supported:
---------------------------------------------------------------------

* WordPress default editor, shortcode or Gutenberg block. Content of any post type, including pages and custom posts.
* **ACF** fields of type Text, Text Area, and WYSIWYG.
* **Avada**. The following elements has been proven to work: Content Boxes, "Gravity Form", Modal, Text Block. Other elements could work too, but not tested.
* **Beaver Builder**. It will detect Gravity Forms shortcodes added to a Text Editor module.
* **Divi**. It should work with any of the default modules where you can insert a GF shortcode into the content. e.g. Call To Action, Text, Tabs...
* **Elementor**. The following widgets added to the post content are supported: Shortcode, Text.
* [Essential Addons for Elementor](https://wordpress.org/plugins/essential-addons-for-elementor-lite/) Gravity Forms widget.
* **PowerPack for Beaver Builder** Gravity Forms Styler module.
* [Ultimate Addons for Beaver Builder](https://wordpress.org/plugins/ultimate-addons-for-beaver-builder-lite/) Gravity Forms Styler module.
* Ultimate Addons for Elementor By Brainstorm Force.
* **WooCommerce Gravity Forms Product Add-ons** by Lucas Stark.
* **WPBakery Page Builder**. The following elements has been proven to work: "Gravity Form", Text Block. Other elements could work too, but not tested.
* [WP Tools Gravity Forms Divi Module](https://wordpress.org/plugins/wp-tools-gravity-forms-divi-module/).

There's no options page, and **nothing is saved on the database**. Nothing!

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
* SiteGround Optimizer
* Surge
* W3 Total Cache
* WP Engine System
* WP Fastest Cache
* WP Optimize
* WP Rocket
* WP Super Cache

Caching plugins **NOT supported**:
----------------------------------

* Breeze. It doesn't support DONOTCACHEPAGE constant or filters to skip caching. [**Check FAQ for a workaround.**](https://wordpress.org/plugins/fresh-forms-for-gravity/#is%20varnish%20caching%20supported%3F)
* NitroPack. It doesn't support DONOTCACHEPAGE constant or filters to skip caching. [**Check FAQ for a workaround.**](https://wordpress.org/plugins/fresh-forms-for-gravity/#is%20varnish%20caching%20supported%3F)

CloudFlare and other proxies:
-----------------------------

This plugin will add appropriate HTTP header to pages with a Gravity Forms to exlude the page HTML from caching when the web host setup allows it. 

By default CloudFlare doesn't cache the page HTML, it does only when you have configured it to "Cache Everything". In this case, after activating the plugin, you need to purge cache in your CloudFlare account or wait for cache expiration to let CloudFlare know the page must be excluded from caching.

Certain hosts like **WP Engine and Kinsta don't allow HTTP headers modification from WordPress side of things**, therefore CloudFlare support will not work for these hosts.

Other proxy services should work in a similar way, but I don't have access to test any other proxy service. Feel to reach me if you want to provide me access to add support for your proxy service (documentation for the proxy would be required).

Requirements
---------------------------------------------------------------------

* PHP 7.0 or higher.
* WordPress 4.9 or higher.
* Gravity Forms 2.3 or higher.

Usage
---------------------------------------------------------------------

Just install and activate, no settings page.
