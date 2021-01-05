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
		// Prevent combination of GF scripts when SGO Combine JavaScript Files is enabled.
		add_filter( 'sgo_javascript_combine_exclude', 'sgo_exclude_gf_scripts' );

		/**
		 * Exclude Gravity Forms scripts from SGO minification, async loading, and JS combination.
		 *
		 * @param array $exclude_list List of script handlers to exclude.
		 */
		function sgo_exclude_gf_scripts( $exclude_list ) {

			$exclude_list[] = 'jquery'; // Yeah, not a GF script but many of them (and themes, and etc...) have it as dependency.
			$exclude_list[] = 'gform_gravityforms';
			$exclude_list[] = 'gform_conditional_logic';
			$exclude_list[] = 'gform_datepicker_init';
			$exclude_list[] = 'plupload-all';
			$exclude_list[] = 'gform_json';
			$exclude_list[] = 'gform_textarea_counter';
			$exclude_list[] = 'gform_masked_input';
			$exclude_list[] = 'gform_chosen';
			$exclude_list[] = 'gform_placeholder';
			$exclude_list[] = 'gforms_zxcvbn';
			$exclude_list[] = 'gf_partial_entries'; // Partial Entries Add-On.
			$exclude_list[] = 'stripe.js'; // Stripe Add-On.
			$exclude_list[] = 'stripe_v3';
			$exclude_list[] = 'gforms_stripe_frontend';
			$exclude_list[] = 'gform_coupon_script'; // Coupons Add-On.
			$exclude_list[] = 'gforms_ppcp_frontend'; // PPCP Add-On.
			$exclude_list[] = 'gform_paypal_sdk'; // Dependency for PPCP.
			$exclude_list[] = 'wp-a11y'; // Dependency for PPCP. This and the following three lines fixed issues with a PPCP form.
			$exclude_list[] = 'wp-dom-ready'; // Dependency for wp-a11y.
			$exclude_list[] = 'wp-polyfill'; // Dependency for wp-a11y.
			$exclude_list[] = 'wp-i18n'; // Dependency for wp-a11y.
			$exclude_list[] = 'gforms_square_frontend'; // Square Add-On.
			$exclude_list[] = 'gform_mollie_components'; // Mollie Add-On.
			$exclude_list[] = 'gform_chained_selects'; // Chained Selects Add-On.
			$exclude_list[] = 'gsurvey_js'; // Survey Add-On.
			$exclude_list[] = 'gpoll_js'; // Polls Add-On.
			$exclude_list[] = 'gaddon_token.min.js'; // Credit Card Token.

			return $exclude_list;
		}

		// Prevent combination of inline GF scripts when SGO Combine JavaScript Files is enabled. This prevents issues with confirmations redirection.
		add_filter( 'sgo_javascript_combine_excluded_inline_content', 'sgo_exclude_inline_gf_scripts' );

		/**
		 * Exclude Gravity Forms inline scripts from SGO "Combine JavaScript Files" feature.
		 *
		 * @param array $exclude_list First few symbols of inline content script.
		 */
		function sgo_exclude_inline_gf_scripts( $exclude_list ) {
			$exclude_list[] = 'gformRedirect';
			$exclude_list[] = 'var gf_global';
			$exclude_list[] = 'gformInitSpinner';
			$exclude_list[] = 'var gf_partial_entries';
			$exclude_list[] = '(function(d,s,i,r)'; // HubSpot Tracking Script.
			$exclude_list[] = 'gform.addAction';
			$exclude_list[] = 'gform_post_render';
			$exclude_list[] = 'var gforms_ppcp_frontend_strings'; // PPCP Add-On.
			$exclude_list[] = 'gform_page_loaded'; // Multi-page Ajax forms.
			$exclude_list[] = 'var stripe'; // Stripe Checkout.

			return $exclude_list;
		}

		// This fixes a "contains errors" issue with the Signature page.
		add_filter( 'sgo_html_minify_exclude_params', 'sgo_exclude_gf_pages_html_minify' );

		/**
		 * Exclude Gravity Forms Signature and downloads URL's from SGO Minify the HTML Output feature.
		 *
		 * @param array $exclude_params Query params that you want to exclude.
		 */
		function sgo_exclude_gf_pages_html_minify( $exclude_params ) {
			$exclude_params[] = 'signature'; // Signatures.
			$exclude_params[] = 'gf-signature'; // Signatures since 4.0, old links still use the above.
			$exclude_params[] = 'gf-download'; // Downloads.

			return $exclude_params;
		}

		add_filter( 'sgo_javascript_combine_excluded_external_paths', 'sgo_exclude_js_combine_external_scripts' );

		/**
		 * Exclude sensitive external scripts sources from SGO "Combine JavaScript Files" feature.
		 *
		 * @param array $exclude_list Domains that you want to exclude.
		 */
		function sgo_exclude_js_combine_external_scripts( $exclude_list ) {
			$exclude_list[] = '2checkout.com';
			$exclude_list[] = 'agilecrm.com';
			$exclude_list[] = 'dropbox.com';
			$exclude_list[] = 'js.hs-analytics.net'; // HubSpot Analytics Code.
			$exclude_list[] = 'mollie.com';
			$exclude_list[] = 'paypal.com';
			$exclude_list[] = 'square.com';
			$exclude_list[] = 'stripe.com';

			return $exclude_list;
		}

		// Autoptimize. What's the point of minifiying scripts that were excluded?
		add_filter( 'autoptimize_filter_js_minify_excluded', '__return_false', 99 ); // Lower priority to ensure it runs after any other.
		// Add Gravity Forms scripts to the excluded JS list.
		add_filter( 'autoptimize_filter_js_exclude', 'autoptimize_exclude_gf_scripts', 99 ); // Lower priority to ensure it runs after any other.

		/**
		 * Exclude Gravity Forms scripts from Autoptimize.
		 *
		 * @param string $js_excluded Comma separated list of scripts filenames.
		 */
		function autoptimize_exclude_gf_scripts( $js_excluded ) {
			$js_excluded .= ', /wp-content/plugins/gravityforms/js/, /wp-content/plugins/gravityforms2checkout/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformscoupons/js/, /wp-content/plugins/gravityformsdropbox/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformsmollie/js/, /wp-content/plugins/gravityformspartialentries/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformspaypal/js/, /wp-content/plugins/gravityformsppcp/js/, /wp-content/plugins/gravityformspaypalexpresscheckout/js/, /wp-content/plugins/gravityformspaypalpro/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformspolls/js/, /wp-content/plugins/gravityformsquiz/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformssignature/js/, /wp-content/plugins/gravityformssquare/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformsstripe/js/, /wp-content/plugins/gravityformsurvey/js/';
			$js_excluded .= ', /wp-includes/js/dist/a11y.min.js, /wp-includes/js/plupload/plupload.min.js'; // WP dependencies for GF features.

			return $js_excluded;
		}

	}

	/**
	 * Check if post/page has a GF shortcode or block.
	 *
	 * @param object $post Post Object.
	 */
	public function check_gf( $post ) {

		// Check for GF shortcode.
		if ( true === $this->find_gf_shortcode( $post->post_content ) ) {
			return true;
		}

		// Check for a GF block or GF form in a reusable block.
		if ( function_exists( 'has_block' ) && true === has_blocks( $post->ID ) ) {

			$this->log_debug( __METHOD__ . "(): Post ID {$post->ID} has at least one block. Checking if there's a GF form... " );

			// Check for GF blocks.
			if ( true === $this->find_gf_block( $post->post_content ) ) {
				return true;
			}

			// Additional check for GF forms in reusable blocks.
			$blocks = parse_blocks( $post->post_content );

			foreach ( $blocks as $block ) {

				// Skip block if empty or not a core/block.
				if ( empty( $block['blockName'] ) || 'core/block' !== $block['blockName'] || empty( $block['attrs']['ref'] ) ) {
					continue;
				}

				// Check core/block found.
				$reusable_block = get_post( $block['attrs']['ref'] );

				if ( empty( $reusable_block ) || 'wp_block' !== $reusable_block->post_type ) {
					continue;
				}

				if ( true === $this->find_gf_shortcode( $reusable_block->post_content ) || true === $this->find_gf_block( $reusable_block->post_content ) ) {
					return true;
				}
			}
		}

		/**
		 * Support for Essential Addons for Elementor Gravity Forms widget.
		 * It runs only if Elementor builder is enabled for the post, otherwise you should remove the form HTML and use a shortcode or Gutenber block instead.
		 */
		if ( class_exists( 'Essential_Addons_Elementor\\Classes\\Bootstrap' ) && 'builder' === get_post_meta( $post->ID, '_elementor_edit_mode', true ) ) {
			// Essential Addons for Elementor Gravity Forms removes gform_wrapper for some reason, gform_fields would be an alternative.
			$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );

			if ( strpos( $elementor_data, 'eael-gravity-form' ) !== false ) {
				$this->log_debug( __METHOD__ . '(): Essential Addons for Elementor Gravity Forms widget detected!' );
				return true;
			}
		}

		// WooCommerce Gravity Forms Product Add-ons support enabled by default.
		$wcgfpa_support = apply_filters( 'freshforms_wcgfpa_support', true );

		// Look for a GF form added to a product using WooCommerce Gravity Forms Product Add-ons.
		if ( class_exists( 'WC_GFPA_Main' ) && true === $wcgfpa_support && 'product' === $post->post_type ) {
			$wc_gfpa_settings = get_post_meta( $post->ID, '_gravity_form_data', true );
			if ( ! empty( $wc_gfpa_settings ) && is_numeric( $wc_gfpa_settings['id'] ) ) {
				$this->log_debug( __METHOD__ . "(): Product ID {$post->ID} has GF form {$wc_gfpa_settings['id']} added as product add-ons form." );
				return true;
			}
		}

		// ACF Support disabled by default.
		$acf_support = apply_filters( 'freshforms_acf_support', false );

		// Look for a GF shortcode inside ACF fields.
		if ( class_exists( 'ACF' ) && true === $acf_support ) {
			$acf_fields = get_field_objects( $post->ID );

			if ( is_array( $acf_fields ) && true === $this->find_gf_acf_field( $acf_fields ) ) {
				return true;
			}
		}

		// Look for a GF form embedded using WP Tools Gravity Forms Divi Module plugin.
		if ( class_exists( 'WPT_Divi_Gravity_Modules\\GravityFormExtension' ) && has_shortcode( $post->post_content, 'et_pb_wpt_gravityform' ) ) {
			$this->log_debug( __METHOD__ . '(): WP Tools Gravity Forms Divi Module detected!' );
			return true;
		}

		// UABB Gravity Forms Styler module detection. Beaver Builder Text Editor module doesn't need this.
		if ( class_exists( 'FLBuilderModel' ) && FLBuilderModel::is_builder_enabled( $post->ID ) ) {
			$rows = FLBuilderModel::get_nodes( 'row' );

			$module_list = array_reduce(
				$rows,
				function( $module_list, $row ) {
					return array_merge( $module_list, $this->get_row_module_list( $row ) );
				},
				array()
			);

			if ( is_array( $module_list ) && in_array( 'uabb-gravity-form', $module_list, true ) ) {
				$this->log_debug( __METHOD__ . '(): UABB Gravity Forms Styler module detected!' );
				return true;
			}
		}

		// If we're here, there's no form.
		return false;

	}

	/**
	 * Check post content provided for a GF shortcode.
	 *
	 * @param string $post_content      Post content.
	 */
	public function find_gf_shortcode( $post_content ) {

		// Check for a GF shortcode.
		if ( has_shortcode( $post_content, 'gravityform' ) ) {
			// Shortcode found!
			$this->log_debug( __METHOD__ . '(): GF shortcode detected!' );
			return true;
		}
		// If we're here, there's no GF shortcode.
		return false;
	}

	/**
	 * Check post content provided for a GF block.
	 *
	 * @param string $post_content      Post content.
	 */
	public function find_gf_block( $post_content ) {

		// Get GF blocks registered.
		$gf_blocks = GF_Blocks::get_all_types();

		// Checking for GF blocks.
		foreach ( $gf_blocks as $gf_block ) {

			if ( has_block( $gf_block, $post_content ) ) {
				// Block found!
				$this->log_debug( __METHOD__ . '(): GF block detected! ' );
				return true;
			}
		}
		// If we're here, there's no GF block.
		return false;
	}

	/**
	 * Check post content provided for Gravity Forms class.
	 *
	 * @param string $post_content      Post content.
	 */
	public function find_gform_class( $post_content ) {
		// Look for the gform_wrapper.
		if ( strpos( $post_content, 'gform_wrapper' ) !== false ) {
			// gform_wrapper found!
			$this->log_debug( __METHOD__ . '(): gform_wrapper detected!' );
			return true;
		}
		// If we're here, there's no GF form class.
		return false;
	}

	/**
	 * Check ACF field content provided for a GF shortcode or form.
	 *
	 * @param array $acf_fields ACF Fields saved for the post.
	 */
	public function find_gf_acf_field( $acf_fields ) {

		$supported_acf_fields = array( 'text', 'textarea', 'wysiwyg', 'flexible_content', 'repeater' );

		foreach ( $acf_fields as $acf_field ) {
			if ( ! in_array( $acf_field['type'], $supported_acf_fields, true ) ) {
				continue;
			}

			if ( 'text' === $acf_field['type'] || 'textarea' === $acf_field['type'] ) { // Look for a GF shortcode inside a standalone text or textarea fields.
				if ( true === $this->find_gf_shortcode( $acf_field['value'] ) ) {
					$this->log_debug( __METHOD__ . "(): ACF {$acf_field['type']} field has a GF form!" );
					return true;
				}
			} elseif ( 'wysiwyg' === $acf_field['type'] ) { // Look for a GF class inside a standalone wysiwyg field.
				if ( true === $this->find_gform_class( $acf_field['value'] ) ) {
					$this->log_debug( __METHOD__ . "(): ACF {$acf_field['type']} field has a GF form!" );
					return true;
				}
			} elseif ( ( 'flexible_content' === $acf_field['type'] || 'repeater' === $acf_field['type'] ) && ! empty( $acf_field['value'] ) ) {
				// Look for a GF shortcode or GF class inside the value of any sub-field for a flexible_content field.
				foreach ( $acf_field['value'] as $acf_subfield_array ) {
					foreach ( $acf_subfield_array as $key => $value ) {
						if ( true === $this->find_gf_shortcode( $value ) ) {
							$this->log_debug( __METHOD__ . "(): ACF {$acf_field['type']} field has a GF form!" );
							return true;
						} elseif ( true === $this->find_gform_class( $value ) ) {
							$this->log_debug( __METHOD__ . "(): ACF {$acf_field['type']} field has a GF form!" );
							return true;
						}
					}
				}
			}
		}
		// If we're here, there's no ACF field with a GF form.
		return false;
	}

	/**
	 * Get the list of Beaver Builder modules in a row.
	 *
	 * @param  object $row The row node.
	 *
	 * @return array       The list of modules.
	 */
	private function get_row_module_list( $row ) {
		if ( ! FLBuilderModel::is_node_visible( $row ) ) {
			return array();
		}

		$groups = FLBuilderModel::get_nodes( 'column-group', $row );

		return array_reduce(
			$groups,
			function( $module_list, $group ) {
				return array_merge( $module_list, $this->get_group_module_list( $group ) );
			},
			array()
		);
	}

	/**
	 * Get the list of Beaver Builder modules in a group.
	 *
	 * @param  object $group The group node.
	 *
	 * @return array         The list of modules.
	 */
	private function get_group_module_list( $group ) {

		$cols = FLBuilderModel::get_nodes( 'column', $group );

		return array_reduce(
			$cols,
			function( $module_list, $col ) {
				return array_merge( $module_list, $this->get_column_module_list( $col ) );
			},
			array()
		);
	}

	/**
	 * Get the list of Beaver Builder modules for a column.
	 *
	 * @param  object $col The column node.
	 *
	 * @return array       The list of modules.
	 */
	private function get_column_module_list( $col ) {
		$col = is_object( $col ) ? $col : FLBuilderModel::get_node( $col );

		if ( ! FLBuilderModel::is_node_visible( $col ) ) {
			return array();
		}

		$nodes = FLBuilderModel::get_nodes( null, $col );

		return array_reduce(
			$nodes,
			function( $module_list, $node ) {
				return array_merge( $module_list, $this->get_beaver_builder_node_module_list( $node ) );
			},
			array()
		);
	}

	/**
	 * Get the list of Beaver Builder modules for a node.
	 *
	 * @param object $node The node.
	 *
	 * @return array       The modules.
	 */
	private function get_beaver_builder_node_module_list( $node ) {
		$module_list = array();

		if ( 'module' === $node->type && FLBuilderModel::is_module_registered( $node->settings->type ) ) {
			$module_list[] = $node->settings->type;
		} elseif ( 'column-group' === $node->type ) {
			$module_list = array_merge( $module_list, $this->get_group_module_list( $node ) );
		}

		return $module_list;
	}

	/**
	 * Check if we're in a post/page and has the shortcode or block.
	 *
	 * @param integer $post_id      ID of the post.
	 */
	public function maybe_no_cache( $post_id ) {

		$post = get_post( $post_id );

		// No Gravity Forms form detected? Do nothing.
		if ( false === $this->check_gf( $post ) ) {
			$this->log_debug( __METHOD__ . "(): No form found for Post ID {$post_id}, nothing to do..." );
			return false;
		}

		$this->log_debug( __METHOD__ . "(): Form found for Post ID {$post_id}..." );
		return true;
	}

	/**
	 *  Prevent caching if a GF shortcode or block is embedded in the post/page.
	 */
	public function donotcache_and_headers() {

		global $post;

		// Running only for single posts (any type) and pages.
		if ( ! is_singular() ) {
			return;
		}

		// No shortcode and no block? Do nothing.
		if ( false === $this->maybe_no_cache( $post->ID ) ) {
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

		// SG Optimizer cookie. This will turn off both x-cache and proxy-cache.
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

		// No shortcode and no block? Do nothing.
		if ( false === $this->maybe_no_cache( $post->ID ) ) {
			return;
		}

		echo '<!-- [wpfcNOT] -->';
		$this->log_debug( __METHOD__ . '(): Added [wpfcNOT] for WP Fastest Cache.' );
	}

}
