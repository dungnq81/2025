<?php

namespace Addons\Security;

\defined( 'ABSPATH' ) || exit;

final class Xmlrpc {
	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function disable(): void {
		// Disable XMLRPC authentication and related functions
		if ( is_admin() ) {
			update_option( 'default_ping_status', 'closed' );
		}

		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'pre_update_option_enable_xmlrpc', '__return_false' );
		add_filter( 'pre_option_enable_xmlrpc', '__return_zero' );

		// Unset XMLRPC headers
		add_filter( 'wp_headers', static function ( $headers ) {
			if ( isset( $headers['X-Pingback'] ) ) {
				unset( $headers['X-Pingback'] );
			}

			return $headers;
		}, 10, 1 );

		// Unset XMLRPC methods for ping-backs
		add_filter( 'xmlrpc_methods', static function ( $methods ) {
			unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

			return $methods;
		}, 10, 1 );

		// Block direct access to xmlrpc.php
		add_action( 'init', static function () {
			if ( str_contains( $_SERVER['REQUEST_URI'], 'xmlrpc.php' ) ) {
				status_header( 403 );
				exit;
			}
		});
	}
}
