<?php

\defined( 'ABSPATH' ) || die;

$order_link = function_exists( 'wc_get_account_endpoint_url' ) ? esc_url( wc_get_account_endpoint_url( 'orders' ) ) : '';
if ( ! $order_link ) {
    return;
}

?>
<a rel="nofollow" class="order-history-link !lg:show" href="<?= $order_link ?>" data-fa=""
   title="<?= esc_attr__( 'Lịch sử đơn hàng', TEXT_DOMAIN ) ?>"><?= __( 'Lịch sử đơn hàng', TEXT_DOMAIN ) ?></a>
