<?php

declare( strict_types=1 );

\defined( 'ABSPATH' ) || die;

//-----------------------------------------------------------------
// Custom functions
//-----------------------------------------------------------------

/**
 * @see woocommerce_product_taxonomy_archive_footer
 */
add_action( 'woocommerce_shop_loop_footer', 'woocommerce_product_taxonomy_archive_footer' );

function woocommerce_product_taxonomy_archive_footer(): void {
	wc_get_template( 'loop/footer.php' );
}

//-----------------------------------------------------------------

/**
 * @see add_buy_now_button
 */
add_action( 'woocommerce_after_add_to_cart_button', 'add_buy_now_button' );

function add_buy_now_button(): void {
	global $product;

	if ( $product->is_type( 'simple' ) ) {
		?>
        <button type="submit" name="buy_now" class="button buy-now-button">
			<?php echo __( 'Mua ngay', TEXT_DOMAIN ); ?>
        </button>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let form = document.querySelector('form.cart');
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'buy_now_product_id';
                input.value = '<?= esc_attr( $product->get_id() ) ?>';
                form.appendChild(input);
            });
        </script>
		<?php
	}
}

//-----------------------------------------------------------------

/**
 * @see handle_buy_now_redirect
 */
add_action( 'template_redirect', 'handle_buy_now_redirect' );

/**
 * @throws Exception
 */
function handle_buy_now_redirect(): void {
	if ( isset( $_POST['buy_now_product_id'] ) && is_numeric( $_POST['buy_now_product_id'] ) ) {
		$product_id = (int) $_POST['buy_now_product_id'];

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product_id );
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}
}

