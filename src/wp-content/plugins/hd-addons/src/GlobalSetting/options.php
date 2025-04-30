<?php

\defined( 'ABSPATH' ) || exit;

$menu_options           = \Addons\Helper::loadYaml( ADDONS_PATH . 'config.yaml' );
$global_setting_options = \Addons\Helper::getOption( 'global_setting__options' );

$current_slug =	$current_slug ?? 'global_setting';

?>
<div class="container flex flex-x flex-gap sm-up-2 md-up-3 lg-up-4">
	<?php
	foreach ( $menu_options as $slug => $value ) :
		$title       = ! empty( $value['title'] ) ? __( $value['title'], ADDONS_TEXTDOMAIN ) : '';
		$description = ! empty( $value['description'] ) ? __( $value['description'], ADDONS_TEXTDOMAIN ) : '';

		if ( $slug === $current_slug ) {
			continue;
		}

        // WooCommerce
        if ( (string) $slug === 'woocommerce' && ! \Addons\Helper::checkPluginActive( 'woocommerce/woocommerce.php' ) ) {
            continue;
        }
    ?>
    <div class="section section-checkbox cell" id="section_<?= $slug ?>">
        <label class="heading" for="<?= $slug ?>"><?php echo $title; ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="<?= $slug ?>" id="<?= $slug ?>" <?php checked( $global_setting_options[$slug] ?? false, 1 ); ?> value="1">
            </div>
            <div class="explain"><?php _e( 'Check to activate', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
        <div class="desc !mt-15"><?php echo $description; ?></div>
    </div>
	<?php endforeach; ?>
</div>
