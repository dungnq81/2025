<?php

declare( strict_types=1 );

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

	/* ---------- CONSTRUCT ----------------------------------------------- */

	private function init(): void {
		//-----------------------------------------------------------------
		// Setup
		//-----------------------------------------------------------------

		add_action( 'widgets_init', [ $this, 'unregisterDefaultWidgets' ], 33 );
		add_action( 'widgets_init', [ $this, 'registerWidgets' ], 34 );

		add_action( 'after_setup_theme', [ $this, 'afterSetupTheme' ], 33 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueAssets' ], 98 );

		add_filter( 'wp_theme_json_data_theme', [ $this, 'jsonDataTheme' ] );

		//-----------------------------------------------------------------
		// Custom Hooks
		//-----------------------------------------------------------------

		// Remove header from the WooCommerce administrator panel
		add_action( 'admin_head', static function () {
			echo '<style>#wpadminbar ~ #wpbody { margin-top: 0 !important; }.woocommerce-layout__header { display: none !important; }</style>';
		} );

		add_filter( 'woocommerce_defer_transactional_emails', '__return_true' );
		add_filter( 'woocommerce_product_get_rating_html', [ $this, 'getRatingHtml' ], 10, 3 );

		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'cartFragment' ], 11, 1 );

		add_filter( 'woocommerce_product_description_heading', '__return_empty_string' );
		add_filter( 'woocommerce_product_additional_information_heading', '__return_empty_string' );

		// woocommerce_before_shop_loop
		add_action( 'woocommerce_before_shop_loop', static function () {
			echo '<div class="woocommerce-shop-info">';
		}, 19 );

		add_action( 'woocommerce_before_shop_loop', static function () {
			echo '</div>';
		}, 31 );

		// woocommerce_before_shop_loop_item_title
		add_action( 'woocommerce_before_shop_loop_item_title', static function () {
			echo '<span class="thumb wc-thumb">';
		}, 9 );

		add_action( 'woocommerce_before_shop_loop_item_title', static function () {
			echo '</span>';
		}, 11 );

		// woocommerce_single_product_summary
		add_action( 'woocommerce_single_product_summary', [ $this, 'acfCustomMeta' ], 39 );

        // woocommerce_after_shop_loop_item
        remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
        //add_action( 'woocommerce_after_shop_loop_item', [ $this, 'addProductLink' ], 12 );

		// woocommerce_before_main_content
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	}

	/* ---------- PUBLIC -------------------------------------------------- */

	/**
	 * @param $theme_json
	 *
	 * @return mixed
	 */
	public function jsonDataTheme( $theme_json ): mixed {
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

	/**
	 * Registers a WP_Widget widget
	 *
	 * @return void
	 */
	public function registerWidgets(): void {
		$widgets_dir = INC_PATH . 'Integration/WooCommerce/Widgets';
		$FQN         = '\\HD\\Integration\\WooCommerce\\Widgets\\';

		\HD_Helper::createDirectory( $widgets_dir );
		\HD_Helper::FQNLoad( $widgets_dir, false, true, $FQN, true );
	}

	/**
	 * Unregister a WP_Widget widget
	 *
	 * @return void
	 */
	public function unregisterDefaultWidgets(): void {
		unregister_widget( 'WC_Widget_Product_Search' );
		unregister_widget( 'WC_Widget_Products' );
	}

	/**
	 * @return void
	 */
	public function enqueueAssets(): void {
		$version = \HD_Helper::version();
		\HD_Asset::enqueueStyle( 'hdwc-css', ASSETS_URL . 'css/components/woocommerce.css', [ 'index-css' ], $version );
		\HD_Asset::enqueueScript( 'hdwc-js', ASSETS_URL . 'js/components/woocommerce.js', [ 'jquery-core', 'index-js' ], $version, true, [
			'module',
			'defer'
		] );
	}

	/**
	 * @return void
	 */
	public function afterSetupTheme(): void {
		// Add support for WC features.
		//add_theme_support( 'wc-product-gallery-zoom' );
		//add_theme_support( 'wc-product-gallery-lightbox' );
		//add_theme_support( 'wc-product-gallery-slider' );

		add_theme_support( 'woocommerce' );
	}

	/**
	 * Cart Fragments, ensure cart contents update when products are added to the cart via AJAX
	 *
	 * @param array $fragments Fragments to refresh via AJAX.
	 *
	 * @return array            Fragments to refresh via AJAX
	 */
	public function cartFragment( array $fragments ): array {
		ob_start();
		echo '<span class="cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';
		$fragments['.cart-count'] = ob_get_clean();

		ob_start(); ?>
        <div class="mini-cart-dropdown">
			<?php woocommerce_mini_cart(); ?>
        </div>
		<?php
		$fragments['div.mini-cart-dropdown'] = ob_get_clean();

		return $fragments;
	}

	/**
	 * @param $html
	 * @param $rating
	 * @param $count
	 *
	 * @return string
	 */
	public function getRatingHtml( $html, $rating, $count ): string {
		$return = '';

		if ( 0 < $rating ) {
			$label = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating );

			$return .= '<div class="loop-stars-rating" role="img" aria-label="' . esc_attr( $label ) . '">';
			$return .= \wc_get_star_rating_html( $rating, $count );
			$return .= '</div>';
		}

		return $return;
	}

	/**
	 * @return void
	 */
	public function acfCustomMeta(): void {
		if ( ! isset( $GLOBALS['post'] ) ) {
			return;
		}

		global $post;
		$ACF              = \HD_Helper::getFields( $post->ID );
		$shipping_returns = $ACF['shipping-returns'] ?? '';
		$product_care     = $ACF['product-care'] ?? '';

		if ( empty( $shipping_returns ) && empty( $product_care ) ) {
			return;
		}
		?>
        <ul class="accordion" data-accordion data-allow-all-closed="true">
            <?php if ( ! empty( $shipping_returns ) ) : ?>
            <li class="accordion-item" data-accordion-item>
                <a href="#" class="accordion-title"><?php echo __( 'Vận chuyển & Đổi trả', TEXT_DOMAIN ); ?></a>
                <div class="accordion-content" data-tab-content>
                    <?= $shipping_returns ?>
                </div>
            </li>
            <?php endif; ?>
            <?php if ( ! empty( $product_care ) ) : ?>
            <li class="accordion-item" data-accordion-item>
                <a href="#" class="accordion-title"><?php echo __( 'Chăm sóc sản phẩm', TEXT_DOMAIN ); ?></a>
                <div class="accordion-content" data-tab-content>
                    <?= $product_care ?>
                </div>
            </li>
            <?php endif; ?>
        </ul>
		<?php
	}

	/**
	 * @return void
	 */
    public function addProductLink(): void {
	    global $product;

	    if ( ! ( $product instanceof \WC_Product ) ) {
		    return;
	    }

	    $link = apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );
        if ( ! empty( $link ) ) {
	        echo '<a title="' . esc_attr__( 'Xem chi tiết', TEXT_DOMAIN ) . '" href="' . esc_url( $link ) . '" class="view-more">' . __( 'Chi tiết sản phẩm', TEXT_DOMAIN ) . '</a>';
        }
    }
}
