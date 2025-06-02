<?php

namespace Addons\Security;

\defined( 'ABSPATH' ) || exit;

final class Security {
	/* ---------- CONFIG -------------------------------------------------- */

	public mixed $securityOptions = [];

	/* ---------- CONSTRUCT ----------------------------------------------- */

	public function __construct() {
		$this->securityOptions = \Addons\Helper::getOption( 'security__options' );

		$comments_off      = $this->securityOptions['comments_off'] ?? false;
		$xmlrpc_off        = $this->securityOptions['xmlrpc_off'] ?? false;
		$hide_wp_version   = $this->securityOptions['hide_wp_version'] ?? false;
		$wp_links_opml_off = $this->securityOptions['wp_links_opml_off'] ?? false;
		$rss_feed_off      = $this->securityOptions['rss_feed_off'] ?? false;
		$remove_readme     = $this->securityOptions['remove_readme'] ?? false;

		$comments_off && ( new Comment() )->disable();
		$xmlrpc_off && ( new Xmlrpc() )->disable();

		$hide_wp_version && $this->_hideVersion();
		$wp_links_opml_off && $this->_disableOpml();
		$rss_feed_off && $this->_disableRssFeed();
		$remove_readme && ( new Readme() );

		// Restrict mode
		add_filter( 'all_plugins', [ $this, 'hidePluginInstall' ], 10 );
		add_filter( 'user_has_cap', [ $this, 'restrictPluginInstall' ], 10, 3 );
		add_filter( 'user_has_cap', [ $this, 'preventDeletionAccounts' ], 10, 3 );
		add_action( 'delete_user', [ $this, 'preventDeletionUser' ], 10 );
		add_action( 'pre_user_query', [ $this, 'hideUsers' ], 20 );
	}

	/* ---------- PUBLIC -------------------------------------------------- */

	/**
	 * Hide plugin X from the plugin list for everyone
	 *
	 * @param array $plugins
	 *
	 * @return array
	 */
	public function hidePluginInstall( array $plugins ): array {
		$security    = \Addons\Helper::filterSettingOptions( 'security', [] );
		$allowed_ids = $security['allowed_users_ids_show_plugins'] ?? [];

		if ( ! is_array( $allowed_ids ) ) {
			$allowed_ids = [];
		}

		$user_id = get_current_user_id();
		if ( ! in_array( $user_id, $allowed_ids, true ) ) {

			$target_plugins = [ 'hd-addons/hd-addons.php' ];
			foreach ( $target_plugins as $target_plugin ) {
				unset( $plugins[ $target_plugin ] );
			}
		}

		return $plugins;
	}

	/**
	 * Hide protected accounts from the Users screen for everyone
	 *
	 * @param \WP_User_Query $query
	 *
	 * @return void
	 */
	public function hideUsers( \WP_User_Query $query ): void {
		if ( ( $GLOBALS['pagenow'] !== 'users.php' ) || ! is_admin() ) {
			return;
		}

		$security   = \Addons\Helper::filterSettingOptions( 'security', [] );
		$hidden_ids = $security['disallowed_users_ids_delete_account'] ?? [];

		if ( empty( $hidden_ids ) || ! is_array( $hidden_ids ) ) {
			return;
		}

		global $wpdb;

		$user_id    = get_current_user_id();
		$hidden_ids = array_map( 'absint', $hidden_ids );

		if ( ! in_array( $user_id, $hidden_ids, true ) ) {
			$ids_sql            = implode( ',', $hidden_ids );
			$query->query_where = str_replace(
				'WHERE 1=1',
				"WHERE 1=1 AND {$wpdb->users}.ID NOT IN ( {$ids_sql} )",
				$query->query_where
			);
		}
	}

	/**
	 * @param $user_id
	 *
	 * @return void
	 */
	public function preventDeletionUser( $user_id ): void {
		$security   = \Addons\Helper::filterSettingOptions( 'security', [] );
		$hidden_ids = $security['disallowed_users_ids_delete_account'] ?? [];

		if ( ! is_array( $hidden_ids ) ) {
			$hidden_ids = [];
		}

		if ( in_array( $user_id, $hidden_ids, false ) ) {
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
	public function preventDeletionAccounts( $allcaps, $cap, $args ): mixed {
		$security   = \Addons\Helper::filterSettingOptions( 'security', [] );
		$hidden_ids = $security['disallowed_users_ids_delete_account'] ?? [];

		if ( ! is_array( $hidden_ids ) ) {
			$hidden_ids = [];
		}

		if ( isset( $cap[0] ) && in_array( $cap[0], [ 'delete_users', 'edit_users' ] ) ) {
			$user_id_to_delete = $args[2] ?? 0;
			if ( $user_id_to_delete && in_array( $user_id_to_delete, $hidden_ids, true ) ) {
				unset( $allcaps['delete_users'], $allcaps['edit_users'] );
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
	public function restrictPluginInstall( $allcaps, $caps, $args ): mixed {
		$security    = \Addons\Helper::filterSettingOptions( 'security', [] );
		$allowed_ids = $security['allowed_users_ids_install_plugins'] ?? [];

		if ( ! is_array( $allowed_ids ) ) {
			$allowed_ids = [];
		}

		$user_id = get_current_user_id();

		if ( $user_id && in_array( $user_id, $allowed_ids, false ) ) {
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
