<?php

namespace Addons\LoginSecurity;

use Random\RandomException;

\defined( 'ABSPATH' ) || exit;

/**
 * Simple Email-OTP Login
 *
 * @author Gaudev
 */
class LoginOtpVerification {
	/** Template constants for transient keys */
	public const TRANSIENT_OTP = '_otp_%d';
	public const TRANSIENT_ATTEMPTS = '_otp_try_%d';

	/** Behaviour constants */
	public const TTL = 5 * MINUTE_IN_SECONDS; // 5 minutes
	public const TTL_PENDING = 5 * MINUTE_IN_SECONDS; // 5 minutes
	public const TTL_COOKIE_EXPIRES = 2 * HOUR_IN_SECONDS; // 2 hours
	public const MAX_ATTEMPTS = 5;
	public const LOGIN_ACTION = '_otp_validate';

	// ------------------------------------------

	public function __construct() {
		if ( ! $this->_isEnabled() ) {
			return;
		}

		add_action( 'login_enqueue_scripts', [ $this, 'enqueueAssets' ], 32 );
		add_action( 'wp_login', [ $this, 'initOtp' ], 10, 2 ); // Fires after the user has successfully logged in.
		add_filter( 'login_message', [ $this, 'otpFailMessage' ] );
		add_action( 'login_form_' . self::LOGIN_ACTION, [ $this, 'validateOtpLogin' ] );
	}

	// ------------------------------------------

	public function enqueueAssets(): void {
		\Addons\Asset::enqueueScript( 'login-otp-js', ADDONS_URL . 'assets/js/login-otp.js', [ 'login-js' ], null, true, [
			'module',
			'defer'
		] );
	}

	// ------------------------------------------

	/**
	 * Initialize the OTP verification, fires after the user has successfully logged in.
	 *
	 * @param string $user_login Username.
	 * @param \WP_User $user WP_User object of the logged-in user.
	 *
	 * @return void
	 * @throws RandomException
	 */
	public function initOtp( string $user_login, \WP_User $user ): void {
		if ( empty( array_intersect( $this->_otpUserRoles(), $user->roles ) ) ) {
			return;
		}

		// Valid OTP cookie.
		if ( true === $this->_checkOtpCookie( $user_login, $user ) ) {
			return;
		}

		// Remove the auth cookie.
		wp_clear_auth_cookie();

		$result = $this->_maybeSendOtpEmail( $user );
		if ( $result === false ) {
			$this->_clearOtpData( $user->ID );
			wp_safe_redirect( add_query_arg( '_error', 'email', wp_login_url() ) );
			exit;
		}

		// Load the OTP form.
		$this->_loadForm( [
				'action'   => esc_url( add_query_arg( 'action', self::LOGIN_ACTION, wp_login_url() ) ),
				'template' => 'recovery-login.php',
				'uid'      => $user->ID,
				'error'    => '',
			]
		);
	}

	// ------------------------------------------

