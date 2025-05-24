<?php
/**
 * Theme functions and definitions
 *
 * @author Gaudev
 */

add_action( 'wp_enqueue_scripts', 'child_wp_enqueue_scripts', 99 );
function child_wp_enqueue_scripts(): void {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', [ 'index-css' ], false );
	wp_enqueue_script( 'child-script', get_stylesheet_directory_uri() . '/script.js', [ 'index-js' ], false, true );
}
