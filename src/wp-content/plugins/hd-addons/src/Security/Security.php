<?php

namespace Addons\Security;

\defined( 'ABSPATH' ) || exit;

final class Security {

	public mixed $security_options = [];

	// ------------------------------------------------------

	public function __construct() {
		$this->security_options = \Addons\Helper::getOption( 'security__options' );

		$comments_off      = $this->security_options['comments_off'] ?? false;
		$xmlrpc_off        = $this->security_options['xmlrpc_off'] ?? false;
		$hide_wp_version   = $this->security_options['hide_wp_version'] ?? false;
		$wp_links_opml_off = $this->security_options['wp_links_opml_off'] ?? false;
		$rss_feed_off      = $this->security_options['rss_feed_off'] ?? false;
		$remove_readme     = $this->security_options['remove_readme'] ?? false;

		$comments_off && ( new Comment() )->disable();  // Disable comments
		$xmlrpc_off && ( new Xmlrpc() )->disable();     // Disable `xmlprc.php`

		$hide_wp_version && $this->_hide_wp_version();  // Remove WP version
		$wp_links_opml_off && $this->_disable_opml();   // Disable `wp_links_opml.php`
		$rss_feed_off && $this->_disable_rss_feed();    // Disable RSS and ATOM feeds

		$remove_readme && ( new Readme() );            // Add action to delete `readme.html` on WP core update if the option is set.
	}

	// ------------------------------------------------------

	private function _hide_wp_version(): void {
		add_filter( 'update_footer', '__return_empty_string', 11 ); // Remove an admin wp version
		add_filter( 'the_generator', '__return_empty_string' );     // Remove WP version from RSS.
		add_filter( 'style_loader_src', [ $this, 'remove_version_scripts_styles' ], PHP_INT_MAX );
		add_filter( 'script_loader_src', [ $this, 'remove_version_scripts_styles' ], PHP_INT_MAX );
	}

	// ------------------------------------------------------

	private function _disable_opml(): void {
		// Block direct access to wp-links-opml.php
		add_action( 'init', static function () {
			if ( str_contains( $_SERVER['REQUEST_URI'], 'wp-links-opml.php' ) ) {
				status_header( 403 );
				exit;
			}
		} );
	}

	// ------------------------------------------------------

	private function _disable_rss_feed(): void {
		add_action( 'do_feed', [ $this, 'disable_feed' ], 1 );
		add_action( 'do_feed_rdf', [ $this, 'disable_feed' ], 1 );
		add_action( 'do_feed_rss', [ $this, 'disable_feed' ], 1 );
		add_action( 'do_feed_rss2', [ $this, 'disable_feed' ], 1 );
		add_action( 'do_feed_atom', [ $this, 'disable_feed' ], 1 );
		add_action( 'do_feed_rss2_comments', [ $this, 'disable_feed' ], 1 );
		add_action( 'do_feed_atom_comments', [ $this, 'disable_feed' ], 1 );

		remove_action( 'wp_head', 'feed_links_extra', 3 ); // remove comments feed.
		remove_action( 'wp_head', 'feed_links', 2 );
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function disable_feed(): void {
		\Addons\Helper::redirect( trailingslashit( esc_url( network_home_url() ) ) );
	}

	// ------------------------------------------------------

	/**
	 * @param $src
	 *
	 * @return mixed
	 */
	public function remove_version_scripts_styles( $src ): mixed {
		if ( $src && str_contains( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}

		return $src;
	}
}
