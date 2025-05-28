<?php

declare( strict_types=1 );

namespace Addons\LoginSecurity;

use Random\RandomException;

\defined( 'ABSPATH' ) || exit;

/**
 * Simple Email-OTP Login
 *
 * @author Gaudev
 */
class LoginOtpVerification {
	/* ---------- TRANSIENT & META KEYS ----------------------------------- */

	private const KEY_OTP = 'loginotp_%d';     // hash (OTP)
	private const KEY_ATTEMPT = 'loginotp_try_%d'; // int
	private const META_LASTSEND = '_otp_last_send';  // timestamp
	private const META_TOKEN = '_otp_dnc_token';  // random

	/* ---------- CONFIG -------------------------------------------------- */

	public const OTP_DIGITS = 6;
	public const OTP_LIFETIME = 5 * MINUTE_IN_SECONDS; // 5 minutes (transient and form)
	public const RESEND_INTERVAL = 5 * MINUTE_IN_SECONDS; // 5 minutes (cool-down email)
	public const COOKIE_LIFETIME = DAY_IN_SECONDS; // 1 day
	public const MAX_ATTEMPTS = 5;
	public const ACTION_VALIDATE = '_otp_validate';

	/* ---------- LIFECYCLE ----------------------------------------------- */

	public function __construct() {
		if ( ! $this->_isEnabled() ) {
			return;
		}

		add_action( 'login_enqueue_scripts', [ $this, 'enqueueAssets' ], 32 );

		// login / logout
		add_action( 'wp_login', [ $this, 'initOtp' ], 10, 2 ); // Fires after the user has successfully logged in.
		add_action( 'wp_logout', [ $this, 'cleanupOtpOnLogout' ], 10, 1 );
		add_action( 'clear_auth_cookie', [ $this, 'cleanupOtpOnLogout' ], 10, 0 );

		// form + message
		add_filter( 'login_message', [ $this, 'otpFailMessage' ] );
		add_action( 'login_form_' . self::ACTION_VALIDATE, [ $this, 'validateOtpLogin' ] );
	}

	/* ---------- PUBLIC HOOKS -------------------------------------------- */

	/**
	 * Enqueue JS on OTP login page
	 *
	 * @return void
	 */
	public function enqueueAssets(): void {
		\Addons\Asset::enqueueScript( 'login-otp-js', ADDONS_URL . 'assets/js/login-otp.js', [ 'login-js' ], null, true, [
			'module',
			'defer'
		] );
	}

	/**
	 * Sends OTP, if not yet verified, fires after the user has successfully logged in.
	 *
	 * @param string $user_login Username.
	 * @param \WP_User $user WP_User object of the logged-in user.
	 *
	 * @return void
	 * @throws RandomException
	 */
	public function initOtp( string $user_login, \WP_User $user ): void {
		// Only roles configured to use OTP
		if ( empty( array_intersect( $this->_otpUserRoles(), $user->roles ) ) ) {
			return;
		}

		// Already has valid OTP cookie → skip
		if ( true === $this->_checkOtpCookie( $user ) ) {
			return;
		}

		// Remove auth-cookie to pause the session
		wp_clear_auth_cookie();

		// Send OTP email (respects cool-down)
		$result = $this->_maybeSendOtpEmail( $user );
		if ( $result === false ) {
			$this->_clearOtpData( $user->ID );
			wp_safe_redirect( add_query_arg( '_error', 'email', wp_login_url() ) );
			exit;
		}

		// Show an OTP form
		$this->_loadForm( [
			'action'   => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
			'template' => 'recovery-login.php',
			'uid'      => $user->ID,
			'send_at' => (int) get_user_meta( $user->ID, self::META_LASTSEND, true ),
			'error'    => '',
		] );
	}

