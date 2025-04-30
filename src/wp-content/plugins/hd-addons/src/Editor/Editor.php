<?php

namespace Addons\Editor;

\defined( 'ABSPATH' ) || exit;

final class Editor {
	public mixed $editor_options = [];

	// ------------------------------------------------------

	public function __construct() {
		$this->editor_options = \Addons\Helper::getOption( 'editor__options' );

		( new TinyMCE() );

		add_action( 'admin_init', [ $this, 'editor_admin_init' ], 11 );
		add_action( 'wp_enqueue_scripts', [ $this, 'editor_enqueue_scripts' ], 98 );
	}

	// ------------------------------------------------------

	public function editor_admin_init(): void {

		$use_widgets_block_editor_off           = $this->editor_options['use_widgets_block_editor_off'] ?? '';
		$gutenberg_use_widgets_block_editor_off = $this->editor_options['gutenberg_use_widgets_block_editor_off'] ?? '';
		$use_block_editor_for_post_type_off     = $this->editor_options['use_block_editor_for_post_type_off'] ?? '';

		// Disables the block editor from managing widgets.
		if ( $use_widgets_block_editor_off ) {
			add_filter( 'use_widgets_block_editor', '__return_false', 100 );
		}

		// Disables the block editor from managing widgets in the Gutenberg plugin.
		if ( $gutenberg_use_widgets_block_editor_off ) {
			add_filter( 'gutenberg_use_widgets_block_editor', '__return_false', 100 );
		}

		// Use Classic Editor - Disable Gutenberg Editor
		if ( $use_block_editor_for_post_type_off ) {
			$gutenberg = \function_exists( 'gutenberg_register_scripts_and_styles' );

			// Fix for Safari 18 negative horizontal margin on floats. - Classic Editor plugin
			add_action( 'admin_print_styles', [ $this, 'safari_18_temp_fix' ] );

			// Fix for the Categories `post-box` on the classic Edit Post screen for WP 6.7.1.

			global $wp_version;

			if ( $wp_version === '6.7.1' && is_admin() ) {
				add_filter( 'script_loader_src', [ $this, 'replace_post_js' ], 11, 2 );
			}

			// Also used in Gutenberg.
			// Consider disabling other Block Editor functionality.
			add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );

			if ( $gutenberg ) {
				// Support older Gutenberg versions.
				add_filter( 'gutenberg_can_edit_post_type', '__return_false', 100 );
				$this->_remove_gutenberg_hooks();
			}
		}
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function editor_enqueue_scripts(): void {
		wp_dequeue_style( 'classic-theme-styles' );

		/** Remove block CSS */
		if ( $this->editor_options['block_style_off'] ?? '' ) {
			wp_dequeue_style( 'global-styles' );
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );

			add_action( 'wp_footer', static function () {
				wp_dequeue_style( 'core-block-supports' );
			}, 10 );
		}
	}

	// ------------------------------------------------------

	/**
	 * Temporary fix for Safari 18 negative horizontal margin on floats.
	 * See: https://core.trac.wordpress.org/ticket/62082 and
	 * https://bugs.webkit.org/show_bug.cgi?id=280063.
	 * TODO: Remove when Safari is fixed.
	 */
	public function safari_18_temp_fix(): void {
		global $current_screen;

		if ( isset( $current_screen->base ) && $current_screen->base === 'post' ) {
			$clear = is_rtl() ? 'right' : 'left';

			?>
            <style id="classic-editor-safari-18-temp-fix">
                _::-webkit-full-page-media, _:future, :root #post-body #postbox-container-2 {
                    clear: <?php echo $clear; ?>;
                }
            </style>
			<?php
		}
	}

	// ------------------------------------------------------

	/**
	 * Fix for the Categories post-box on the classic Edit Post screen for WP 6.7.1.
	 * See: https://core.trac.wordpress.org/ticket/62504 and
	 * https://github.com/WordPress/classic-editor/issues/222.
	 *
	 * @param mixed $src
	 * @param mixed $handle
	 *
	 * @return mixed|string
	 */
	public static function replace_post_js( mixed $src, mixed $handle ): mixed {
		if ( $handle === 'post' && is_string( $src ) && ! str_contains( $src, 'ver=62504-20241121' ) ) {
			$src = ADDONS_URL . 'src/Editor/js/post.min.js';
			$src = add_query_arg( 'ver', '62504-20241121', $src );
		}

		return $src;
	}

	// ------------------------------------------------------

	/**
	 * @param string|null $remove
	 *
	 * @return void
	 */
	private function _remove_gutenberg_hooks( ?string $remove = 'all' ): void {
		if ( $remove !== 'all' ) {
			return;
		}

		// Gutenberg 5.3+
		remove_action( 'wp_enqueue_scripts', 'gutenberg_register_scripts_and_styles' );
		remove_action( 'admin_enqueue_scripts', 'gutenberg_register_scripts_and_styles' );
		remove_action( 'admin_notices', 'gutenberg_wordpress_version_notice' );
		remove_action( 'rest_api_init', 'gutenberg_register_rest_widget_updater_routes' );
		remove_action( 'admin_print_styles', 'gutenberg_block_editor_admin_print_styles' );
		remove_action( 'admin_print_scripts', 'gutenberg_block_editor_admin_print_scripts' );
		remove_action( 'admin_print_footer_scripts', 'gutenberg_block_editor_admin_print_footer_scripts' );
		remove_action( 'admin_footer', 'gutenberg_block_editor_admin_footer' );
		remove_action( 'admin_enqueue_scripts', 'gutenberg_widgets_init' );
		remove_action( 'admin_notices', 'gutenberg_build_files_notice' );

		remove_filter( 'load_script_translation_file', 'gutenberg_override_translation_file' );
		remove_filter( 'block_editor_settings', 'gutenberg_extend_block_editor_styles' );
		remove_filter( 'default_content', 'gutenberg_default_demo_content' );
		remove_filter( 'default_title', 'gutenberg_default_demo_title' );
		remove_filter( 'block_editor_settings', 'gutenberg_legacy_widget_settings' );
		remove_filter( 'rest_request_after_callbacks', 'gutenberg_filter_oembed_result' );

		// Previously used, compat for older Gutenberg versions.
		remove_filter( 'wp_refresh_nonces', 'gutenberg_add_rest_nonce_to_heartbeat_response_headers' );
		remove_filter( 'get_edit_post_link', 'gutenberg_revisions_link_to_editor' );
		remove_filter( 'wp_prepare_revision_for_js', 'gutenberg_revisions_restore' );

		remove_action( 'rest_api_init', 'gutenberg_register_rest_routes' );
		remove_action( 'rest_api_init', 'gutenberg_add_taxonomy_visibility_field' );
		remove_filter( 'registered_post_type', 'gutenberg_register_post_prepare_functions' );

		remove_action( 'do_meta_boxes', 'gutenberg_meta_box_save' );
		remove_action( 'submitpost_box', 'gutenberg_intercept_meta_box_render' );
		remove_action( 'submitpage_box', 'gutenberg_intercept_meta_box_render' );
		remove_action( 'edit_page_form', 'gutenberg_intercept_meta_box_render' );
		remove_action( 'edit_form_advanced', 'gutenberg_intercept_meta_box_render' );
		remove_filter( 'redirect_post_location', 'gutenberg_meta_box_save_redirect' );
		remove_filter( 'filter_gutenberg_meta_boxes', 'gutenberg_filter_meta_boxes' );

		remove_filter( 'body_class', 'gutenberg_add_responsive_body_class' );
		remove_filter( 'admin_url', 'gutenberg_modify_add_new_button_url' );
		remove_action( 'admin_enqueue_scripts', 'gutenberg_check_if_classic_needs_warning_about_blocks' );
		remove_filter( 'register_post_type_args', 'gutenberg_filter_post_type_labels' );
	}
}
