<?php

\defined( 'ABSPATH' ) || die;

$account_link = function_exists( 'wc_get_page_permalink' ) ? esc_url( wc_get_page_permalink( 'myaccount' ) ) : '';
if ( ! $account_link ) {
	return;
}

?>
<div class="account-item not-logged-in">
	<a rel="nofollow" class="account-btn-link" href="<?= $account_link ?>" data-open="#login-form-popup" data-fa="" title="<?= esc_attr__( 'Tài khoản', TEXT_DOMAIN ) ?>"></a>
	<ul class="hidden">
		<li><a href="#">A</a></li>
		<li><a href="#">B</a></li>
	</ul>
</div>
