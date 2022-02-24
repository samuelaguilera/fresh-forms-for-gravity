<?php
/**
 * Plugin Name: Fresh Forms for Gravity
 * Description: Prevent posts and pages with a Gravity Forms shortcode or Gutenberg block from being cached.
 * Author: Samuel Aguilera
 * Version: 1.3.16
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

define( 'FRESH_FORMS_FOR_GRAVITY_VERSION', '1.3.16' );

// Scripts handlers for plugins using them for exclusion filters (e.g. SG Optimizer or Hummingbird).
$fffg_js_handlers = array(
	'jquery', // Yeah, not a GF script but many of them (and themes, and etc...) have it as dependency.
	'jquery-core',
	'gform_gravityforms',
	'gform_conditional_logic',
	'gform_datepicker_init',
	'plupload-all', // Multi-upload fields.
	'gform_json',
	'gform_textarea_counter',
	'gform_masked_input',
	'gform_chosen',
	'gform_placeholder',
	'gforms_zxcvbn', // Password strength.
	'gf_partial_entries', // Partial Entries Add-On.
	'stripe.js', // Stripe Add-On.
	'stripe_v3',
	'gforms_stripe_frontend',
	'gform_coupon_script', // Coupons Add-On.
	'gforms_ppcp_frontend', // PPCP Add-On.
	'gform_paypal_sdk', // Dependency for PPCP.
	'wp-a11y', // Dependency for PPCP. This and the following three lines fixed issues with a PPCP form.
	'wp-dom-ready', // Dependency for wp-a11y.
	'wp-polyfill', // Dependency for wp-a11y.
	'wp-i18n', // Dependency for wp-a11y.
	'gforms_square_frontend', // Square Add-On.
	'gform_mollie_components', // Mollie Add-On.
	'gform_chained_selects', // Chained Selects Add-On.
	'gsurvey_js', // Survey Add-On.
	'gpoll_js', // Polls Add-On.
	'gaddon_token', // Credit Card Token.
	'gform_signature_frontend', // Signature Add-on.
	'super_signature_script',
	'super_signature_base64',
	'gform_signature_delete_signature',
	'gform_recaptcha', // reCAPTCHA.
);

// Scripts partial matches for plugins using them for exclusion filters (e.g. WP-Optimize or WP Rocket).
$fffg_js_partial = array(
	'gravityforms', // This is enough to match any script having gravityforms as part of the URL.
	'jquery.min.js',
	'plupload.min.js',
	'a11y.min.js',
	'wp-polyfill.min.js',
	'dom-ready.min.js',
	'i18n.min.js',
	'zxcvbn.min.js', // Password strength.
	'recaptcha',
);

// Inline scripts string matches for plugins using them for exclusion filters (e.g. SG Optimizer or WP Rocket).
$fffg_js_inline_partial = array(
	'gformRedirect',
	'var gf_global',
	'gformInitSpinner',
	'var gf_partial_entries',
	'(function(d,s,i,r)', // HubSpot Tracking Script.
	'gform.addAction',
	'gform_post_render',
	'var gforms_ppcp_frontend_strings', // PPCP Add-On.
	'gform_page_loaded', // Multi-page Ajax forms.
	'var stripe', // Stripe Checkout.
	'gform_gravityforms-js-extra',
	'gform.initializeOnLoaded',
);

// Domains for external JS for exclusion filters (e.g. SG Optimizer or WP Rocket).
$fffg_js_external_domain = array(
	'2checkout.com',
	'agilecrm.com',
	'dropbox.com',
	'js.hs-analytics.net', // HubSpot Analytics Code.
	'mollie.com',
	'paypal.com',
	'js.squareup.com',
	'js.squareupsandbox.com',
	'js.stripe.com',
);

add_action( 'gform_loaded', array( 'Fresh_Forms_For_Gravity_Bootstrap', 'load' ), 5 );
register_activation_hook( __FILE__, 'fffg_purge_all_cache' );
add_action( 'wp_loaded', 'fffg_actions_after_update' );

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
	global $file_prefix, $kinsta_cache, $epc; // Third-party variables that we may need.

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
	if ( function_exists( 'w3tc_flush_all' ) ) {
		// Delete all cache.
		w3tc_flush_all();
	}

	// WP Super Cache.
	if ( function_exists( 'wp_cache_clean_cache' ) ) {
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

	// WP-Optimize.
	if ( class_exists( 'WP_Optimize_Cache_Commands' ) ) {
		// This function returns a response, so I'm assigning it to a variable to prevent unexpected output.
		$response = WP_Optimize_Cache_Commands::purge_page_cache();
	}

	// WP-Optimize minification files have a different cache.
	if ( class_exists( 'WP_Optimize_Minify_Cache_Functions' ) ) {
		// This function returns a response, so I'm assigning it to a variable to prevent unexpected output.
		$response = WP_Optimize_Minify_Cache_Functions::purge();
	}

	// Kinsta Cache.
	if ( class_exists( 'Kinsta\Cache' ) && is_object( $kinsta_cache ) ) {
		// $kinsta_cache object already created by Kinsta cache.php file.
		$kinsta_cache->kinsta_cache_purge->purge_complete_full_page_cache();
	}

	// Endurance Page Cache.
	if ( class_exists( 'Endurance_Page_Cache' ) && is_object( $epc ) ) {
		// $epc object already created by endurance-page-cache.php file.
		$epc->purge_all();
	}

	// Pantheon Cache. Documentation points to a different function, but seems outdated, as it doesn't exist in the current plugin files at GitHub.
	if ( function_exists( 'pantheon_wp_clear_edge_all' ) ) {
		// This function returns a response, so I'm assigning it to a variable to prevent unexpected output.
		$response = pantheon_wp_clear_edge_all();
	}
}

/**
 * Actions to perform after an update.
 */
function fffg_actions_after_update() {
	// Get stored version if any.
	$current_version = get_option( 'fffg_version' );

	// Return without actions if stored version match with defined version.
	if ( version_compare( $current_version, FRESH_FORMS_FOR_GRAVITY_VERSION, '==' ) ) {
		return;
	}

	/**
	 * Try to flush cache to make sure changes made to new version are applied.
	 * Known issue: WP-Optimize classes are not loaded at this point.
	 */
	fffg_purge_all_cache();

	// Update stored version.
	update_option( 'fffg_version', FRESH_FORMS_FOR_GRAVITY_VERSION );

}