	/**
	 * Remove all OTP artifacts when a user logs out (or cookie cleared)
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function cleanupOtpOnLogout( int $user_id = 0 ): void {
		$user_id = $user_id ?: get_current_user_id();
		if ( $user_id ) {
			$this->_clearOtpData( $user_id );
		}
	}

	/**
	 * Handle OTP submit (`wp-login.php?action=_otp_validate`)
	 *
	 * @throws RandomException
	 */
	public function validateOtpLogin(): void {
		// Basic validation
		if ( empty( $_POST['authcode'] ) ||
		     empty( $_POST['uid'] ) ||
		     empty( $_POST['_csrf_token'] ) ||
		     ! wp_verify_nonce( wp_unslash( $_POST['_csrf_token'] ), 'otp_csrf_token' )
		) {
			return;
		}

		$userId  = (int) $_POST['uid'];
		$entered = preg_replace( '/\D/', '', (string) wp_unslash( $_POST['authcode'] ) );

		// Transient data
		$hash     = get_transient( sprintf( self::KEY_OTP, $userId ) );
		$attempts = (int) get_transient( sprintf( self::KEY_ATTEMPT, $userId ) );

		if ( false === $hash ) {
			$this->_loadForm( [
				'action'   => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
				'template' => 'recovery-login.php',
				'uid'      => $userId,
				'error'    => __( 'Verification code expired – please request a new code.', ADDONS_TEXTDOMAIN ),
			] );
		}

		// Compare
		if ( ! hash_equals( $hash, wp_hash( $entered ) ) ) {

			// +1 failed attempt
			$attempts ++;
			set_transient( sprintf( self::KEY_ATTEMPT, $userId ), $attempts, self::OTP_LIFETIME );

			// Too many attempts?
			if ( $attempts >= self::MAX_ATTEMPTS ) {
				$this->_clearOtpData( $userId );
				wp_safe_redirect( add_query_arg( '_error', 'max_attempts', wp_login_url() ) );
				exit;
			}

			$this->_loadForm( [
				'action'   => esc_url( add_query_arg( 'action', self::ACTION_VALIDATE, wp_login_url() ) ),
				'template' => 'recovery-login.php',
				'uid'      => $userId,
				'error'    => sprintf(
					__( 'Invalid code. You have %1$d of %2$d attempts left.', ADDONS_TEXTDOMAIN ),
					self::MAX_ATTEMPTS - $attempts,
					self::MAX_ATTEMPTS
				),
			] );
		}

		// Success → log the user in again and redirect
		$this->_loginUser( $userId );
		$this->_interimCheck();

		$redirect = ! empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : get_admin_url();
		wp_safe_redirect( esc_url_raw( wp_unslash( $redirect ) ) );
	}

	/**
	 * Replace default login messages with OTP-specific errors
	 *
	 * @param string $message
	 *
	 * @return string
	 */
	public function otpFailMessage( string $message ): string {
		if ( empty( $_GET['_error'] ) ) {
			return $message;
		}

		return match ( $_GET['_error'] ) {
			'email' => '<div id="login_error" class="notice notice-error"><p><strong>Error:</strong> Unable to send OTP e-mail.</p></div>',
			'max_attempts' => '<div id="login_error" class="notice notice-error"><p><strong>Error:</strong> Too many attempts.</p></div>',
			default => $message,
		};
	}

	/* ---------- INTERNAL ------------------------------------------------ */

	/**
	 * Log user in again & set OTP cookie
	 *
	 * @param int $userId
	 *
	 * @return void
	 * @throws RandomException
	 */
	private function _loginUser( int $userId = 0 ): void {
		wp_set_auth_cookie( wp_unslash( $userId ), (bool) ( $_POST['rememberme'] ?? 0 ) );

		$this->_clearOtpData( $userId );
		$this->_setOtpCookie( $userId );
	}

	/**
	 * Show success page for interim-login iframe
	 *
	 * @return void
	 */
	private function _interimCheck(): void {
		if ( empty( $_REQUEST['interim-login'] ) ) {
			return;
		}

		global $interim_login;
		$interim_login = 'success';

		login_header( '', '<p class="message">' . __( 'You have logged in successfully.', ADDONS_TEXTDOMAIN ) . '</p>' );
		echo '</div>';
		do_action( 'login_footer' );
		echo '</body></html>';
		exit;
	}

