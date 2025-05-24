<?php

namespace Addons\LoginSecurity;

\defined( 'ABSPATH' ) || exit;

final class LoginSecurity {
	// ------------------------------------------------------

	public function __construct() {
		( new LoginRestricted() );
		( new LoginIllegalUsers() );
		( new LoginAttempts() );
		( new LoginOtpVerification() );

		// csrf login-form
		add_action( 'login_form', [ $this, 'addCsrfLoginForm' ] );
		add_filter( 'authenticate', [ $this, 'verifyCsrfLogin' ], 30, 3 );
		add_filter( 'login_message', [ $this, 'showCsrfErrorMessage' ] );

		// csrf lost-password
		add_action( 'lostpassword_form', [ $this, 'addCsrfLostpasswordForm' ] );
		add_action( 'lostpassword_post', [ $this, 'verifyCsrfLostpasswordPost' ] );
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function addCsrfLoginForm(): void {
		$csrf_token = wp_create_nonce( 'login_csrf_token' );
		echo '<input type="hidden" name="login_csrf_token" value="' . esc_attr( $csrf_token ) . '">';
	}

	// ------------------------------------------------------

	/**
	 * @param $user
	 * @param $username
	 * @param $password
	 *
	 * @return mixed|\WP_Error
	 */
	public function verifyCsrfLogin( $user, $username, $password ): mixed {
		if ( ! empty( $_POST['login_csrf_token'] ) && ! wp_verify_nonce( $_POST['login_csrf_token'], 'login_csrf_token' ) ) {
			return new \WP_Error( 'csrf_error', __( 'Invalid CSRF token. Please try again.' ) );
		}

		return $user;
	}

	// ------------------------------------------------------

	/**
	 * @param $message
	 *
	 * @return mixed|string
	 */
	public function showCsrfErrorMessage( $message ): mixed {
		if ( isset( $_GET['login'] ) && $_GET['login'] === 'csrf_error' ) {
			$message .= '<div id="login_error">' . __( 'Invalid CSRF token. Please try again.' ) . '</div>';
		}

		return $message;
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function addCsrfLostpasswordForm(): void {
		$nonce = wp_create_nonce( 'lostpassword_csrf_token' );
		echo '<input type="hidden" name="lostpassword_csrf_token" value="' . esc_attr( $nonce ) . '">';
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function verifyCsrfLostpasswordPost(): void {
		if ( isset( $_POST['lostpassword_csrf_token'] ) ) {
			$nonce = $_POST['lostpassword_csrf_token'];

			if ( ! wp_verify_nonce( $nonce, 'lostpassword_csrf_token' ) ) {
				\HD_Helper::wpDie(
					__( 'Invalid CSRF token, please try again.', TEXT_DOMAIN ),
					__( 'Error', TEXT_DOMAIN ),
					[ 'response' => 403 ]
				);
			}
		}
	}
}