	/**
	 * @throws RandomException
	 */
	public function validateOtpLogin(): void {
		if ( empty( $_POST['authcode'] ) ||
		     empty( $_POST['uid'] ) ||
		     empty( $_POST['_csrf_token'] ) ||
		     ! wp_verify_nonce( wp_unslash( $_POST['_csrf_token'] ), 'otp_csrf_token' )
		) {
			return;
		}

		$user_id = (int) $_POST['uid'];

		// OTP hash and failed attempts
		$stored_hash = get_transient( sprintf( self::TRANSIENT_OTP, $user_id ) );
		$attempts    = (int) get_transient( sprintf( self::TRANSIENT_ATTEMPTS, $user_id ) );

		// expired transient
		if ( false === $stored_hash ) {
			$this->_loadForm( [
				'action'   => esc_url( add_query_arg( 'action', self::LOGIN_ACTION, wp_login_url() ) ),
				'template' => 'recovery-login.php',
				'uid'      => $user_id,
				'error'    => __( 'Verification code expired – please request a new code.', ADDONS_TEXTDOMAIN ),
			] );
		}

		$entered  = preg_replace( '/\D/', '', wp_unslash( $_POST['authcode'] ) );
		$is_match = hash_equals( $stored_hash, wp_hash( $entered ) );

		if ( ! $is_match ) {

			// Increase the number of failed attempts and save it
			$attempts ++;
			set_transient( sprintf( self::TRANSIENT_ATTEMPTS, $user_id ), $attempts, self::TTL );

			// Too many attempts?
			if ( $attempts >= self::MAX_ATTEMPTS ) {
				$this->_clearOtpData( $user_id );
				wp_safe_redirect( add_query_arg( '_error', 'max_attempts', wp_login_url() ) );
				exit;
			}

			$this->_loadForm( [
				'action'   => esc_url( add_query_arg( 'action', self::LOGIN_ACTION, wp_login_url() ) ),
				'template' => 'recovery-login.php',
				'uid'      => $user_id,
				'error'    => sprintf( __( 'Invalid code. You have %1$d of %2$d attempts left.', ADDONS_TEXTDOMAIN ), self::MAX_ATTEMPTS - $attempts, self::MAX_ATTEMPTS
				),
			] );
		}

		// Log in the user.
		$this->_loginUser( $user_id );

		// Interim login.
		$this->_interimCheck();

		// Get the redirect url.
		$redirect_url = ! empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : get_admin_url();
		wp_safe_redirect( esc_url_raw( wp_unslash( $redirect_url ) ) );
	}

	// ------------------------------------------

	/**
	 * @param $message
	 *
	 * @return mixed|string
	 */
	public function otpFailMessage( $message ): mixed {
		if ( empty( $_GET['_error'] ) ) {
			return $message;
		}

		if ( 'email' === $_GET['_error'] ) {
			return '<div id="login_error" class="notice notice-error"><p><strong>Error:</strong> Unable to send OTP e-mail.</p></div>';
		}

		if ( 'max_attempts' === $_GET['_error'] ) {
			return '<div id="login_error" class="notice notice-error"><p><strong>Error:</strong> Too many attempts.</p></div>';
		}

		return $message;
	}

	// ------------------------------------------

	/**
	 * @param $user_id
	 *
	 * @return void
	 * @throws RandomException
	 */
	private function _loginUser( $user_id ): void {
		// Set the auth cookie.
		wp_set_auth_cookie( wp_unslash( $user_id ), (int) wp_unslash( $_POST['rememberme'] ) );

		// Clear and setup OTP cookie
		$this->_clearOtpData( $user_id );
		$this->_setOtpCookie( $user_id );
	}

	// ------------------------------------------

	private function _interimCheck(): void {
		global $interim_login;

		$interim_login = ( isset( $_REQUEST['interim-login'] ) ) ? filter_var( $_REQUEST['interim-login'], FILTER_VALIDATE_BOOLEAN ) : false;
		if ( false === $interim_login ) {
			return;
		}

		$interim_login = 'success';
		login_header( '', '<p class="message">' . __( 'You have logged in successfully.', ADDONS_TEXTDOMAIN ) . '</p>' );
		?>
        </div>
		<?php do_action( 'login_footer' ); ?>
        </body></html>
		<?php
		exit;
	}

	// ------------------------------------------

	/**
	 * Send an OTP e-mail if the cool-down has passed.
	 *
	 * @param \WP_User $user
	 *
	 * @return bool|null
	 * @throws RandomException
	 */
	private function _maybeSendOtpEmail( \WP_User $user ): ?bool {
		// Cool-down check (5 minutes)
		$last_sent = (int) get_user_meta( $user->ID, '_otp_ttl_pending', true );
		if ( $last_sent && ( current_time( 'timestamp' ) - $last_sent ) < self::TTL_PENDING ) {
			return null;
		}

		// build OPT & send email
		$otp  = str_pad( random_int( 0, 999999 ), 6, '0', STR_PAD_LEFT );
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
		update_user_meta( $user->ID, '_otp_ttl_pending', current_time( 'timestamp' ) );
		set_transient( sprintf( self::TRANSIENT_OTP, $user->ID ), wp_hash( $otp ), self::TTL );
		set_transient( sprintf( self::TRANSIENT_ATTEMPTS, $user->ID ), 0, self::TTL );

		return true;
	}

