<?php

declare( strict_types=1 );

namespace HD\Integration\WooCommerce;

\defined( 'ABSPATH' ) || die;

/**
 * WOO_Helper Class
 *
 * @author Gaudev
 */
final class WOO_Helper {
	// -------------------------------------------------------------

	/**
	 * @param $product
	 *
	 * @return float|string
	 */
	public static function wc_sale_flash_percent( $product ): float|string {
		global $product;

		$percent_off = '';
		if ( $product->is_on_sale() ) {
			if ( $product->is_type( 'variable' ) && $product->get_variation_regular_price( 'min' ) ) {
				$percent_off = ceil( 100 - ( $product->get_variation_sale_price() / $product->get_variation_regular_price( 'min' ) ) * 100 );
			} elseif ( $product->get_regular_price() && ! $product->is_type( 'grouped' ) ) {
				$percent_off = ceil( 100 - ( $product->get_sale_price() / $product->get_regular_price() ) * 100 );
			}
		}

		return $percent_off;
	}
}
