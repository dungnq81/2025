<?php
/**
 * Theme functions and definitions
 *
 * @author Gaudev
 */

add_action( 'wp_enqueue_scripts', '_wp_enqueue_scripts', 99 );
function _wp_enqueue_scripts(): void {
	\HD_Asset::enqueueStyle( '_style', get_stylesheet_directory_uri() . '/style.css', [ 'index-css' ], \HD_Helper::version() );
	\HD_Asset::enqueueScript( '_script', get_stylesheet_directory_uri() . '/script.js', [ 'index-js' ], \HD_Helper::version(), true, [ 'defer' ] );
}
