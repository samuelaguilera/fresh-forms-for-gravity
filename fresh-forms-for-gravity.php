<?php
/**
 * Plugin Name: Fresh Forms for Gravity
 * Description: Prevent posts and pages with a Gravity Forms shortcode or Gutenberg block from being cached.
 * Author: Samuel Aguilera
 * Version: 1.3
 * Author URI: https://www.samuelaguilera.com
 * Text Domain: fresh-forms-for-gravity
 * Domain Path: /languages
 * License: GPL3
 *
 * @package Fresh Forms for Gravity
 */

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License version 3 as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

define( 'FRESH_FORMS_FOR_GRAVITY_VERSION', '1.3' );

add_action( 'gform_loaded', array( 'Fresh_Forms_For_Gravity_Bootstrap', 'load' ), 5 );
register_activation_hook( __FILE__, 'fffg_purge_all_cache' );

/**
 * Class Fresh_Forms_For_Gravity_Bootstrap
 *
 * Handles the loading of the Add-On and registers with the Add-On framework.
 *
 * @since 1.0.0
 */
class Fresh_Forms_For_Gravity_Bootstrap {

	/**
	 * If the Add-On Framework exists, Fresh Forms for Gravity Add-On is loaded.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @uses GFAddOn::register()
	 *
	 * @return void
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		require_once 'class-fresh-forms-for-gravity.php';

		GFAddOn::register( 'Fresh_Forms_For_Gravity' );
	}

}

/**
 * Obtains and returns an instance of the SAR_Fresh_Forms
 *
 * @since  1.0.0
 * @access public
 *
 * @uses Fresh_Forms_For_Gravity::get_instance()
 *
 * @return object Fresh_Forms_For_Gravity
 */
function fresh_forms_for_gravity() {
	return Fresh_Forms_For_Gravity::get_instance();
}

/**
 * Purge everything for a fresh start... This is required in order to allow donotcache_and_headers() to run before caching a page.
 */
function fffg_purge_all_cache() {

	// WP Engine.
	if ( class_exists( 'WpeCommon' ) ) {
		if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {
			WpeCommon::purge_memcached();
		}
		if ( method_exists( 'WpeCommon', 'clear_maxcdn_cache' ) ) {
			WpeCommon::clear_maxcdn_cache();
		}
		if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
			WpeCommon::purge_varnish_cache();
		}

		// WPE doesn't allow third-party caching plugins https://wpengine.com/blog/no-caching-plugins/ so we can stop here for WPE hosted sites.
		return;
	}

	// W3TC.
	if ( function_exists( 'w3tc_pgcache_flush' ) ) {
		// Delete page cache.
		w3tc_pgcache_flush();
	}

	// WP Super Cache.
	if ( function_exists( 'wp_cache_clean_cache' ) ) {
		global $file_prefix;

		empty( $file_prefix ) ? $file_prefix = 'wp-cache-' : $file_prefix;
		wp_cache_clean_cache( $file_prefix, true );
	}

	// WP Fastest Cache.
	if ( class_exists( 'WpFastestCache' ) ) {
		$wpfc = new WpFastestCache();
		$wpfc->deleteCache();
	}

	// WP Rocket.
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}

	// Autoptimize.
	if ( class_exists( 'autoptimizeCache' ) ) {
		autoptimizeCache::clearall();
	}

	// SG Optimizer.
	if ( class_exists( 'SiteGround_Optimizer\Supercacher\Supercacher' ) ) {
		SiteGround_Optimizer\Supercacher\Supercacher::purge_cache();
	}

	// Comet Cache.
	if ( class_exists( 'comet_cache' ) ) {
		comet_cache::clear();
	}

	// LiteSpeed Cache.
	if ( class_exists( 'LiteSpeed_Cache_Purge' ) ) {
		LiteSpeed_Cache_Purge::all();
	}

	// Hummingbird.
	if ( class_exists( 'Hummingbird\Core\Filesystem' ) ) {
		// I would use Hummingbird\WP_Hummingbird::flush_cache( true, false ) instead, but it's disabling the page cache option in Hummingbird settings.
		Hummingbird\Core\Filesystem::instance()->clean_up();
	}

	// WP Optimize.
	if ( class_exists( 'WP_Optimize_Cache_Commands' ) ) {
		// This function returns a response, so I'm assigning it to a variable to prevent unexpected output to the screen.
		$response = WP_Optimize_Cache_Commands::purge_page_cache();
	}

	// Kinsta Cache.
	if ( class_exists( 'Kinsta\Cache' ) ) {
		// $kinsta_cache object already created by Kinsta cache.php file.
		global $kinsta_cache;
		$kinsta_cache->kinsta_cache_purge->purge_complete_full_page_cache();
	}

}
