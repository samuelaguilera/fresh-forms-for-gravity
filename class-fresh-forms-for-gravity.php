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
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return file_get_contents( $this->get_base_path() . '/images/menu-icon.svg' ); // phpcs:ignore
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'       => esc_html__( 'ACF Settings', 'fresh-forms-for-gravity' ),
				'description' => '<p style="text-align: left;">' . esc_html__( 'Enable optional ACF support using the settings below.', 'fresh-forms-for-gravity' ) . '</p>',
				'fields'      => array(
					array(
						'name'    => 'acf',
						'label'   => esc_html__( 'Search ACF fields for:', 'fresh-forms-for-gravity' ),
						'type'    => 'checkbox',
						'choices' => array(
							array(
								'label'         => esc_html__( 'Gravity Forms shortcode', 'fresh-forms-for-gravity' ),
								'name'          => 'acf_shortcode',
								'default_value' => 0,
							),
							array(
								'label'         => esc_html__( 'Gravity Forms gform_wrapper class', 'fresh-forms-for-gravity' ),
								'name'          => 'acf_scan',
								'default_value' => 0,
								'tooltip'       => esc_html__( 'Enable this option only if the shortcode option is not able to detect the form.', 'fresh-forms-for-gravity' ),
							),
						),
					),
				),
			),
			array(
				'title'  => esc_html__( 'Other Settings', 'fresh-forms-for-gravity' ),
				'fields' => array(
					array(
						'type'          => 'text',
						'name'          => 'force_has_form',
						'label'         => esc_html__( 'Force Fresh Forms to run for the following page or post IDs', 'fresh-forms-for-gravity' ),
						'tooltip'       => esc_html__( 'This is useful if your caching plugin is supported but the embedding method is not.', 'fresh-forms-for-gravity' ),
						'default_value' => '',
						'after_input'   => esc_html__( 'Enter a comma separated list. Example: 6,8,5,3', 'fresh-forms-for-gravity' ),
					),
				),
			),
		);
	}

	/**
	 * Handles save of settings and clearing cache after it.
	 *
	 * @param array $settings Plugin settings to be saved.
	 */
	public function update_plugin_settings( $settings ) {
		parent::update_plugin_settings( $settings );
		fffg_purge_all_cache(); // Clear the site cache after saving settings.
	}

	/**
	 * Handles hooks.
	 */
	public function init() {
		parent::init();
		// Let's see if we need to stop caching.
		add_filter( 'template_redirect', array( $this, 'fresh_content' ) );

		// WP Fastest Cache doesn't support DONOTCACHEPAGE ...
		if ( class_exists( 'WpFastestCache' ) ) {
			add_filter( 'wp_footer', array( $this, 'wpfc_blockCache' ) );
		}

		/**
		 * WP-Optimize blocks any other code using WordPress core actions/filters after many of their functions by using PHP_INT_MAX as priority for their add_action/add_filter lines.
		 * This also completely invalidates most of the filters WP-Optimize provides to make cache/minify exclusions if you need to use the same WordPress core filters.
		 * ¯\_(ツ)_/¯
		 */
		if ( class_exists( 'WP_Optimize' ) ) {
			// Using this filter to avoid WP-Optimize invalidating the use of its filters when using template_redirect due to bad use of PHP_INT_MAX for add_filter.
			add_filter( 'wp_default_scripts', array( $this, 'wpo_no_cache_minify' ) );
		}

		/*
		 * Endurance Page Cache.
		 * NOTE: X-Endurance-Cache-Level header is set by .htacces rule, so it doesn't necessarily represents the current caching level.
		 * Need more testing to confirm if Endurance Page Cache is really allowing us to exclude the page with this UNDOCUMENTED filter.
		 */
		if ( class_exists( 'Endurance_Page_Cache' ) ) {
			add_filter( 'init', array( $this, 'epc_no_cache' ) ); // EPC runs start() function on init.
		}

		// I could check for the CloudFlare plugin, but many people is using CloudFlare without having the plugin installed.
		add_filter( 'script_loader_tag', 'rocket_loader_exclude_gf_scripts', 99, 3 );

		/**
		 * Exclude Gravity Forms scripts from Rocket Loader minification. All Gravity Forms scripts are already minified.
		 *
		 * @param string $tag    The <script> tag for the enqueued script.
		 * @param string $handle The script's registered handle.
		 * @param string $src    The script's source URL.
		 */
		function rocket_loader_exclude_gf_scripts( $tag, $handle, $src ) {
			if ( is_array( FFFG_JS_HANDLERS ) && in_array( $handle, FFFG_JS_HANDLERS, true ) ) {
				// Prevent issues with CloudFlare Rocket Loader.
				$tag = str_replace( 'src="', 'data-cfasync="false" src="', $tag );
			}
			return $tag;
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
			$exclude_list = array_merge( $exclude_list, FFFG_JS_HANDLERS );

			return $exclude_list;
		}

		// Prevent combination of inline GF scripts when SGO Combine JavaScript Files is enabled. This prevents issues with confirmations redirection.
		add_filter( 'sgo_javascript_combine_excluded_inline_content', 'sgo_exclude_inline_gf_scripts' );

		/**
		 * Exclude Gravity Forms inline scripts from SGO "Combine JavaScript Files" feature.
		 *
		 * @param array $js_excluded First few symbols of inline content script.
		 */
		function sgo_exclude_inline_gf_scripts( $js_excluded ) {
			$js_excluded = array_merge( $js_excluded, FFFG_JS_INLINE_PARTIAL );
			return $js_excluded;
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
			$exclude_params[] = 'gf-download'; // Secure Downloads.

			return $exclude_params;
		}

		add_filter( 'sgo_javascript_combine_excluded_external_paths', 'sgo_exclude_js_combine_external_scripts' );

		/**
		 * Exclude sensitive external scripts sources from SGO "Combine JavaScript Files" feature.
		 *
		 * @param array $js_excluded Domains for external JS that you want to exclude.
		 */
		function sgo_exclude_js_combine_external_scripts( $js_excluded ) {
			$js_excluded = array_merge( $js_excluded, FFFG_JS_EXTERNAL_DOMAIN );
			return $js_excluded;
		}

		// Autoptimize. What's the point of minifiying scripts that were excluded?
		add_filter( 'autoptimize_filter_js_minify_excluded', '__return_false', 99 ); // Lower priority to ensure it runs later than default.
		// Add Gravity Forms scripts to the excluded JS list.
		add_filter( 'autoptimize_filter_js_exclude', 'autoptimize_exclude_gf_scripts', 99 ); // Lower priority to ensure it runs later than default.

		/**
		 * Exclude Gravity Forms scripts from Autoptimize.
		 *
		 * @param string $js_excluded Comma separated list of scripts filenames.
		 */
		function autoptimize_exclude_gf_scripts( $js_excluded ) {
			$js_excluded .= ', /wp-content/plugins/gravityforms/js/, /wp-content/plugins/gravityforms2checkout/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformscoupons/js/, /wp-content/plugins/gravityformschainedselects/js/, /wp-content/plugins/gravityformsdropbox/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformsmollie/js/, /wp-content/plugins/gravityformspartialentries/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformspaypal/js/, /wp-content/plugins/gravityformsppcp/js/, /wp-content/plugins/gravityformspaypalpro/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformspolls/js/, /wp-content/plugins/gravityformsquiz/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformssignature/js/, /wp-content/plugins/gravityformssquare/js/';
			$js_excluded .= ', /wp-content/plugins/gravityformsstripe/js/, /wp-content/plugins/gravityformsurvey/js/, /wp-content/plugins/gravityformssignature/includes/super_signature/';
			$js_excluded .= ', /wp-includes/js/dist/a11y.min.js, /wp-includes/js/plupload/plupload.min.js'; // WP dependencies for GF features.

			return $js_excluded;
		}

		/**
		 * Add Gravity Forms scripts to WP-Optimize default exclusions.
		 * No documentation available for the filter, but it seems to be valid to use any partial match for the the script path.
		 */
		add_filter( 'wp-optimize-minify-default-exclusions', 'partial_match_exclude_gf_js_files', 99 ); // Lower priority to ensure it runs later than default.

		/**
		 * Function to exclude scripts for plugin filters using URL partial match.
		 *
		 * @param array $js_excluded Array of scripts partial matches to exclude.
		 */
		function partial_match_exclude_gf_js_files( $js_excluded ) {
			// External domains.
			$js_excluded = array_merge( $js_excluded, FFFG_JS_EXTERNAL_DOMAIN );
			// Local paths.
			$js_excluded = array_merge( $js_excluded, FFFG_JS_PARTIAL );

			return $js_excluded;
		}

		add_filter( 'wphb_minify_resource', 'wphb_exclude_gravity_scripts', 99, 3 );
		add_filter( 'wphb_combine_resource', 'wphb_exclude_gravity_scripts', 99, 3 );
		add_filter( 'wphb_minification_display_enqueued_file', 'wphb_exclude_gravity_scripts', 99, 3 );
		/**
		 * Exclude Gravity Forms script files from Hummingbird minification.
		 * No documentation available for the filters.
		 *
		 * @param bool   $action False to exclude the script.
		 * @param string $handle Handle registered for the resource.
		 * @param string $type   scripts or styles.
		 */
		function wphb_exclude_gravity_scripts( $action, $handle, $type ) {
			global $fffg_js_handlers;

			if ( is_array( $handle ) && isset( $handle['handle'] ) ) {
				$handle = $handle['handle'];
			}

			if ( 'scripts' === $type && is_array( $fffg_js_handlers ) && in_array( $handle, $fffg_js_handlers, true ) ) {
				return false;
			}

			return $action;
		}

		/**
		 * Exclude Gravity Forms script files from WP Rocket defer.
		 * Documentation: https://docs.wp-rocket.me/article/976-exclude-files-from-defer-js .
		 */
		add_filter( 'rocket_exclude_defer_js', 'partial_match_exclude_gf_js_files', 99 ); // Lower priority to ensure it runs later than default.

		// Add Gravity Forms scripts to WP Rocket excluded inline JS combining list.
		add_filter( 'rocket_excluded_inline_js_content', 'wprocket_exclude_gf_inline_js', 99 ); // Lower priority to ensure it runs later than default.

		/**
		 * Exclude Gravity Forms script files from WP Rocket inline JS combining.
		 * No documentation for the filter, but according to this https://docs.wp-rocket.me/article/1104-excluding-inline-js-from-combine it will accept any string of the script, like SG Optimizer does.
		 *
		 * @param array $js_excluded Array of scripts to exclude by WP Rocket.
		 */
		function wprocket_exclude_gf_inline_js( $js_excluded ) {
			$js_excluded = array_merge( $js_excluded, FFFG_JS_INLINE_PARTIAL );
			return $js_excluded;
		}

		/**
		 * Exclude Gravity Forms script files from WP Rocket minification/concatenation.
		 * Documentation: https://docs.wp-rocket.me/article/39-excluding-external-js-from-concatenation .
		 */
		add_filter( 'rocket_exclude_js', 'partial_match_exclude_gf_js_files', 99 ); // Lower priority to ensure it runs later than default.

		// Exclude Gravity Forms scripts from Automattic's Page Optimize plugin.
		add_filter( 'js_do_concat', 'pageoptimize_exclude_gf_scripts', 99, 2 );

		/**
		 * Exclude Gravity Forms scripts from Automattic's Page Optimize plugin. No documentation available for this filter.
		 *
		 * @param bool   $do_concat true concatenates the script.
		 * @param string $handle  Script handler name.
		 */
		function pageoptimize_exclude_gf_scripts( $do_concat, $handle ) {
			$do_concat = in_array( $handle, FFFG_JS_HANDLERS ) ? false : true;
			return $do_concat;
		}

		/**
		 * Exclude Gravity Forms script files from Perfmatters delay scripts feature.
		 * Documentation: https://perfmatters.io/docs/filters/#perfmatters_delay_js_exclusions and https://perfmatters.io/docs/delay-javascript .
		 */
		add_filter( 'perfmatters_delay_js_exclusions', 'partial_match_exclude_gf_js_files', 99 );
	}

	/**
	 * Check if post/page has a GF shortcode or block.
	 *
	 * @param object $post Post Object.
	 */
	public function check_gf( $post ) {

		// Return false when $post is not an object.
		if ( ! is_object( $post ) ) {
			$this->log_debug( __METHOD__ . '(): $post not valid. Returning false.' );
			return false;
		}

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
			if ( ! empty( $wc_gfpa_settings ) && is_int( $wc_gfpa_settings['id'] ) ) {
				$this->log_debug( __METHOD__ . "(): Product ID {$post->ID} has GF form {$wc_gfpa_settings['id']} added as product add-ons form." );
				return true;
			}
		}

		// Look for a GF form embedded using WP Tools Gravity Forms Divi Module plugin.
		if ( class_exists( 'WPT_Divi_Gravity_Modules\\GravityFormExtension' ) && has_shortcode( $post->post_content, 'et_pb_wpt_gravityform' ) ) {
			$this->log_debug( __METHOD__ . '(): WP Tools Gravity Forms Divi Module detected!' );
			return true;
		}

		// Ultimate Addons for Beaver Builder and PowerPack for Beaver Builder Gravity Forms modules detection. Beaver Builder Text Editor module doesn't need this.
		if ( class_exists( 'FLBuilderModel' ) && FLBuilderModel::is_builder_enabled( $post->ID ) ) {
			$rows = FLBuilderModel::get_nodes( 'row' );

			$module_list = array_reduce(
				$rows,
				function ( $module_list, $row ) {
					return array_merge( $module_list, $this->get_row_module_list( $row ) );
				},
				array()
			);

			$bb_gravity_modules = array( 'uabb-gravity-form', 'pp-gravity-form' );

			if ( is_array( $module_list ) ) {
				foreach ( $bb_gravity_modules as $bb_gravity_module ) {
					if ( in_array( $bb_gravity_module, $module_list ) ) {
						$this->log_debug( __METHOD__ . '(): Third-party Gravity Forms Styler module for Beaver Builder detected!' );
						return true;
					}
				}
			}
		}

		// Check for Ultimate Addons for Elementor By Brainstorm Force.
		if ( class_exists( 'UAEL_Loader' ) && $this->scan_content( $post->post_content, 'gform_hidden', 'UAEL' ) ) {
			/*
			 * UAEL is replacing the default form wrapper with its own, so checking for gform_wrapper is not possible.
			 * They add their stuff on the fly, it's not saved in the post content, so we can't check for it either.
			 * Checking for gform_hidden instead as the form has always some hidden inputs added by default.
			*/
			$this->log_debug( __METHOD__ . '(): Ultimate Addons for Elementor!' );
			return true;
		}

		/**
		 * Support for GravityKit Gravity Forms Widget for Elementor.
		 */
		if ( class_exists( 'GravityKit\GravityFormsElementorWidget\Widget' ) && 'builder' === get_post_meta( $post->ID, '_elementor_edit_mode', true ) ) {

			$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );

			if ( strpos( $elementor_data, 'gk_elementor_gravity_form' ) !== false ) {
				$this->log_debug( __METHOD__ . '(): GravityKit Gravity Forms Widget for Elementor widget detected!' );
				return true;
			}
		}

		// Legacy freshforms_acf_support filter.
		$shortcode = apply_filters_deprecated( 'freshforms_acf_support', array( $this->get_plugin_setting( 'acf_shortcode' ) ), '1.5' );
		$scan      = apply_filters_deprecated( 'freshforms_acf_support', array( $this->get_plugin_setting( 'acf_scan' ) ), '1.5' );

		$acf_support = $shortcode || $scan ? true : false;

		if ( class_exists( 'ACF' ) && true == $acf_support ) {
			$this->log_debug( __METHOD__ . '(): ACF support is enabled.' );
			$acf_fields = get_field_objects( $post->ID, true, false ); // Get ACF fields without the value.

			if ( is_array( $acf_fields ) && true === $this->find_gf_acf_field( $acf_fields, $shortcode, $scan ) ) {
				return true;
			}
		}

		// If we're here, no form was detected.
		return false;
	}

	/**
	 * Check post content provided for a GF shortcode.
	 *
	 * @param string $post_content      Post content.
	 */
	public function find_gf_shortcode( $post_content ) {

		// Prevent fatal error if post content is not a string (as required by WordPress has_shortcode function).
		// Which could happen for not supported embedding methods or when a third-party is altering the expected post content.
		if ( ! is_string( $post_content ) ) {
			$this->log_debug( __METHOD__ . '(): Post Content is not a string. Aborting...' );
			return false;
		}

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
	 * Search for the value provided inside the content passed.
	 *
	 * @param string $content   Content for searching.
	 * @param string $value     The value to search.
	 * @param string $generator The software that generates the content to scan.
	 */
	public function scan_content( $content, $value, $generator ) {

		// Return without scanning if there's no content to scan.
		if ( ! is_string( $content ) || empty( $content ) ) {
			$this->log_debug( __METHOD__ . "(): {$generator} content is empty or not a string. Nothing to scan." );
			return false;
		}

		$this->log_debug( __METHOD__ . "(): {$generator} content to scan: {$content} " );
		// Look for the gform_wrapper.
		if ( strpos( $content, $value ) !== false ) {
			// Scanned value found!
			$this->log_debug( __METHOD__ . "(): {$value} detected in post content." );
			return true;
		}
		// If we're here, there's no GF form class.
		return false;
	}

	/**
	 * Check ACF field content provided for a GF shortcode or form.
	 *
	 * @param array $acf_fields ACF Fields saved for the post.
	 * @param bool  $shortcode  Set to true to scan ACF fields for a GF shortocode.
	 * @param bool  $scan       Set to true to scan ACF fields for the gform_wrapper class.
	 */
	public function find_gf_acf_field( $acf_fields, $shortcode = false, $scan = false ) {

		$supported_acf_fields = array( 'text', 'textarea', 'wysiwyg', 'flexible_content', 'repeater' );

		foreach ( $acf_fields as $acf_field ) {

			if ( ! in_array( $acf_field['type'], $supported_acf_fields, true ) ) {
				$this->log_debug( __METHOD__ . "(): ACF field not supported. Skipping field: {$acf_field['name']}" );
				continue;
			}

			// Get the field content. No need to set the post id as the list of fields received is already limited to the current post.
			$acf_field_content = get_field( $acf_field['name'] ); // Using name which in this case means "custom field meta key name".

			if ( true == $shortcode ) { // Check for GF shortcodes added to ACF fields if it's enabled.

				// Look for a GF shortcode inside a standalone text or textarea fields.
				if ( ( is_string( $acf_field_content ) && 'text' === $acf_field['type'] ) || ( is_string( $acf_field_content ) && 'textarea' === $acf_field['type'] ) ) {
					$this->log_debug( __METHOD__ . "(): Checking for GF shorcodes added to ACF field {$acf_field['name']}" );
					if ( true === $this->find_gf_shortcode( $acf_field_content ) ) {
						$this->log_debug( __METHOD__ . "(): ACF {$acf_field['type']} field has a GF form!" );
						return true;
					}
				}

				if ( ( 'flexible_content' === $acf_field['type'] || 'repeater' === $acf_field['type'] ) && ! empty( $acf_field_content ) ) {
					// Look for a GF shortcode inside the value of any sub-field for a flexible_content or repeater field.
					foreach ( $acf_field_content as $acf_subfield_array ) {
						foreach ( $acf_subfield_array as $key => $value ) {
							$this->log_debug( __METHOD__ . "(): Checking for GF shorcodes added to ACF field {$acf_field['name']}" );
							if ( is_string( $value ) && true === $this->find_gf_shortcode( $value ) ) {
								$this->log_debug( __METHOD__ . "(): ACF {$acf_field['type']} field has a GF form!" );
								return true;
							}
						}
					}
				}
			} // Shortcode search done.

			if ( true == $scan ) { // Check for GF class added to ACF fields if it's enabled.

				if ( is_string( $acf_field_content ) && 'wysiwyg' === $acf_field['type'] ) { // Look for a GF class inside a standalone wysiwyg field.
					$this->log_debug( __METHOD__ . "(): Checking GF HTML markup for ACF field {$acf_field['name']}" );
					if ( true === $this->scan_content( $acf_field_content, 'gform_wrapper', 'ACF' ) ) {
						$this->log_debug( __METHOD__ . "(): ACF {$acf_field['type']} field has a GF form!" );
						return true;
					}
				}

				if ( ( 'flexible_content' === $acf_field['type'] || 'repeater' === $acf_field['type'] ) && ! empty( $acf_field_content ) ) {
					// Look for a GF shortcode or GF class inside the value of any sub-field for a flexible_content or repeater field.
					foreach ( $acf_field_content as $acf_subfield_array ) {
						foreach ( $acf_subfield_array as $key => $value ) {
							$this->log_debug( __METHOD__ . "(): Checking GF HTML markup for ACF field {$acf_field['name']}" );
							if ( is_string( $value ) && true === $this->scan_content( $value, 'gform_wrapper', 'ACF' ) ) {
								$this->log_debug( __METHOD__ . "(): ACF {$acf_field['type']} field has a GF form!" );
								return true;
							}
						}
					}
				}
			} // Class scan done.

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
			function ( $module_list, $group ) {
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
			function ( $module_list, $col ) {
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
			function ( $module_list, $node ) {
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
	 * Return true or false to exclude a post/page.
	 *
	 * @param integer $post_id      ID of the post.
	 */
	public function exclude_the_post( $post_id ) {

		// Prevent going further if no valid post ID is provided.
		if ( false === is_int( $post_id ) ) {
			$this->log_debug( __METHOD__ . "(): Post ID provided is not valid: {$post_id}" );
			return false;
		}

		// Allow forcing Fresh Form run for certain post ID's without doing the checkings.
		$force_has_form = $this->get_plugin_setting( 'force_has_form' ) ? $this->get_plugin_setting( 'force_has_form' ) : '';
		// Remove any empty spaces and convert to array.
		$force_has_form = array_map( 'intval', explode( ',', preg_replace( '/\s+/', '', $force_has_form ) ) );
		// Allow settings to be filtered.
		$force_has_form = apply_filters( 'freshforms_post_has_gform', $force_has_form );

		// Exclude posts if any ID was provided.
		if ( ! empty( $force_has_form ) && in_array( $post_id, $force_has_form, true ) ) {
			$this->log_debug( __METHOD__ . "(): Form detection forced to return true by setting or filter for post ID {$post_id}." );
			return true;
		}

		// Get the post_type for Conversational Forms check.
		$post_type = get_post_type( $post_id );
		$this->log_debug( __METHOD__ . "(): Post Type is {$post_type}" );

		// Exclude Conversational Forms pages.
		if ( ! empty( $post_type ) && 'conversational_form' === $post_type ) {
			$this->log_debug( __METHOD__ . '(): Conversational Forms page detected!' );
			return true;
		}

		// Now we want the full post.
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
	 *  Prevent caching if a GF shortcode or block is embedded in the content.
	 */
	public function fresh_content() {

		global $post;

		// Running only for single posts (any type) and pages.
		if ( ! is_object( $post ) || ! is_singular() ) {
			return;
		}

		// No shortcode and no block? Do nothing.
		if ( false === $this->exclude_the_post( $post->ID ) ) {
			return;
		}

		// At this point we have found a shortcode or block, fresh time!
		$this->log_debug( __METHOD__ . '(): Keep it fresh!' );

		// Delete existing cache? No. Why? If the page is cached no PHP will be executed for the page, so we can't do anything.

		// W3TC. Another plugin without documentation for the filters. As far as I know there are no filters to exclude per handler, path to file, etc.
		if ( class_exists( 'W3TC\Minify_Plugin' ) ) {
			add_filter( 'w3tc_minify_js_enable', '__return_false', 99 );
			add_filter( 'w3tc_minify_html_enable', '__return_false', 99 ); // This includes inline JS minify.
		}
		// W3TC has support for DONOTCACHEPAGE, but just in case something else could be changing DONOTCACHEPAGE value after FF.
		if ( class_exists( 'W3TC\PgCache_ContentGrabber' ) ) {
			add_filter( 'w3tc_can_cache', '__return_false', 99 );
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

		// LiteSpeed Cache plugin.
		if ( ! defined( 'LITESPEED_DISABLE_ALL' ) ) {
			define( 'LITESPEED_DISABLE_ALL', true );
		}

		// Adds WP nocache headers and some additional stuff.
		$this->headers_and_cookies( $post );
	}

	/**
	 *  Prevent caching for WP Fastest Cache.
	 */
	public function wpfc_blockCache() {

		global $post;

		// Running only for posts (any type) and pages.
		if ( ! is_singular() ) {
			return;
		}

		// No shortcode and no block? Do nothing.
		if ( false === $this->exclude_the_post( $post->ID ) ) {
			return;
		}

		echo '<!-- [wpfcNOT] -->';
		$this->log_debug( __METHOD__ . '(): Added [wpfcNOT] for WP Fastest Cache.' );
	}

	/**
	 *  Prevent WP-Optimize cache and minification when a form is found.
	 */
	public function wpo_no_cache_minify() {

		global $post;

		// Running only for posts (any type) and pages.
		if ( ! is_singular() ) {
			return;
		}

		// No shortcode and no block? Do nothing.
		if ( false === $this->exclude_the_post( $post->ID ) ) {
			return;
		}

		// Exclude form page from WP-Optimize cache.
		add_filter( 'wpo_can_cache_page', '__return_false', 99 ); // Lower priority to ensure it runs later than default.
		/**
		 * WP-Optimize exclude minification of the form page.
		 * The developer doesn't provide documentation for the filters, but the function where it's applied is also used to exclude WooCommerce Checkout.
		 * So it seems like the right one to exclude other forms.
		 */
		add_filter( 'wpo_minify_exclude_contents', '__return_true', 99 ); // Lower priority to ensure it runs later than default.
		add_filter( 'wpo_minify_run_on_page', '__return_true', 99 ); // Lower priority to ensure it runs later than default.
	}

	/**
	 *  Prevent Endurance Page Cache.
	 */
	public function epc_no_cache() {

		global $post;

		// Running only for posts (any type) and pages.
		if ( ! is_singular() ) {
			return;
		}

		// No shortcode and no block? Do nothing.
		if ( false === $this->exclude_the_post( $post->ID ) ) {
			return;
		}

		// At this point we have a form.
		add_filter( 'epc_is_cachable', '__return_false', 99 );
	}

	/**
	 *  Adds WP nocache headers and some additional stuff.
	 *
	 *  @param integer $post The post object.
	 */
	public function headers_and_cookies( $post ) {

		// WP Engine System cookie.
		if ( class_exists( 'WpeCommon' ) ) {
			/*
			 * No support for DONOTCACHEPAGE and not filters available. They only allow a few cookies to exclude pages from caching.
			 * Value is not important really, but I'm using a nonce anyway.
			 */
			setcookie( 'wpengine_no_cache', wp_create_nonce( 'fffg' ), 0, $this->return_cookie_path( $post ) ); // Will expire at the end of the session (when the browser closes).
			$this->log_debug( __METHOD__ . '(): Cookie set for WP Engine System. Path: ' . $this->return_cookie_path( $post ) );

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
			setcookie( 'wordpress_logged_in_' . wp_hash( 'pleasekinstaaddsupportfordonotcachepageconstant' ), 1, 0, $this->return_cookie_path( $post ) ); // Will expire at the end of the session (when the browser closes).
			$this->log_debug( __METHOD__ . '(): Cookie set for Kinsta Cache. Path: ' . $this->return_cookie_path( $post ) );

			// As far as I know Kinsta doesn't forbid the use of other caching plugins. So let's Fresh Forms continue...

		}

		// SG Optimizer cookie. This will turn off both x-cache and proxy-cache.
		if ( class_exists( 'SiteGround_Optimizer\Supercacher\Supercacher' ) ) {
			header( 'X-Cache-Enabled: False', true );
			setcookie( 'wpSGCacheBypass', 1, 0, $this->return_cookie_path( $post ) ); // Will expire at the end of the session (when the browser closes).
			$this->log_debug( __METHOD__ . '(): Cookie set for SG Optimizer. Path: ' . $this->return_cookie_path( $post ) );
		}

		// Sets the nocache headers to prevent caching by browsers and proxies respecting these headers.
		nocache_headers();
		// Adding no-store value to Cache-Control header for additional enforcement.
		header( 'Cache-Control: no-store', false );
		// Adding Fresh-Forms header. Reminder: WPE doesn't support doing modifications to the HTTP headers, so this will not work for WPE hosted sites.
		header( 'Fresh-Forms: ' . FRESH_FORMS_FOR_GRAVITY_VERSION, false );

		/**
		 * Optionally add a custom cookie to be used with caching systems not allowing other methods to exclude pages from cache.
		 * Requires additional setup on your caching software to recognize the cookie created.
		 */
		$fresh_forms_cookie = apply_filters( 'freshforms_add_cookie', false );
		if ( true === $fresh_forms_cookie ) {
			setcookie( 'FreshForms', 'no-cache', 0, $this->return_cookie_path( $post ) ); // Will expire at the end of the session (when the browser closes).
			$this->log_debug( __METHOD__ . '(): FreshForms Cookie added. Path: ' . $this->return_cookie_path( $post ) );
		}
	}

	/**
	 *  Returns the cookie path caching methods requiring a cookie.
	 *
	 *  @param integer $post The post object.
	 */
	public function return_cookie_path( $post ) {
		return is_object( $post ) && is_singular() ? "/$post->post_name/" : '/';
	}
}
