<?php
/**
 * Theme functions and definitions
 *
 * @author Gaudev
 */

const THEME_VERSION = '1.0.5';
const TEXT_DOMAIN   = 'hd';
const AUTHOR        = 'Gaudev';

define( 'THEME_PATH', untrailingslashit( get_template_directory() ) . DIRECTORY_SEPARATOR ); // **/wp-content/themes/**/
define( 'THEME_URL', untrailingslashit( get_template_directory_uri() ) . '/' );  // http(s)://**/wp-content/themes/**/

const INC_PATH   = THEME_PATH . 'inc' . DIRECTORY_SEPARATOR;
const ASSETS_URL = THEME_URL . 'assets/';

/**
 * @param $error_message
 *
 * @return void
 */
function _static_error( $error_message ): void {
	add_action( 'admin_notices', static function () use ( $error_message ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $error_message ) . '</p></div>';
	} );

	if ( ! is_admin() ) {
		get_template_part( 'template-blocks/php-error', null, [ 'error_message' => $error_message ] );
		die();
	}
}

// PHP version guard (8.2 or newer)
if ( PHP_VERSION_ID < 80200 ) {
	_static_error( 'HD Theme: requires PHP 8.2 or newer. Please upgrade your PHP version.' );

	return;
}

// Composer autoload
$autoload = __DIR__ . '/vendor/autoload.php';
if ( ! file_exists( $autoload ) ) {
	_static_error( 'HD Theme: missing vendor autoload file. Please run `composer install`.' );

	return;
}

require_once $autoload; // composer dump-autoload -o --classmap-authoritative

class_alias( \HD\Utilities\Helpers\Helper::class, 'HD_Helper' );
class_alias( \HD\Utilities\Helpers\Asset::class, 'HD_Asset' );
class_alias( \HD\Utilities\Helpers\CSS::class, 'HD_CSS' );

require_once __DIR__ . '/inc/setting.php';
require_once __DIR__ . '/inc/helper.php';

// Initialize theme.
( \HD\Core\Theme::get_instance() );

$rest_instance = ( \HD\API\API::get_instance() );
define( 'RESTAPI_URL', untrailingslashit( $rest_instance->restApiUrl() ) . '/' );
