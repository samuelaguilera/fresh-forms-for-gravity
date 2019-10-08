## Description
Caching is great for scenarios where your post or page content it's not changed frequently, but if you have a form embedded to which you do changes
very often or you're using dynamic code, that doesn't run for a cached page, or using third-party solutions relaying in dynamic live data (e.g. reCAPTCHA), using caching in these cases is going to cause issues.

An easy solution is to configure your caching plugin or proxy to exclude the page where the form is embedded from caching, but you need also to remember this when you create a new page or embed a new form in an existing page...

This plugin will take care of the above automatically doing the following:

1. Flush current cache on plugin activation. This is **required** in order to allow the next step to run.
2. Dynamically check if there's a Gravity Forms shortcode or Gutenberg block in your post (any post type) or page **content** using WordPress core functions for it.
If so, it will prevent post/page caching for supported caching plugins, browsers and proxies.

There's no options page, and **nothing is saved on the database**. Nothing!

It should work with any caching plugin with support for DONOTCACHEPAGE constant, and proxies respecting the use Cache-Control HTTP header.

### Caching plugins **supported**:

* WP Super Cache
* W3 Total Cache
* WP Rocket
* Autoptimize
* Comet Cache
* LiteSpeed Cache
* Hummingbird
* WP Optimize
* WP Fastest Cache

### Caching plugins **not supported**:

* Breeze. It doesn't support DONOTCACHEPAGE constant or filters to skip caching.
* SG Optimizer. It doesn't support DONOTCACHEPAGE constant or filters to skip caching. Although it supports excluding GF scripts from minification and async loading, so this is supported.

### CloudFlare and other proxies:

This plugin will add appropriate HTTP header to pages with a Gravity Forms to exlude the page HTML from caching. By default CloudFlare doesn't cache the page HTML,
it does only when you have configured it to "Cache Everything". In this case, after activating the plugin, you need to purge cache in your CloudFlare account or wait
for cache expiration to let CloudFlare know the page must be excluded from caching.

Other proxy services should work in a similar way, but I don't have access to test any other proxy service. Feel to reach me if you want to provide me access to add support
for your proxy service (documentation for the proxy would be required).

## Requirements

* PHP 7.0 or higher.
* WordPress 4.9 or higher.
* Gravity Forms 2.3 or higher.

## Usage

Just install and activate, no settings page.
