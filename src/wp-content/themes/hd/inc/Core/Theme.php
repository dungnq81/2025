<?php

declare( strict_types=1 );

namespace HD\Core;

use HD\Core\Admin\Admin;
use HD\Core\Frontend\Ajax;
use HD\Core\Frontend\Hook;
use HD\Core\Frontend\Shortcode;
use HD\Integration\ACF\ACF;
use HD\Integration\WooCommerce\WooCommerce;
use HD\Utilities\Traits\Singleton;

\defined( 'ABSPATH' ) || die;

/**
 * Theme Class
 *
 * @author Gaudev
 */
final class Theme {
	use Singleton;

	// --------------------------------------------------

	private function init(): void {
		// wp-config.php -> muplugins_loaded -> plugins_loaded -> after_setup_theme -> init (rest_api_init, widgets_init, v.v...)
		// FE: init -> wp_loaded -> wp -> template_redirect -> template_include -> v.v...
		// BE: init -> wp_loaded -> admin_menu -> admin_init -> v.v...

		add_action( 'after_setup_theme', [ $this, 'setup_theme' ], 10 );
		add_action( 'after_setup_theme', [ $this, 'setup' ], 11 );

		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 10 );

		/** Widgets */
		add_action( 'widgets_init', [ $this, 'unregister_widgets' ], 11 );
		add_action( 'widgets_init', [ $this, 'register_widgets' ], 11 );

		/** Dynamic Template Hook */
		add_filter( 'template_include', [ $this, 'dynamic_template_include' ], 20 );
	}

	// --------------------------------------------------

	/**
	 * Sets up theme defaults and register support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which runs before the init hook.
	 * The init hook is too late for some features, such as indicating support for post-thumbnails.
	 */
	public function setup_theme(): void {
		load_theme_textdomain( TEXT_DOMAIN, get_template_directory() . '/languages' );

		/** Add theme support for various features. */
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script' ] );

		add_theme_support( 'align-wide' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );

		/** Enable excerpt to page, page-attributes to post */
		add_post_type_support( 'page', [ 'excerpt' ] );
		add_post_type_support( 'post', [ 'page-attributes' ] );

		/** Set default values for the upload media box */
		update_option( 'image_default_align', 'center' );
		update_option( 'image_default_size', 'large' );

		/** Add support for the core custom logo. */
		add_theme_support(
			'custom-logo',
			[
				'height'               => 240,
				'width'                => 240,
				'flex-height'          => true,
				'flex-width'           => true,
				'header-text'          => '',
				'unlink-homepage-logo' => true,
			]
		);
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function setup(): void {
		if ( \HD_Helper::isAdmin() ) {
			( Admin::get_instance() );
		}

		( Customizer::get_instance() );
		( Optimizer::get_instance() );
		( Shortcode::get_instance() );
		( Hook::get_instance() );
		( Ajax::get_instance() );

		// Integration
		\HD_Helper::isAcfActive() && ACF::get_instance();
		\HD_Helper::isWoocommerceActive() && WooCommerce::get_instance();
	}

	// --------------------------------------------------

	/**
	 * Enqueue scripts and styles
	 *
	 * @return void
	 */
	public function wp_enqueue_scripts(): void {
		$version = \HD_Helper::version();

		/** Inline Js */
		$recaptcha_options = \HD_Helper::getOption( 'recaptcha__options' );
		$l10n              = [
			'_ajaxUrl'      => admin_url( 'admin-ajax.php', 'relative' ),
			'_baseUrl'      => \HD_Helper::siteURL( '/' ),
			'_themeUrl'     => THEME_URL,
			'_restApiUrl'   => RESTAPI_URL,
			'_csrfToken'    => wp_create_nonce( 'wp_csrf_token' ),
			'_restToken'    => wp_create_nonce( 'wp_rest' ),
			'_reCaptcha_v2' => $recaptcha_options['recaptcha_v2_site_key'] ?? '',
			'_reCaptcha_v3' => $recaptcha_options['recaptcha_v3_site_key'] ?? '',
			'_lang'         => \HD_Helper::currentLanguage(),
		];
		\HD_Asset::localize( 'jquery-core', 'hdConfig', $l10n );
		\HD_Asset::inlineScript( 'jquery-core', 'Object.assign(window,{ $:jQuery,jQuery });', 'after' );

		/** CSS */
		\HD_Asset::enqueueStyle( 'vendor-css', ASSETS_URL . 'css/_vendor.css', [], $version );
		\HD_Asset::enqueueStyle( 'index-css', ASSETS_URL . 'css/index.css', [ 'vendor-css' ], $version );

		/** JS */
		\HD_Asset::enqueueScript( 'preload-js', ASSETS_URL . 'js/components/preload-polyfill.js', [], $version, false, [ 'module', 'async' ] );
		\HD_Asset::enqueueScript( 'index-js', ASSETS_URL . 'js/index.js', [ 'jquery-core' ], $version, true, [ 'module', 'defer' ] );

		/** Comments */
		if ( is_singular() && comments_open() && \HD_Helper::getOption( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		} else {
			wp_dequeue_script( 'comment-reply' );
		}
	}

	// --------------------------------------------------

	/**
	 * @param $template
	 *
	 * @return mixed
	 */
	public function dynamic_template_include( $template ): mixed {
		static $enqueued_hooks = [];

		$template_slug = basename( $template, '.php' );
		$hook_name     = 'enqueue_assets_' . str_replace( '-', '_', $template_slug );

		if ( ! in_array( $hook_name, $enqueued_hooks, true ) ) {
			// dynamic hook - enqueue style/script
			add_action( 'wp_enqueue_scripts', static function () use ( $hook_name ) {
				do_action( $hook_name );
				do_action( 'enqueue_assets_extra' ); // dynamic hook extra
			}, 20 );

			$enqueued_hooks[] = $hook_name;
		}

		return $template;
	}

	// --------------------------------------------------

	/**
	 * Unregister widgets
	 *
	 * @return void
	 */
	public function unregister_widgets(): void {
		unregister_widget( 'WP_Widget_Search' );
		unregister_widget( 'WP_Widget_Recent_Posts' );

		// Removes the styling added to the header for recent comments
		global $wp_widget_factory;
		remove_action( 'wp_head', [
			$wp_widget_factory->widgets['WP_Widget_Recent_Comments'],
			'recent_comments_style'
		] );
	}

	// --------------------------------------------------

	/**
	 * Registers widgets
	 *
	 * @return void
	 */
	public function register_widgets(): void {
		$widgets_dir = INC_PATH . 'Utilities/Widgets';
		$FQN         = '\\HD\\Utilities\\Widgets\\';

		\HD_Helper::createDirectory( $widgets_dir );
		\HD_Helper::FQNLoad( $widgets_dir, false, true, $FQN, true );
	}

	// --------------------------------------------------
}
