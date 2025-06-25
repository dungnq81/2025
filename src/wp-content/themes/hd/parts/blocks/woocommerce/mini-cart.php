<?php
// Display Mini-Cart

\defined( 'ABSPATH' ) || die;

if ( ! \HD_Helper::isWoocommerceActive() ) {
	return;
}

?>
<div class="wc-mini-cart">
    <a rel="nofollow" class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php echo esc_attr__( 'View your shopping cart', TEXT_DOMAIN ); ?>">
        <span class="cart-icon" data-fa=""></span>
        <span class="cart-count">
			<?php echo WC()->cart->get_cart_contents_count(); ?>
		</span>
    </a>
    <div class="mini-cart-dropdown">
		<?php woocommerce_mini_cart(); ?>
    </div>
</div>
