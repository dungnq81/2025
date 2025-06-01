<?php

declare( strict_types=1 );

namespace Addons\LoginSecurity;

\defined( 'ABSPATH' ) || exit;

/**
 * Custom Login Url
 *
 * @author Gaudev
 */
class LoginUrl {
	/* ---------- CONFIG -------------------------------------------------- */

	private const CLU_TOKEN = 'token';
	private array $options = [];

	/* ---------- CONSTRUCT ----------------------------------------------- */

	public function __construct() {
		if ( ! $this->_isEnabled() ) {
			return;
		}

		add_action( 'plugins_loaded', [ $this, 'handleRequest' ], 1000 );
		add_action( 'wp_authenticate_user', [ $this, 'maybeBlockCustomLogin' ], 10, 1 );
		add_filter( 'wp_logout', [ $this, 'logout' ] );
		add_filter( 'logout_redirect', [ $this, 'logoutRedirect' ], 10, 3 );
	}

	/* ---------- PUBLIC -------------------------------------------------- */

	/**
	 * Handle user logout.
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function logout( int $user_id ): void {
		if ( $this->_isValidCookie( 'login' ) ) {
			$this->_removeCookie( 'login' );

			return;
		}

		// Redirect to the homepage.
		wp_redirect( home_url( '/' ) );
		exit;
	}

	/**
	 * @param $redirect_to
	 * @param $requested_redirect_to
	 * @param $user
	 *
	 * @return string
	 */
	public function logoutRedirect( $redirect_to, $requested_redirect_to, $user ): string {
		return add_query_arg( self::CLU_TOKEN, rawurlencode( $this->_queryToken() ), $redirect_to );
	}

	/**
	 * Handle request paths.
	 *
	 * @return void
	 */
	public function handleRequest(): void {
		$path = $this->_relativePath( $_SERVER['REQUEST_URI'], false );

		if ( $path === $this->options['new_slug'] ) {
			$this->_redirectToken( 'login', 'wp-login.php' );
		}

		if ( str_contains( $path, 'wp-login' ) || str_contains( $path, 'wp-login.php' ) ) {
			$this->_handleLogin();
		}

		if ( $path === $this->options['register'] ) {
			$this->_handleRegistration();
		}
	}

	/**
	 * Block administrators from logging-in through third party login forms when Custom Login URL is enabled.
	 *
	 * @param \WP_User $user
	 *
	 * @return \WP_Error|\WP_User
	 */
	public function maybeBlockCustomLogin( \WP_User $user ): \WP_Error|\WP_User {
		if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
			return $user;
		}

		$error = new \WP_Error(
			'authentication_failed',
			__( '<strong>Error</strong>: Invalid login credentials.', ADDONS_TEXTDOMAIN )
		);

		// Get referer parts by parsing its url.
		$referer = str_replace(
			[ $this->_siteUrl(), '/' ],
			[ '', '' ],
			$_SERVER['HTTP_REFERER']
		);

		$referer_parts = wp_parse_url( $referer );
		if ( empty( $referer_parts['query'] ) ) {
			return $error;
		}

		parse_str( $referer_parts['query'], $referer_query );
		$token = ! empty( $referer_query[ self::CLU_TOKEN ] ) ? esc_attr( $referer_query[ self::CLU_TOKEN ] ) : '';

		if (
			! empty( $token ) &&
			hash_equals( $token, rawurldecode( $this->_queryToken() ) )
		) {
			return $user;
		}

