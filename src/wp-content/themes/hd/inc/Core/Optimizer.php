<?php

declare( strict_types=1 );

namespace HD\Core;

use HD\Utilities\Traits\Singleton;

\defined( 'ABSPATH' ) || die;

/**
 * Optimizer Class
 *
 * @author Gaudev
 */
final class Optimizer {
	use Singleton;

	// ------------------------------------------------------

	private function init(): void {
		$this->_cleanup();
		$this->_optimizer();

		/** custom hooks */
		$this->_customHooks();
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	private function _customHooks(): void {

		// -------------------------------------------------------------
		// permalink structure
		// -------------------------------------------------------------

		if ( ! \HD_Helper::getOption( '_permalink_structure_updated' ) ) {
			\HD_Helper::updateOption( '_permalink_structure_updated', true );
			global $wp_rewrite;

			$wp_rewrite->set_permalink_structure( '/%postname%/' );
			$wp_rewrite->flush_rules();
		}

		// -------------------------------------------------------------
		// images sizes
		// -------------------------------------------------------------

		/**
		 * thumbnail (540x0)
		 * medium (768x0)
		 * large (1024x0)
		 *
		 * small-thumbnail (150x150)
		 * widescreen (1920x9999)
		 * post-thumbnail (1280x9999)
		 */
		if ( ! \HD_Helper::getOption( '_image_sizes_updated' ) ) {
			\HD_Helper::updateOption( '_image_sizes_updated', true );

			/** Default thumb */
			\HD_Helper::updateOption( 'thumbnail_size_w', 540 );
			\HD_Helper::updateOption( 'thumbnail_size_h', 0 );
			\HD_Helper::updateOption( 'thumbnail_crop', 0 );

			/** Medium thumb */
			\HD_Helper::updateOption( 'medium_size_w', 768 );
			\HD_Helper::updateOption( 'medium_size_h', 0 );

			/** Large thumb */
			\HD_Helper::updateOption( 'large_size_w', 1024 );
			\HD_Helper::updateOption( 'large_size_h', 0 );
		}

		/** Custom images sizes */
		add_image_size( 'small-thumbnail', 150, 150, true );
		add_image_size( 'widescreen', 1920, 9999, false );
		add_image_size( 'post-thumbnail', 1200, 9999, false );

		/** Disable unwanted images sizes */
		add_filter( 'intermediate_image_sizes_advanced', static function ( $sizes ) {
			unset( $sizes['medium_large'], $sizes['1536x1536'], $sizes['2048x2048'] );

			// disable 2x medium-large size
			// disable 2x large size

			return $sizes;
		} );

		/** Disable scaled */
		add_filter( 'big_image_size_threshold', '__return_false' );

		/** Disable other sizes */
		add_action( 'init', static function () {
			remove_image_size( '1536x1536' ); // disable 2x medium-large size
			remove_image_size( '2048x2048' ); // disable 2x large size
		} );

		// ------------------------------------------

		add_filter( 'post_thumbnail_html', static function ( $html ) {
			return preg_replace( '/(<img[^>]+)(style=\"[^\"]+\")([^>]+)(>)/', '${1}${3}${4}', $html );
		}, 10, 1 );

		//		add_filter( 'image_send_to_editor', function ( $html ) {
		//			return preg_replace( '/(<img[^>]+)(style=\"[^\"]+\")([^>]+)(>)/', '${1}${3}${4}', $html );
		//		}, 10, 1 );

		add_filter( 'the_content', static function ( $html ) {
			return preg_replace( '/(<img[^>]+)(style=\"[^\"]+\")([^>]+)(>)/', '${1}${3}${4}', $html );
		}, 10, 1 );

		// -------------------------------------------------------------
		// Custom hooks
		// -------------------------------------------------------------

		// https://html.spec.whatwg.org/multipage/rendering.html#img-contain-size
		add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

		// excerpt_more
		add_filter( 'excerpt_more', static function () {
			return ' ' . '&hellip;';
		} );

		// admin bar
		add_action( 'wp_before_admin_bar_render', static function () {
			if ( is_admin_bar_showing() ) {
				global $wp_admin_bar;

				$wp_admin_bar->remove_menu( 'wp-logo' );
				$wp_admin_bar->remove_menu( 'updates' );

				// Clear Cache
				$current_url = add_query_arg( 'clear_cache', 1, $_SERVER['REQUEST_URI'] );
				$wp_admin_bar->add_menu( [
					'id'    => 'clear_cache_button',
					'title' => '<div class="custom-admin-button"><span class="custom-icon">⚡</span><span class="custom-text">Clear cache</span></div>',
					'href'  => $current_url,
				] );
			}
		} );

		/** Clear Cache */
		add_action( 'init', static function () {
			if ( isset( $_GET['clear_cache'] ) ) {
				\HD_Helper::clearAllCache();
				set_transient( '_clear_cache_message', __( 'Cache has been successfully cleared.', TEXT_DOMAIN ), 30 );

				echo <<<HTML
                <script>
                    const currentUrl = window.location.href;
                    if (currentUrl.includes('clear_cache=1')) {
                        let newUrl = currentUrl.replace(/([?&])clear_cache=1/, '$1').replace(/&$/, '').replace(/\?$/, '');
                        currentUrl.includes('wp-admin')
                            ? window.location.replace(newUrl)
                            : window.history.replaceState({}, document.title, newUrl);
                    }
                </script>
            HTML;
			}
		} );

		// Normalize upload filename
		add_filter( 'sanitize_file_name', static function ( $filename ) {
			return remove_accents( $filename );
		}, 10, 1 );

		// Remove archive title prefix
		add_filter( 'get_the_archive_title_prefix', static function ( $prefix ) {
			return __return_empty_string();
		} );
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	private function _optimizer(): void {
		// Filters the script, style tag
		add_filter( 'script_loader_tag', [ $this, 'scriptLoaderTag' ], 12, 3 );
		add_filter( 'style_loader_tag', [ $this, 'styleLoaderTag' ], 12, 2 );

		// Adding Shortcode in WordPress Using Custom HTML Widget
		add_filter( 'widget_text', 'do_shortcode' );
		add_filter( 'widget_text', 'shortcode_unautop' );

		// Search by title
		add_filter( 'posts_search', [ $this, 'searchByTitle' ], 500, 2 );

		// Front-end only, excluding the login page
		if ( ! is_admin() && ! \HD_Helper::isLogin() ) {
			add_action( 'wp_print_footer_scripts', [ $this, 'printFooterScripts' ], 999 );
		}
	}

	// ------------------------------------------------------

	/**
	 * Launching operation cleanup
	 *
	 * @return void
	 */
	private function _cleanup(): void {
		// wp_head
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );

		// All actions related to emojis
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

		// Staticize emoji
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

		// Remove the wp-json header from WordPress.
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );

		// Remove id li navigation
		add_filter( 'nav_menu_item_id', '__return_null', 10, 3 );
	}

