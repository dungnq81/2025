<?php

namespace Addons\Security;

\defined( 'ABSPATH' ) || exit;

final class Security {
	/* ---------- CONFIG -------------------------------------------------- */

	public mixed $security_options = [];

	/* ---------- CONSTRUCT ----------------------------------------------- */

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

		$hide_wp_version && $this->_hideVersion();  // Remove WP version
		$wp_links_opml_off && $this->_disableOpml();   // Disable `wp_links_opml.php`
		$rss_feed_off && $this->_disableRssFeed();    // Disable RSS and ATOM feeds
		$remove_readme && ( new Readme() );            // Add action to delete `readme.html` on WP core update if the option is set.

		// Restrict mode
		add_filter( 'user_has_cap', [ $this, 'restrictAdminPluginInstall' ], 10, 3 );
		add_filter( 'user_has_cap', [ $this, 'preventDeletionAdminAccounts' ], 10, 3 );
		add_action( 'delete_user', [ $this, 'preventDeletionUser' ], 10 );
	}

	/* ---------- PUBLIC -------------------------------------------------- */

	/**
	 * @param $user_id
	 *
	 * @return void
	 */
	public function preventDeletionUser( $user_id ): void {
		$security                            = \Addons\Helper::filterSettingOptions( 'security', [] );
		$disallowed_users_ids_delete_account = $security['disallowed_users_ids_delete_account'] ?? [];

		if ( ! is_array( $disallowed_users_ids_delete_account ) ) {
			$disallowed_users_ids_delete_account = [];
		}

		if ( in_array( $user_id, $disallowed_users_ids_delete_account, false ) ) {
			\Addons\Helper::wpDie(
				__( 'You cannot delete this admin account.', TEXT_DOMAIN ),
				__( 'Error', TEXT_DOMAIN ),
				[ 'response' => 403 ]
			);
		}
	}

	/**
	 * @param $allcaps
	 * @param $cap
	 * @param $args
	 *
	 * @return mixed
	 */
	public function preventDeletionAdminAccounts( $allcaps, $cap, $args ): mixed {
		$security                            = \Addons\Helper::filterSettingOptions( 'security', [] );
		$disallowed_users_ids_delete_account = $security['disallowed_users_ids_delete_account'] ?? [];

		if ( ! is_array( $disallowed_users_ids_delete_account ) ) {
			$disallowed_users_ids_delete_account = [];
		}

		if ( isset( $cap[0] ) && $cap[0] === 'delete_users' ) {
			$user_id_to_delete = $args[2] ?? 0;

			if ( $user_id_to_delete && in_array( $user_id_to_delete, $disallowed_users_ids_delete_account, true ) ) {
				unset( $allcaps['delete_users'] );
			}
		}

		return $allcaps;
	}

	/**
	 * @param $allcaps
	 * @param $caps
	 * @param $args
	 *
	 * @return mixed
	 */
	public function restrictAdminPluginInstall( $allcaps, $caps, $args ): mixed {
		$security                          = \Addons\Helper::filterSettingOptions( 'security', [] );
		$allowed_users_ids_install_plugins = $security['allowed_users_ids_install_plugins'] ?? [];

		if ( ! is_array( $allowed_users_ids_install_plugins ) ) {
			$allowed_users_ids_install_plugins = [];
		}

		$user_id = get_current_user_id();

		if ( $user_id && in_array( $user_id, $allowed_users_ids_install_plugins, false ) ) {
			return $allcaps;
		}

		if ( isset( $allcaps['activate_plugins'] ) ) {
			unset( $allcaps['install_plugins'], $allcaps['delete_plugins'] );
		}

		if ( isset( $allcaps['install_themes'] ) ) {
			unset( $allcaps['install_themes'] );
		}

		return $allcaps;
	}

	/**
	 * @return void
	 */
	public function disableFeed(): void {
		\Addons\Helper::redirect( trailingslashit( esc_url( network_home_url() ) ) );
	}

	/**
	 * @param $src
	 *
	 * @return mixed
	 */
	public function removeVersionScriptStyle( $src ): mixed {
		if ( $src && str_contains( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}

		return $src;
	}

	/* ---------- INTERNAL ------------------------------------------------ */

	/**
	 * @return void
	 */
	private function _disableRssFeed(): void {
		add_action( 'do_feed', [ $this, 'disableFeed' ], 1 );
		add_action( 'do_feed_rdf', [ $this, 'disableFeed' ], 1 );
		add_action( 'do_feed_rss', [ $this, 'disableFeed' ], 1 );
		add_action( 'do_feed_rss2', [ $this, 'disableFeed' ], 1 );
		add_action( 'do_feed_atom', [ $this, 'disableFeed' ], 1 );
		add_action( 'do_feed_rss2_comments', [ $this, 'disableFeed' ], 1 );
		add_action( 'do_feed_atom_comments', [ $this, 'disableFeed' ], 1 );

		remove_action( 'wp_head', 'feed_links_extra', 3 ); // remove comments feed.
		remove_action( 'wp_head', 'feed_links', 2 );
	}

	/**
	 * @return void
	 */
	private function _hideVersion(): void {
		add_filter( 'update_footer', '__return_empty_string', 11 ); // Remove an admin wp version
		add_filter( 'the_generator', '__return_empty_string' );     // Remove WP version from RSS.
		add_filter( 'style_loader_src', [ $this, 'removeVersionScriptStyle' ], PHP_INT_MAX );
		add_filter( 'script_loader_src', [ $this, 'removeVersionScriptStyle' ], PHP_INT_MAX );
	}

	/**
	 * @return void
	 */
	private function _disableOpml(): void {
		// Block direct access to wp-links-opml.php
		add_action( 'init', static function () {
			if ( str_contains( $_SERVER['REQUEST_URI'], 'wp-links-opml.php' ) ) {
				status_header( 403 );
				exit;
			}
		} );
	}
}
