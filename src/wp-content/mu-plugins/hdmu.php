<?php
/**
 * Plugin Name: HDMU
 * Description: mu-plugins for HD theme
 * Version: 1.0.5
 * Requires PHP: 8.2
 * Author: Gaudev
 * License: MIT
 */

define( 'MU_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR ); // **/wp-content/mu-plugins/
define( 'MU_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/' ); // https://**/wp-content/mu-plugins/
define( 'MU_BASENAME', plugin_basename( __FILE__ ) ); // **/**.php

if ( file_exists( __DIR__ . '/hdmu/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/hdmu/vendor/autoload.php';

	function plugins_loaded(): void {
		require_once MU_PATH . 'hdmu' . DIRECTORY_SEPARATOR . 'MU.php';
		( new \MU() );
	}

	\plugins_loaded();
}