		return $error;
	}

	/**
	 * Block a request to the page.
	 *
	 * @param string $type
	 *
	 * @return void
	 */
	public function block( string $type = 'login' ): void {
		if ( is_user_logged_in() || $this->_isValidCookie( $type ) ) {
			return;
		}

		// Die if there is not 404 page.
		if ( empty( $this->options['redirect'] ) ) {
			\Addons\Helper::wpDie(
				esc_html__( 'This feature has been disabled.', ADDONS_TEXTDOMAIN ),
				esc_html__( 'Restricted access', ADDONS_TEXTDOMAIN ),
				[
					'addon_error' => true,
					'response'    => 403,
				]
			);
		}

		// Redirect to 404 page.
		wp_redirect( $this->_homeUrl() . $this->options['redirect'], 302 );
		exit;
	}

	/* ---------- INTERNAL ------------------------------------------------ */

	/**
	 * Handle login.
	 *
	 * @return void
	 */
	private function _handleLogin(): void {
		$action = $_GET['action'] ?? '';

		if ( in_array( $action, [ 'rp', 'resetpass', 'postpass', 'lostpassword' ] ) ) {
			return;
		}

		if ( 'register' === $action ) {
			if ( 'wp-signup.php' !== $this->options['register'] ) {
				$this->block( 'register' );
			}

			return;
		}

		// Jetpack
		if (
			'jetpack_json_api_authorization' === $action &&
			has_filter( 'login_form_jetpack_json_api_authorization' )
		) {
			return;
		}

		// Jetpack SSO
		if (
			'jetpack-sso' === $action &&
			has_filter( 'login_form_jetpack-sso' )
		) {
			add_action( 'login_form_jetpack-sso', [ $this, 'block' ] );

			return;
		}

		$this->block( 'login' );
	}

	/**
	 * Handle registration request.
	 *
	 * @return void
	 */
	private function _handleRegistration(): void {
		if (
			1 !== (int) \Addons\Helper::getOption( 'users_can_register', 0 ) ||
			empty( \Addons\Helper::getOption( 'users_can_register' ) )
		) {
			return;
		}

		$this->_setPermissionsCookie( 'login' );

		if ( is_multisite() ) {
			$this->_redirectToken( 'register', 'wp-signup.php' );
		}

		$this->_redirectToken( 'register', 'wp-login.php?action=register' );
	}

	/**
	 * Adds a token and redirect to the url.
	 *
	 * @param $type
	 * @param $path
	 *
	 * @return void
	 */
	private function _redirectToken( $type, $path ): void {
		$this->_setPermissionsCookie( $type );

		// Preserve existing query vars and add access token query arg.
		$query_vars                    = $_GET;
		$query_vars[ self::CLU_TOKEN ] = rawurlencode( $this->_queryToken() );

		$url = add_query_arg( $query_vars, rtrim( $this->_siteUrl( $path ), '/' ) );

		wp_redirect( $url );
		exit;
	}

	/**
	 * @return string
	 */
	private function _queryToken(): string {
		return wp_hash( gmdate( 'Y-m-d' ) . '-' . $this->options['new_slug'] );
	}

	/**
	 * Set a cookie which will be used to check if the user has permissions to view a page.
	 *
	 * @param string $type
	 */
	private function _setPermissionsCookie( string $type = '' ): void {
		$url_parts = wp_parse_url( $this->_siteUrl() );
		$home_path = trailingslashit( $url_parts['path'] );

		if ( ! empty( $type ) ) {
			setcookie(
				self::CLU_TOKEN . '-' . $type . '-' . COOKIEHASH,
				$this->options['new_slug'],
				[
					'expires'  => time() + 3600,
					'path'     => $home_path,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				]
			);
		}
	}

	/**
	 * @param string $type
	 *
	 * @return void
	 */
	private function _removeCookie( string $type = 'login' ): void {
		$url_parts = wp_parse_url( $this->_siteUrl() );
		$home_path = trailingslashit( $url_parts['path'] );

		setcookie(
			self::CLU_TOKEN . '-' . $type . '-' . COOKIEHASH,
			'',
			[
				'expires'  => time() - 3600,
				'path'     => $home_path,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * Checks if the user has permissions to view a page.
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	private function _isValidCookie( string $type ): bool {
		$cookie = self::CLU_TOKEN . '-' . $type . '-' . COOKIEHASH;

		// Check if the validation cookie is set.
		if ( isset( $_COOKIE[ $cookie ] ) && $_COOKIE[ $cookie ] === $this->options['new_slug'] ) {
			return true;
		}

		// Check if the token value is set.
		if (
			isset( $_REQUEST[ self::CLU_TOKEN ] ) &&
			hash_equals( $_REQUEST[ self::CLU_TOKEN ], rawurldecode( $this->_queryToken() ) )
		) {
			// Add the permissions' cookie.
			$this->_setPermissionsCookie( $type );

			return true;
		}

		return false;
	}

	/**
	 * Get the path without a home URL path.
	 *
	 * @param string $url
	 * @param bool $queryString
	 *
	 * @return string The URL path.
	 */
	private function _relativePath( string $url, bool $queryString = false ): string {
		$url_parts = wp_parse_url( $this->_siteUrl() );
		$home_path = ! empty( $url_parts['path'] ) ? trailingslashit( $url_parts['path'] ) : '/';

		$_temp_url = explode( '?', wp_make_link_relative( $url ) );
		$path      = wp_parse_url( $_temp_url[0], PHP_URL_PATH );

		if ( $queryString && ! empty( $_temp_url[1] ) ) {
			$path .= '?' . $_temp_url[1];
		}

		return $path ? ltrim( str_replace( $home_path, '', $path ), '/' ) : '';
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	private function _siteUrl( string $path = '' ): string {
		$url    = \Addons\Helper::getOption( 'siteurl' );
		$scheme = is_ssl() ? 'https' : parse_url( $url, PHP_URL_SCHEME );
		$url    = set_url_scheme( $url, $scheme );

		if ( $path && is_string( $path ) ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		return trailingslashit( $url );
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function _homeUrl( string $path = '' ): string {
		$url    = \Addons\Helper::getOption( 'home' );
		$scheme = is_ssl() ? 'https' : parse_url( $url, PHP_URL_SCHEME );
		$url    = set_url_scheme( $url, $scheme );

		if ( $path && is_string( $path ) ) {
			$url .= '/' . ltrim( $path, '/' );
		}

		return trailingslashit( $url );
	}

	/**
	 * True if plugin enabled via option.
	 *
	 * @return bool
	 */
	private function _isEnabled(): bool {
		$opt              = \Addons\Helper::getOption( 'login_security__options' );
		$custom_login_uri = ! empty( $opt['custom_login_uri'] ) ? $opt['custom_login_uri'] : 'wp-login.php';

		// Check if the `WPS Hide Login` plugin is active.
		if ( \class_exists( 'WPS\WPS_Hide_Login\Plugin' ) ) {
			return true;
		}

		// Set the required options.
		$this->options = [
			'new_slug' => $custom_login_uri,
			'redirect' => apply_filters( 'clu_login_redirect', wp_make_link_relative( home_url( '/' ) ) ),
			'register' => apply_filters( 'clu_login_register', 'register' ),
		];

		return ! empty( $custom_login_uri ) && ! in_array( $custom_login_uri, [ 'wp-login.php', 'wp-admin' ] );
	}
}
