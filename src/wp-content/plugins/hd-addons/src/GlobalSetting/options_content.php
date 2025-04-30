<?php

\defined( 'ABSPATH' ) || exit;

?>
<div id="_content" class="tabs-content">
    <h2 class="hidden-text"></h2>
	<?php

	$menu_options           = \Addons\Helper::loadYaml( ADDONS_PATH . 'config.yaml' );
	$global_setting_options = \Addons\Helper::getOption( 'global_setting__options' );
	$i                      = 0;

	foreach ( $menu_options as $current_slug => $value ) {
		//$show_class = ( $i === 0 ) ? ' show' : '';
		$show_class = '';

		$current_title       = ! empty( $value['title'] ) ? __( $value['title'], ADDONS_TEXTDOMAIN ) : '';
		$current_description = ! empty( $value['description'] ) ? __( $value['description'], ADDONS_TEXTDOMAIN ) : '';

		// WooCommerce
		if ( (string) $current_slug === 'woocommerce' && ! \Addons\Helper::checkPluginActive( 'woocommerce/woocommerce.php' ) ) {
			continue;
		}

		// Check module active
		if ( empty( $global_setting_options[$current_slug] ) && 'global_setting' !== $current_slug ) {
			continue;
		}

		?>
        <div id="<?= $current_slug ?>_settings" class="group tabs-panel<?= $show_class ?>">
			<?php

			echo '<h2>' . $current_title . '</h2>';
			echo '<div class="desc mb-30">' . $current_description . '</div>';

			$option_file = ADDONS_PATH . 'src/' . \Addons\Helper::capitalizedSlug( $current_slug, true ) . '/options.php';
			file_exists( $option_file ) && include $option_file;

			?>
        </div>
		<?php $i ++;
	} ?>
    <div class="save-bar">
        <button type="submit" name="_submit_settings" class="button button-primary"><?php _e( 'Save Changes', ADDONS_TEXTDOMAIN ); ?></button>
    </div>
</div>
