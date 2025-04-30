<?php

namespace Addons\LoginSecurity;

\defined( 'ABSPATH' ) || exit;

final class LoginAttempts {
	/**
	 * The maximum allowed login attempts.
	 *
	 * @var int|mixed
	 */
	public int $limit_login_attempts = 0;

	/**
	 * Login attempts data
	 *
	 * @var array|string[]
	 */
	public static array $login_attempts_data = [
		0  => 'OFF',
		3  => '3',
		5  => '5',
		10 => '10',
		15 => '15',
		20 => '20',
	];

	// --------------------------------------------------

	public function __construct() {
		$security_options           = \Addons\Helper::getOption( 'login_security__options' );
		$this->limit_login_attempts = $security_options['limit_login_attempts'] ?? 0;
	}

	// --------------------------------------------------

	public function maybe_block_login_access(): void {
		// Get the user ip.
		$user_ip = \Addons\Helper::ipAddress();

		// Get login attempts data.
		$login_attempts = get_option( '_security_unsuccessful_login', [] );

		// Bail if the user doesn't have attempts.
		if ( empty( $login_attempts[ $user_ip ]['timestamp'] ) ) {
			return;
		}

		// Bail if ip has reached the login attempts limit.
		if ( $login_attempts[ $user_ip ]['timestamp'] > time() ) {
			// Update the total blocked logins counter.
			update_option( '_security_total_blocked_logins', get_option( '_security_total_blocked_logins', 0 ) + 1 );

			error_log( 'Too many incorrect login attempts. - ' . \ip_address() );
			wp_die(
				esc_html__( 'Access to login page is currently restricted because of too many incorrect login attempts.', ADDONS_TEXTDOMAIN ),
				esc_html__( 'Restricted access', ADDONS_TEXTDOMAIN ),
				[
					'addon_error'   => true,
					'response'      => 403,
					'blocked_login' => true,
				]
			);
		}

		// Reset the login attempts if the restriction time has ended and the user was banned for maximum amount of time.
		if (
			$login_attempts[ $user_ip ]['attempts'] >= $this->limit_login_attempts * 3 &&
			$login_attempts[ $user_ip ]['timestamp'] < time()
		) {
			unset( $login_attempts[ $user_ip ] );
			update_option( '_security_unsuccessful_login', $login_attempts );
		}
	}

	// --------------------------------------------------

	/**
	 * Add a login attempt for a specific ip address.
	 *
	 * @param string $error
	 *
	 * @return string
	 */
	public function log_login_attempt( string $error ): string {
		global $errors;

		// Check for errors global since custom login urls plugin is not always returning it.
		if ( empty( $errors ) ) {
			return $error;
		}

		// Invalid login
		if (
			in_array( 'empty_username', $errors->get_error_codes(), false ) ||
			in_array( 'invalid_username', $errors->get_error_codes(), false ) ||
			in_array( 'empty_password', $errors->get_error_codes(), false )
		) {
			return $error;
		}

		// Get the current user ip.
		$user_ip = \Addons\Helper::ipAddress();

		// Get the login attempts data.
		$login_attempts = get_option( '_security_unsuccessful_login', [] );

		// Add the ip to the list if it does not exist.
		if ( ! array_key_exists( $user_ip, $login_attempts ) ) {
			$login_attempts[ $user_ip ] = [
				'attempts'  => 0,
				'timestamp' => '',
			];
		}

		// Increase the attempt count.
		$login_attempts[ $user_ip ]['attempts'] ++;
		if ( $login_attempts[ $user_ip ]['attempts'] > 0 ) {
			$errors->add( 'login_attempts', __( sprintf( '<strong>Alert:</strong> You have entered the wrong credentials <strong>%s</strong> times.', $login_attempts[ $user_ip ]['attempts'] ), ADDONS_TEXTDOMAIN ) );

			if (
				in_array( 'incorrect_password', $errors->get_error_codes(), false ) &&
				in_array( 'login_attempts', $errors->get_error_codes(), false )
			) {
				$error_message = $errors->get_error_messages( 'login_attempts' );
				$error         .= '	' . $error_message[0] . "<br />\n";
			}
		}

		// Check if we are reaching the limits.
		$attempts = (int) $login_attempts[ $user_ip ]['attempts'];

		switch ( $attempts ) {
			case $attempts === (int) $this->limit_login_attempts:
				$login_attempts[ $user_ip ]['timestamp'] = time() + 3600; // Set 1-hour limit.

				break;

			case $attempts === (int) $this->limit_login_attempts * 2:
				$login_attempts[ $user_ip ]['timestamp'] = time() + 86400; // Set 24-hour limit.

				break;

			case $attempts > (int) $this->limit_login_attempts * 3:
				$login_attempts[ $user_ip ]['timestamp'] = time() + 604800; // Set 7-day limit.

				break;

			// Do not set restriction if we do not reach any limits.
			default:
				$login_attempts[ $user_ip ]['timestamp'] = '';

				break;
		}

		// Update the login attempts data.
		update_option( '_security_unsuccessful_login', $login_attempts );

		return $error;
	}

	// --------------------------------------------------

	public function reset_login_attempts(): void {
		$user_ip        = \Addons\Helper::ipAddress();
		$login_attempts = get_option( '_security_unsuccessful_login', [] );

		// Bail if the IP doesn't exist in the unsuccessful logins.
		if ( ! array_key_exists( $user_ip, $login_attempts ) ) {
			return;
		}

		unset( $login_attempts[ $user_ip ] );
		update_option( '_security_unsuccessful_login', $login_attempts );
	}
}
