<?php

\defined( 'ABSPATH' ) || exit;

$woocommerce_options     = \Addons\Helper::getOption( 'woocommerce__options' );
$woocommerce_jsonld      = $woocommerce_options['woocommerce_jsonld'] ?? '';
$woocommerce_default_css = $woocommerce_options['woocommerce_default_css'] ?? '';

?>
<div class="container flex flex-x flex-gap sm-up-1 md-up-2">
    <input type="hidden" name="woocommerce-hidden" value="1">
	<div class="cell section section-checkbox">
		<label class="heading" for="woocommerce_jsonld"><?php _e( 'WooCommerce 3 JSON/LD', ADDONS_TEXTDOMAIN ); ?></label>
		<div class="desc"><?php _e( 'Remove the default WooCommerce 3 JSON/LD structured data format', ADDONS_TEXTDOMAIN ) ?></div>
		<div class="option">
			<div class="controls">
				<input type="checkbox" class="checkbox" name="woocommerce_jsonld" id="woocommerce_jsonld" <?php checked( $woocommerce_jsonld, 1 ); ?> value="1">
			</div>
			<div class="explain"><?php _e( 'Remove WooCommerce JSON/LD', ADDONS_TEXTDOMAIN ); ?></div>
		</div>
	</div>

	<div class="cell section section-checkbox">
		<label class="heading" for="woocommerce_default_css"><?php _e( 'Remove WooCommerce CSS', ADDONS_TEXTDOMAIN ); ?></label>
		<div class="desc">Remove all default CSS of WooCommerce.</div>
		<div class="option">
			<div class="controls">
				<input type="checkbox" class="checkbox" name="woocommerce_default_css" id="woocommerce_default_css" <?php checked( $woocommerce_default_css, 1 ); ?> value="1">
			</div>
			<div class="explain"><?php _e( 'Remove all', ADDONS_TEXTDOMAIN ); ?></div>
		</div>
	</div>
</div>
