<?php

\defined( 'ABSPATH' ) || exit;

?>
<div id="_nav" class="tabs-nav">
    <div class="logo-title">
        <h3>
			<?php _e( 'Addons Settings', ADDONS_TEXTDOMAIN ); ?>
            <span>Version: <?php echo ADDONS_VERSION; ?></span>
        </h3>
    </div>

    <div class="save-bar">
        <button type="submit" name="_submit_settings" class="button button-primary"><?php _e( 'Save Changes', ADDONS_TEXTDOMAIN ); ?></button>
    </div>

    <ul class="ul-menu-list">
		<?php

		$menu_options           = \Addons\Helper::loadYaml( ADDONS_PATH . 'config.yaml' );
		$global_setting_options = \Addons\Helper::getOption( 'global_setting__options' );

		$i = 0;

		foreach ( $menu_options as $slug => $value ) {
			//$current = ( $i === 0 ) ? ' class="current"' : '';
			$current = '';
			$title   = ! empty( $value['title'] ) ? __( $value['title'], ADDONS_TEXTDOMAIN ) : '';

			// WooCommerce
			if ( (string) $slug === 'woocommerce' && ! \Addons\Helper::checkPluginActive( 'woocommerce/woocommerce.php' ) ) {
				continue;
			}

            // Check module active
            if ( empty( $global_setting_options[$slug] ) && 'global_setting' !== $slug ) {
                continue;
            }

			?>
            <li class="<?= $slug ?>-settings">
                <a<?= $current ?> title="<?= esc_attr( $title ) ?>" href="#<?= $slug ?>_settings"><?= $title ?></a>
            </li>
			<?php $i ++;
		} ?>
    </ul>
</div>
