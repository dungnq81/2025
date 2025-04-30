<?php

namespace Addons\LoginSecurity;

\defined( 'ABSPATH' ) || exit;

final class LoginSecurity {

	public mixed $login_security_options = [];

	// ------------------------------------------------------

	public function __construct() {
		$this->login_security_options = \Addons\Helper::getOption( 'login_security__options' );

		$this->_login_restricted();
		$this->_illegal_users();
		$this->_login_attempts();
	}

	// ------------------------------------------------------

	private function _login_restricted(): void {
		$login_restricted = new LoginRestricted();
		add_action( 'login_init', [ $login_restricted, 'restrict_login_to_ips' ], PHP_INT_MIN );
	}

	// ------------------------------------------------------

	private function _illegal_users(): void {
		if ( $this->login_security_options['illegal_users'] ?? '' ) {
			$common_user = new IllegalUsers();
			add_action( 'illegal_user_logins', [ $common_user, 'get_illegal_usernames' ] );
		}
	}

	// ------------------------------------------------------

	private function _login_attempts(): void {
		$limit_login_attempts = $this->login_security_options['limit_login_attempts'] ?? 0;
		$security_login       = new LoginAttempts();

		// Bail if optimization is disabled.
		if ( (int) $limit_login_attempts === 0 ) {
			$security_login->reset_login_attempts();

			return;
		}

		// Check the login attempts for an ip and block the access to the login page.
		add_action( 'login_head', [ $security_login, 'maybe_block_login_access' ], PHP_INT_MAX );

		// Add login attempts for ip.
		add_filter( 'login_errors', [ $security_login, 'log_login_attempt' ] );

		// Reset login attempts for an ip on successful login.
		add_filter( 'wp_login', [ $security_login, 'reset_login_attempts' ] );
	}
}