	// ------------------------------------------

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
				'interim_login' => ( isset( $_REQUEST['interim-login'] ) ) ? filter_var( wp_unslash( $_REQUEST['interim-login'] ), FILTER_VALIDATE_BOOLEAN ) : false,
				'redirect_to'   => isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : admin_url(),
			]
		);

		// Include the login header if the function doesn't exist.
		if ( ! function_exists( 'login_header' ) ) {
			include_once ABSPATH . 'wp-login.php';
		}

		// Include the template.php if the function doesn't exist.
		if ( ! function_exists( 'submit_button' ) ) {
			require_once ABSPATH . '/wp-admin/includes/template.php';
		}

		login_header();

		// Include the template.
		include_once $path;

		login_footer();
		exit;
	}

	// ------------------------------------------

	/**
	 * @param $user_id
	 *
	 * @return void
	 * @throws RandomException
	 */
	private function _setOtpCookie( $user_id ): void {
		// Generate random token.
		$token = bin2hex( random_bytes( 22 ) );

		// Assign the token to the user.
		update_user_meta( $user_id, '_otp_dnc_token', $token );

		$difference            = '';
		$domain                = $_SERVER['SERVER_NAME'];
		$domain_with_subfolder = get_home_url();
		$protocol              = ! empty( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
		$escaped_domain        = preg_quote( $protocol . $domain, '/' );

		if ( get_site_url() !== get_home_url() ) {
			$domain                = get_home_url();
			$domain_with_subfolder = get_site_url();
			$escaped_domain        = preg_quote( $domain, '/' );
		}

		if ( preg_match( '/^' . $escaped_domain . '(.*)$/', $domain_with_subfolder, $matches ) ) {
			$difference = $matches[1];
		}

		$domain = empty( COOKIE_DOMAIN ) ? $_SERVER['SERVER_NAME'] : COOKIE_DOMAIN;

		$slug = '/wp-login.php';
		// Check if the WPS Hide Login Plugin is active.
		if ( \class_exists( 'WPS\WPS_Hide_Login\Plugin' ) ) {
			$slug = \Addons\Helper::getOption( 'whl_page', '' ) ?: '/login';
		}

		// Set the OTP cookie.
		setcookie(
			'_otp_dnc_cookie',
			$user_id . '|' . $token,
			[
				'expires'  => time() + self::TTL_COOKIE_EXPIRES,
				'path'     => $difference . $slug,
				'domain'   => $domain,
				'secure'   => true,
				'httponly' => true,
				'samesite' => 'Lax',
			]
		);
	}

	// ------------------------------------------

	/**
	 * @param string $user_login
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	private function _checkOtpCookie( string $user_login, \WP_User $user ): bool {
		// OTP user cookie name.
		$otp_user_cookie = '_otp_dnc_cookie';

		// Bail if the cookie doesn't exist.
		if ( ! isset( $_COOKIE[ $otp_user_cookie ] ) ) {
			return false;
		}

		// Parse the cookie.
		$cookie_data = explode( '|', $_COOKIE[ $otp_user_cookie ] );

		return $user->ID === (int) $cookie_data[0] &&
		       get_user_meta( $cookie_data[0], '_otp_dnc_token', true ) === $cookie_data[1];
	}

	// ------------------------------------------

	/**
	 * @param $user_id
	 *
	 * @return void
	 */
	private function _clearOtpData( $user_id ): void {
		delete_transient( sprintf( self::TRANSIENT_OTP, $user_id ) );
		delete_transient( sprintf( self::TRANSIENT_ATTEMPTS, $user_id ) );

		delete_user_meta( $user_id, '_otp_ttl_pending' );
		delete_user_meta( $user_id, '_otp_dnc_token' );
	}

	// ------------------------------------------

	/**
	 * @return bool
	 */
	private function _isEnabled(): bool {
		$_options   = \Addons\Helper::getOption( 'login_security__options' );
		$is_enabled = $_options['login_otp_verification'] ?? '';

		return ! empty( $is_enabled );
	}

	// ------------------------------------------

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

		return apply_filters( 'seol_otp_security_user_roles', $roles );
	}

	// ------------------------------------------
}