	/**
	 * Send an OTP e-mail if the cool-down has passed.
	 *
	 * @param \WP_User $user
	 *
	 * @return bool|null
	 * @throws RandomException
	 */
	private function _maybeSendOtpEmail( \WP_User $user ): ?bool {
		$last_sent = (int) get_user_meta( $user->ID, self::META_LASTSEND, true );
		if ( $last_sent && ( current_time( 'timestamp' ) - $last_sent ) < self::RESEND_INTERVAL ) {
			return null;
		}

		// generate OTP
		$otp = str_pad( (string) random_int( 0, ( 10 ** self::OTP_DIGITS ) - 1 ), self::OTP_DIGITS, '0', STR_PAD_LEFT );

		$sent = wp_mail(
			$user->user_email,
			__( 'Your One-Time OTP', ADDONS_TEXTDOMAIN ),
			sprintf(
				__( "Hello %s,\n\nYour OTP is: %s\nThis code will expire in 5 minutes.\n\nIf you didn't request this login, please ignore this email.", ADDONS_TEXTDOMAIN ),
				$user->user_login,
				$otp
			)
		);

		if ( ! $sent ) {
			return false;
		}

		// Success → store cool-down and transients
		update_user_meta( $user->ID, self::META_LASTSEND, current_time( 'timestamp' ) );
		set_transient( sprintf( self::KEY_OTP, $user->ID ), wp_hash( $otp ), self::OTP_LIFETIME );
		set_transient( sprintf( self::KEY_ATTEMPT, $user->ID ), 0, self::OTP_LIFETIME );

		return true;
	}

	/**
	 * Display the OTP authentication forms.
	 *
	 * @param $args
	 *
	 * @return void
	 */
	private function _loadForm( $args ): void {
		if ( empty( $args['template'] ) ) {
			return;
		}

		// Path to the form template.
		$path = __DIR__ . '/' . $args['template'];
		if ( ! file_exists( $path ) ) {
			return;
		}

		$args = array_merge( $args, [
			'otp_digits'      => self::OTP_DIGITS,
			'resend_interval' => self::RESEND_INTERVAL,
			'interim_login'   => ( isset( $_REQUEST['interim-login'] ) ) ? filter_var( wp_unslash( $_REQUEST['interim-login'] ), FILTER_VALIDATE_BOOLEAN ) : false,
			'redirect_to'     => isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : admin_url(),
		] );

		// Include the login header if the function doesn't exist.
		if ( ! function_exists( 'login_header' ) ) {
			include_once ABSPATH . 'wp-login.php';
		}

		// Include the template.php if the function doesn't exist.
		if ( ! function_exists( 'submit_button' ) ) {
			require_once ABSPATH . '/wp-admin/includes/template.php';
		}

		login_header();
		include_once $path;
		login_footer();
		exit;
	}

	/**
	 * Create a secure cookie for device-not-challenge
	 *
	 * @param int $userId
	 *
	 * @return void
	 * @throws RandomException
	 */
	private function _setOtpCookie( int $userId = 0 ): void {
		$token = bin2hex( random_bytes( 22 ) );
		update_user_meta( $userId, self::META_TOKEN, $token );

		setcookie(
			'_otp_dnc_cookie',
			$userId . '|' . $token,
			[
				'expires'  => time() + self::COOKIE_LIFETIME,
				'path'     => defined( 'COOKIEPATH' ) ? COOKIEPATH : '/',
				'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	private function _checkOtpCookie( \WP_User $user ): bool {
		if ( empty( $_COOKIE['_otp_dnc_cookie'] ) ) {
			return false;
		}

		[ $uid, $token ] = explode( '|', $_COOKIE['_otp_dnc_cookie'], 2 );

		return (int) $uid === $user->ID &&
		       $token === get_user_meta( $user->ID, self::META_TOKEN, true );
	}

	/**
	 * @param int $userId
	 *
	 * @return void
	 */
	private function _clearOtpData( int $userId = 0 ): void {
		delete_transient( sprintf( self::KEY_OTP, $userId ) );
		delete_transient( sprintf( self::KEY_ATTEMPT, $userId ) );
		delete_user_meta( $userId, self::META_LASTSEND );
		delete_user_meta( $userId, self::META_TOKEN );

		setcookie( '_otp_dnc_cookie', '', time() - 3600, '/' );
	}

	/**
	 * True if plugin enabled via option
	 *
	 * @return bool
	 */
	private function _isEnabled(): bool {
		$opt = \Addons\Helper::getOption( 'login_security__options' );

		return ! empty( $opt['login_otp_verification'] );
	}

	/**
	 * Roles that should be forced to use Email-OTP.
	 *
	 * @return mixed
	 */
	private function _otpUserRoles(): mixed {
		$roles = [
			'editor',
			'administrator',
		];

		return apply_filters( 'loginotp_security_user_roles', $roles );
	}
}