	// ------------------------------------------------------

	/**
	 * @param string $tag
	 * @param string $handle
	 * @param string $src
	 *
	 * @return string
	 */
	public function scriptLoaderTag( string $tag, string $handle, string $src ): string {
		$attributes = wp_scripts()->registered[ $handle ]->extra ?? [];

		if ( ! isset( wp_scripts()->registered[ $handle ] ) ) {
			return $tag;
		}

		// Add `type="module"` attributes if the script is marked as a module
		if ( ! empty( $attributes['module'] ) ) {
			$tag = preg_replace( '#(?=></script>)#', ' type="module"', $tag, 1 );
		}

		// Handle `async` and `defer` attributes
		foreach ( [ 'async', 'defer' ] as $attr ) {
			if ( 'defer' === $attr ) {
				$attr = 'defer data-wp-strategy="defer"';
			}

			if ( ! empty( $attributes[ $attr ] ) && ! preg_match( "#\s$attr(=|>|\s)#", $tag ) ) {
				$tag = preg_replace( '#(?=></script>)#', " $attr", $tag, 1 );
			}
		}

		// Process combined attributes (e.g., `module defer`) from `extra`
		if ( ! empty( $attributes['extra'] ) ) {
			// Convert space-separated string to array if necessary
			$extra_attrs = is_array( $attributes['extra'] )
				? $attributes['extra']
				: explode( ' ', $attributes['extra'] );

			foreach ( $extra_attrs as $attr ) {
				if ( 'defer' === $attr ) {
					$attr = 'defer data-wp-strategy="defer"';
				}

				if ( $attr === 'module' ) {
					if ( ! preg_match( '#\stype=(["\'])module\1#', $tag ) ) {
						$tag = preg_replace( '#(?=></script>)#', ' type="module"', $tag, 1 );
					}
				} elseif ( ! preg_match( "#\s$attr(=|>|\s)#", $tag ) ) {
					$tag = preg_replace( '#(?=></script>)#', " $attr", $tag, 1 );
				}
			}
		}

		// Fontawesome kit
		if ( ( 'fontawesome-kit' === $handle ) && ! preg_match( '#\scrossorigin([=>\s])#', $tag ) ) {
			$tag = preg_replace( '#(?=></script>)#', " crossorigin='anonymous'", $tag, 1 );
		}

		// Add script handles to the array
		$str_parsed = \HD_Helper::filterSettingOptions( 'defer_script', [] );

		return \HD_Helper::lazyScriptTag( $str_parsed, $tag, $handle );
	}

	// ------------------------------------------------------

	/**
	 * Add style handles to the array below
	 *
	 * @param string $html
	 * @param string $handle
	 *
	 * @return string
	 */
	public function styleLoaderTag( string $html, string $handle ): string {
		$styles = \HD_Helper::filterSettingOptions( 'defer_style', [] );

		return \HD_Helper::lazyStyleTag( $styles, $html, $handle );
	}

	// ------------------------------------------------------

	/**
	 * Search only in post-title or excerpt
	 *
	 * @param $search
	 * @param $wp_query
	 *
	 * @return mixed|string
	 */
	public function searchByTitle( $search, $wp_query ): mixed {
		global $wpdb;

		if ( empty( $search ) ) {
			return $search;
		}

		if ( ! extension_loaded( 'mbstring' ) ) {
			return $search;
		}

		$q = $wp_query->query_vars;
		$n = ! empty( $q['exact'] ) ? '' : '%';

		$search = $search_and = '';

		foreach ( (array) $q['search_terms'] as $term ) {
			$term = mb_strtolower( esc_sql( $wpdb->esc_like( $term ) ) );

			$like       = "LIKE CONCAT('{$n}', CONVERT('{$term}', BINARY), '{$n}')";
			$search     .= "{$search_and}(LOWER($wpdb->posts.post_title) {$like} OR LOWER($wpdb->posts.post_excerpt) {$like})";
			$search_and = ' AND ';
		}

		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( ! is_user_logged_in() ) {
				$search .= " AND ($wpdb->posts.post_password = '') ";
			}
		}

		return $search;
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function printFooterScripts(): void {
		echo '<script>document.documentElement.classList.remove(\'no-js\');</script>';
	}
}
