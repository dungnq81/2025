<?php

namespace Addons\ThirdParty;

\defined( 'ABSPATH' ) || exit;

final class ACF {
	public function __construct() {
		add_action( 'acf/include_field_types', [ $this, 'register_field_for_nav_menu' ] );
	}

	// -------------------------------------------------------------

	public function register_field_for_nav_menu(): void {
		if ( \Addons\Helper::checkPluginActive( 'acf-nav-menu-field/advanced-custom-nav-menu-field.php' ) ||
		     \Addons\Helper::checkPluginActive( 'advanced-custom-nav-menu-field/advanced-custom-nav-menu-field.php' )
		) {
			return;
		}

		( new \Addons\ThirdParty\AcfField\NavMenu() );
	}
}
