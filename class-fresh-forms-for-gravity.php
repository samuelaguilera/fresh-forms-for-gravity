<?php
/**
 * Fresh Forms for Gravity Add-On.
 *
 * @since     1.0
 * @package Fresh Forms for Gravity
 * @author    Samuel Aguilera
 * @copyright Copyright (c) 2019 Samuel Aguilera
 */

GFForms::include_addon_framework();

/**
 * Class Fresh_Forms_For_Gravity
 *
 * Primary class to manage the Fresh Forms for Gravity Add-On.
 *
 * @since 1.0
 *
 * @uses GFAddOn
 */
class Fresh_Forms_For_Gravity extends GFAddOn {

	protected $_version                  = FRESH_FORMS_FOR_GRAVITY_VERSION;
	protected $_min_gravityforms_version = '2.3';
	protected $_slug                     = 'fresh_forms';
	protected $_path                     = 'fresh-forms-for-gravity/fresh-forms-for-gravity.php';
	protected $_full_path                = __FILE__;
	protected $_title                    = 'Fresh Forms for Gravity';
	protected $_short_title              = 'Fresh Forms';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return Fresh_Forms_For_Gravity
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new Fresh_Forms_For_Gravity();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks.
	 */
	public function init() {
		parent::init();
		// Let's see if we need to stop caching.
		add_filter( 'template_redirect', array( $this, 'donotcache_and_headers' ) );

		// WP Fastest Cache doesn't support DONOTCACHEPAGE ...
		if ( class_exists( 'WpFastestCache' ) ) {
			add_filter( 'wp_footer', array( $this, 'wpfc_blockCache' ) );
		}

		// I could check for the CloudFlare plugin, but many people is using CloudFlare without having the plugin installed.
		add_filter( 'script_loader_tag', 'rocket_loader_exclude_gf_scripts', 10, 3 );

		/**
		 * Exclude Gravity Forms scripts from Rocket Loader minification. All Gravity Forms scripts are already minified.
		 *
		 * @param string $tag    The <script> tag for the enqueued script.
		 * @param string $handle The script's registered handle.
		 * @param string $src    The script's source URL.
		 */
		function rocket_loader_exclude_gf_scripts( $tag, $handle, $src ) {
			if ( strpos( $handle, 'gform' ) !== false || 'plupload-all' === $handle ) {
				return str_replace( "src='", "data-cfasync='false' src='", $tag );
			} else {
				return $tag;
			}
		}

		// All Gravity Forms scripts are already minified.
		add_filter( 'sgo_js_minify_exclude', 'sgo_exclude_gf_scripts' );
		// Current branch of Gravity Forms (2.4), by default, doesn't support async loading of scripts.
		add_filter( 'sgo_js_async_exclude', 'sgo_exclude_gf_scripts' );

		/**
		 * Exclude Gravity Forms scripts from SGO minification and async loading.
		 *
		 * @param array $exclude_list List of script handlers to exclude.
		 */
		function sgo_exclude_gf_scripts( $exclude_list ) {

			$exclude_list[] = 'gform_conditional_logic';
			$exclude_list[] = 'gform_datepicker_init';
			$exclude_list[] = 'plupload-all';
			$exclude_list[] = 'gform_json';
			$exclude_list[] = 'gform_gravityforms';
			$exclude_list[] = 'gform_textarea_counter';
			$exclude_list[] = 'gform_masked_input';
			$exclude_list[] = 'gform_chosen';
			$exclude_list[] = 'gform_placeholder';

			return $exclude_list;
		}

		add_filter( 'autoptimize_filter_js_exclude', 'autoptimize_exclude_gf_scripts' );
		/**
		 * Exclude Gravity Forms scripts from Autoptimize.
		 *
		 * @param string $js_excluded Comma separated list of scripts filenames.
		 */
		function autoptimize_exclude_gf_scripts( $js_excluded ) {
			$minify_excluded .= ', gravityforms.min.js, conditional_logic.min.js';
			$minify_excluded .= ', jquery.textareaCounter.plugin.min.js, jquery.json.min.js';
			$minify_excluded .= ', chosen.jquery.min.js, jquery.maskedinput.min.js';
			$minify_excluded .= ', datepicker.min.js, placeholders.jquery.min.js';

			return $js_excluded;
		}

	}

	/**
	 * Check if post/page has a GF shortcode or block.
	 *
	 * @param integer $post_id      ID of the post.
	 * @param string  $post_content Post body.
	 */
	public function has_gf( $post_id, $post_content ) {

		// Setting initial values for the $has_gf array.
		$has_gf = array(
			'shortcode' => false,
			'block'     => false,
		);

		// Check for a GF shortcode.
		if ( has_shortcode( $post_content, 'gravityform' ) ) {
			// Shortcode found!
			$has_gf['shortcode'] = 'yes';
			$this->log_debug( __METHOD__ . '(): GF shortcode detected in post ID ' . $post_id );
		}

		// Check for a GF block.
		if ( function_exists( 'has_block' ) ) {

			$gf_blocks = array( 'gravityforms/block', 'gravityforms/mailchimp', 'gravityforms/form' );

			foreach ( $gf_blocks as $gf_block ) {

				if ( has_block( $gf_block, $post_content ) ) {

					// Block found!
					$has_gf['block'] = 'yes';
					$this->log_debug( __METHOD__ . '(): GF block detected in post ID ' . $post_id );

					// No need to keep running.
					break;
				}
			}
		}

		// Return array with results.
		return $has_gf;

	}

