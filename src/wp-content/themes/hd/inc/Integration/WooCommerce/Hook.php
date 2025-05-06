<?php

namespace HD\Integration\WooCommerce;

\defined( 'ABSPATH' ) || die;

/**
 * WooCommerce Custom Hook
 *
 * @author Gaudev
 */
class Hook {
	public function __construct() {

		// https://stackoverflow.com/questions/57321805/remove-header-from-the-woocommerce-administrator-panel
		add_action( 'admin_head', static function () {
			echo '<style>#wpadminbar ~ #wpbody { margin-top: 0 !important; }.woocommerce-layout__header { display: none !important; }</style>';
		} );

		add_filter( 'woocommerce_product_get_rating_html', [ $this, '_hook_woocommerce_product_get_rating_html' ], 10, 3 );
		add_filter( 'woocommerce_defer_transactional_emails', '__return_true' );
	}

	// ------------------------------------------------------
	// ------------------------------------------------------

	/**
	 * @param $html
	 * @param $rating
	 * @param $count
	 *
	 * @return string
	 */
	public function _hook_woocommerce_product_get_rating_html( $html, $rating, $count ): string {
		$return = '';

		if ( 0 < $rating ) {
			$label = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating );

			$return .= '<div class="loop-stars-rating" role="img" aria-label="' . esc_attr( $label ) . '">';
			$return .= \wc_get_star_rating_html( $rating, $count );
			$return .= '</div>';
		}

		return $return;
	}
}
