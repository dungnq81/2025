<?php

namespace HD\Integration\WooCommerce;

use HD\Utilities\Traits\Singleton;

\defined( 'ABSPATH' ) || die;

require __DIR__ . '/functions.php';

/**
 * WooCommerce Plugin
 *
 * @author   Gaudev
 */
final class WooCommerce {
	use Singleton;

	// ------------------------------------------------------

	private function init(): void {
		add_action( 'widgets_init', [ $this, 'unregister_default_widgets' ], 33 );
		add_action( 'widgets_init', [ $this, 'register_widgets' ], 34 );

		add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 33 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 98 );

		add_filter( 'wp_theme_json_data_theme', [ $this, 'wp_theme_json_data_theme' ] );

		// Custom hooks
		( new Hook() );
	}

	// ------------------------------------------------------

	/**
	 * @param $theme_json
	 *
	 * @return mixed
	 */
	public function wp_theme_json_data_theme( $theme_json ): mixed {
		$new_data = [
			'version'  => 1,
			'settings' => [
				'typography' => [
					'fontFamilies' => [
						'theme' => [],
					],
				],
			],
		];

		$theme_json->update_with( $new_data );

		return $theme_json;
	}

	// ------------------------------------------------------

	/**
	 * Registers a WP_Widget widget
	 *
	 * @return void
	 */
	public function register_widgets(): void {
		$widgets_dir = INC_PATH . 'classes/Plugins/WooCommerce/Widgets';
		$FQN         = '\\HD\\Plugins\\WooCommerce\\Widgets\\';

		\HD_Helper::createDirectory( $widgets_dir );
		\HD_Helper::FQNLoad( $widgets_dir, false, true, $FQN, true );
	}

	// ------------------------------------------------------

	/**
	 * Unregister a WP_Widget widget
	 *
	 * @return void
	 */
	public function unregister_default_widgets(): void {
		unregister_widget( 'WC_Widget_Product_Search' );
		//unregister_widget( 'WC_Widget_Products' );
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_style( 'hdwc-css', ASSETS_URL . 'css/woocommerce-css.css', [ 'index-css' ], THEME_VERSION );
		wp_enqueue_script( 'hdwc', ASSETS_URL . 'js/woocommerce.js', [ 'index' ], THEME_VERSION, true );
		wp_script_add_data( 'hdwc', 'extra', [ 'module', 'defer' ] );
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function after_setup_theme(): void {
		add_theme_support( 'woocommerce' );

		// Add support for WC features.
		//add_theme_support( 'wc-product-gallery-zoom' );
		//add_theme_support( 'wc-product-gallery-lightbox' );
		//add_theme_support( 'wc-product-gallery-slider' );

		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	}
}
