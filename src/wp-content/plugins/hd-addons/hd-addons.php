<?php
/**
 * Plugin Name: HD Addons
 * Plugin URI: https://webhd.vn
 * Version: 1.0.4
 * Requires PHP: 8.2
 * Author: Gaudev
 * Author URI: https://webhd.vn
 * Description: Extra blocks and helpers for HD Theme.
 * License: MIT
 *
 * ###Requires ### Plugins: advanced-custom-fields-pro
 */

defined( 'ABSPATH' ) || exit;

const ADDONS_VERSION    = '1.0.5';
const ADDONS_TEXTDOMAIN = 'hd-addon';

define( 'ADDONS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR );
define( 'ADDONS_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/' );

add_action( 'plugins_loaded', '_addons_init', 10 );
function _addons_init(): void {
	load_plugin_textdomain( ADDONS_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); // i18n

	// PHP version guard (8.2 or newer)
	if ( PHP_VERSION_ID < 80200 ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'HD‑Addons needs PHP 8.2 or newer. Please upgrade.', ADDONS_TEXTDOMAIN );
			echo '</p></div>';
		} );

		return;
	}

	// Composer autoload
	$autoload = ADDONS_PATH . 'vendor/autoload.php';
	if ( ! file_exists( $autoload ) ) {
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'HD‑Addons: missing vendor. Run <code>composer install</code>.', ADDONS_TEXTDOMAIN );
			echo '</p></div>';
		} );

		return;
	}

	require_once $autoload; // composer dump-autoload -o --classmap-authoritative

	// Activation / Deactivation / Uninstall
	register_activation_hook( __FILE__, [ \Addons\Activator::class, 'activation' ] );
	register_deactivation_hook( __FILE__, [ \Addons\Activator::class, 'deactivation' ] );
	register_uninstall_hook( __FILE__, [ \Addons\Activator::class, 'uninstall' ] );

	// Bootstrap
	_addons_bootstrap();
}

/**
 * @return void
 */
function _addons_bootstrap(): void {
	// print notice
	if ( ! \Addons\Helper::isAcfActive() ) {
		acf_requirement_notice();

		return;
	}

	try {
		( new \Addons\Addons() );
	} catch ( \Throwable $e ) {
		\Addons\Helper::errorLog( '[HD‑Addons] ' . $e->getMessage() );
		if ( \Addons\Helper::version() ) {
			add_action( 'admin_notices', static function () use ( $e ) {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html( $e->getMessage() )
				);
			} );
		}
	}
}

// ACF requirement notice
if ( ! function_exists( 'acf_requirement_notice' ) ) {
	function acf_requirement_notice(): void {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_notices', static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'HD‑Addons needs Advanced Custom Fields PRO. Please install/activate it.', ADDONS_TEXTDOMAIN )
			);
		} );
	}
}
