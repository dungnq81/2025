<?php

namespace Addons\Optimizer\LazyLoad;

use Detection\Exception\MobileDetectException;

\defined( 'ABSPATH' ) || exit;

final class LazyLoad {

	public LazyLoad_Iframes $lazyload_iframes;
	public LazyLoad_Videos $lazyload_videos;
	public LazyLoad_Images $lazyload_images;

	// -------------------------------------------------------------

	/**
	 * @var array|array[]
	 */
	public array $lazyload_hooks = [
		'lazyload_iframes' => [
			'the_content',
			'widget_text',
		],
		'lazyload_videos'  => [
			'the_content',
			'widget_text',
		],
		'lazyload_images'  => [
			'the_content',
			'widget_text',
			'widget_block_content',
			'wp_get_attachment_image',
			'post_thumbnail_html',
			'get_avatar',
			'woocommerce_product_get_image',
			'woocommerce_single_product_image_thumbnail_html',
			'hd_picture_html_filter',
			'hd_icon_image_html_filter',
			'hd_attachment_image_html_filter',
			'hd_post_image_html_filter',
		],
	];

	// -------------------------------------------------------------

	/**
	 * @throws MobileDetectException
	 */
	public function __construct() {
		$optimizer_options = \Addons\Helper::getOption( 'optimizer__options' );
		$lazyload         = $optimizer_options['lazyload'] ?? 0;
		$lazyload_mobile  = $optimizer_options['lazyload_mobile'] ?? 0;

		if ( empty( $lazyload ) ) {
			return;
		}

		// Bail if the current browser runs on a mobile device and the lazy-load on mobile is deactivated.
		if ( ! $lazyload_mobile && \Addons\Helper::isMobile() ) {
			return;
		}

		// Disable the native lazy-loading.
		// add_filter( 'wp_lazy_loading_enabled', '__return_false' );

		$this->lazyload_iframes = new LazyLoad_Iframes();
		$this->lazyload_videos  = new LazyLoad_Videos();
		$this->lazyload_images  = new LazyLoad_Images();

		$this->_add_lazy_load_hooks();
	}

	// -------------------------------------------------------------

	/**
	 * @return void
	 */
	private function _add_lazy_load_hooks(): void {
		foreach ( $this->lazyload_hooks as $name => $attributes ) {
			// Loop through all attributes.
			foreach ( $attributes as $hook ) {
				// Add the hooks.
				add_filter( $hook, [ $this->{$name}, 'filter_html' ], 999999 );
			}
		}

		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );
	}

	// -------------------------------------------------------------

	/**
	 * @return void
	 */
	public function load_scripts(): void {
		wp_enqueue_script( 'lazyload-js', ADDONS_URL . 'assets/js/lazyload.js', [], \Addons\Helper::version(), true );
		wp_script_add_data( 'lazyload-js', 'addon', [ 'module', 'async' ] );
	}
}
