<?php
/**
 * Configuration overrides for WP_ENV === 'development'
 */

use function Env\env;
use Roots\WPConfig\Config;

Config::define( 'SAVEQUERIES', true );
Config::define( 'WP_DEBUG', true );
Config::define( 'WP_DEBUG_DISPLAY', false );
Config::define( 'WP_DEBUG_LOG', env( 'WP_DEBUG_LOG' ) ?? true );
Config::define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
Config::define( 'SCRIPT_DEBUG', true );
Config::define( 'DISALLOW_INDEXING', true );
#Config::define( 'WP_ALLOW_REPAIR', true );

ini_set( 'display_errors', '1' );

// Enable plugin and theme updates and installation from the admin
Config::define( 'DISALLOW_FILE_EDIT', false );
Config::define( 'DISALLOW_FILE_MODS', false );

/** DISABLED_PLUGINS */
Config::define( 'DISABLED_PLUGINS', [
	//'wp-rocket/wp-rocket.php'
] );