	/**
	 * Check if we're in a post/page and has the shortcode or block.
	 *
	 * @param integer $post_id      ID of the post.
	 */
	public function maybe_no_cache( $post_id ) {

		$post = get_post( $post_id );

		$this->log_debug( __METHOD__ . '(): Calling has_gf() for post ID ' . $post_id );
		$has_gf = $this->has_gf( $post->ID, $post->post_content );

		// No shortcode and no block? Do nothing.
		if ( ! in_array( 'yes', $has_gf, true ) ) {
			$this->log_debug( __METHOD__ . '(): No form found, nothing to do...' );
			return false;
		}

		return true;
	}

	/**
	 *  Prevent caching if a GF shortcode or block is embedded in the post/page.
	 */
	public function donotcache_and_headers() {

		global $post;

		// Running only for posts (any type) and pages.
		if ( ! is_single() && ! is_page() ) {
			return;
		}

		$has_gf = $this->maybe_no_cache( $post->ID );

		// No shortcode and no block? Do nothing.
		if ( false === $has_gf ) {
			return;
		}

		// At this point we have found a shortcode or block, fresh time!
		$this->log_debug( __METHOD__ . '(): Keep it fresh!' );

		// Delete existing cache? No. Why? If the page is cached no PHP will be executed for the page, so we can't do anything.

		// WP Engine System cookie.
		if ( class_exists( 'WpeCommon' ) ) {
			/*
			 * No support for DONOTCACHEPAGE and not filters available. They only allow a few cookies to exclude pages from caching.
			 * Value is not important really, but I'm using a nonce anyway.
			 */
			setcookie( 'wpengine_no_cache', wp_create_nonce( 'fffg' ), 0, "/$post->post_name/" );
			$this->log_debug( __METHOD__ . "(): Cookie set for WP Engine System. Path: /$post->post_name/" );

			/*
			 * WPE doesn't allow third-party caching plugins https://wpengine.com/blog/no-caching-plugins/
			 * and Cache-Control header modification is not allowed either, so we can stop here for WPE hosted sites.
			 */
			return;
		}

		// Kinsta Cache.
		if ( class_exists( 'Kinsta\Cache' ) && ! is_admin() && ! is_user_logged_in() ) {
			/*
			 * No support for DONOTCACHEPAGE, not filters available, no special cookies. Even no interface for cache exclusion!
			 * They really don't want to allow you to decide which pages to exclude from their cache by your own.
			 * That's a bad practice in my opinion. So we have only a dirty hack to avoid caching ¯\_(ツ)_/¯
			 */
			setcookie( 'wordpress_logged_in_' . wp_hash( 'pleasekinstaaddsupportfordonotcachepageconstant' ), 1, 0, "/$post->post_name/" );
			$this->log_debug( __METHOD__ . "(): Cookie set for Kinsta Cache. Path: /$post->post_name/" );

			// As far as I know Kinsta doesn't forbid the use of other caching plugins. So let's Fresh Forms continue...

		}

		// SG Optimizer cookie.
		if ( class_exists( 'SiteGround_Optimizer\Supercacher\Supercacher' ) ) {
			header( 'X-Cache-Enabled: False', true );
			setcookie( 'wpSGCacheBypass', 1, 0, "/$post->post_name/" );
			$this->log_debug( __METHOD__ . "(): Cookie set for SG Optimizer. Path: /$post->post_name/" );
		}

		// Prevent post (currently not cached) to be cached by plugins.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		// Prevent object caching.
		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( 'DONOTCACHEOBJECT', true );
		}

		// Prevent database caching.
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( 'DONOTCACHEDB', true );
		}

		// LiteSpeed Page Cache.
		if ( ! defined( 'LSCACHE_NO_CACHE' ) ) {
			define( 'LSCACHE_NO_CACHE', true );
		}
		// LiteSpeed Object Cache.
		if ( ! defined( 'LSCWP_OBJECT_CACHE' ) ) {
			define( 'LSCWP_OBJECT_CACHE', false );
		}

		// Autoptimize. What's the point of minifiying scripts that were excluded?
		add_filter( 'autoptimize_filter_js_minify_excluded', '__return_false' );

		// Sets the nocache headers to prevent caching by browsers and proxies respecting these headers.
		nocache_headers();
		// Adding no-store value to Cache-Control header for additional enforcement.
		header( 'Cache-Control: no-store', false );

	}

	/**
	 *  Prevent caching for WP Fastest Cache.
	 */
	public function wpfc_blockCache() {

		global $post;

		// Running only for posts (any type) and pages.
		if ( ! is_single() && ! is_page() ) {
			return;
		}

		$has_gf = $this->maybe_no_cache( $post->ID );

		// No shortcode and no block? Do nothing.
		if ( false === $has_gf ) {
			return;
		}

		echo '<!-- [wpfcNOT] -->';
		$this->log_debug( __METHOD__ . '(): Added [wpfcNOT] for WP Fastest Cache.' );
	}

}
