<?php

namespace Addons\LoginSecurity;

\defined( 'ABSPATH' ) || exit;

final class LoginOtpVerification {

	const OTP_META_PENDING = '_email_otp_pending';
	const OTP_META_KEY = '_email_otp_code';
	const OTP_EXPIRE_META_KEY = '_email_otp_expire';
	const OTP_ATTEMPTS_META_KEY = '_email_otp_attempts';
	const OTP_LAST_SENT_META_KEY = '_email_otp_last_sent';
	const OTP_MAX_ATTEMPTS = 5;
	const OTP_VALIDITY_SECONDS = 300; // 5 minutes
	const OTP_RESEND_INTERVAL = 300; // 5 minutes

	// ------------------------------------------

	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueAssets' ] );
		add_action( 'login_form', [ $this, 'captureLogin' ] );


		add_action( 'wp_logout', [ $this, 'clearOtpData' ] );
		add_action( 'wp_login', [ $this, 'clearOtpData' ], 10, 2 );
		add_action( 'admin_post_nopriv_resend_otp', [ $this, 'handleResendOtp' ] );
		add_action( 'admin_footer', [ $this, 'blockPasswordSave' ] );
	}

	// ------------------------------------------

	public function init(): void {
		add_rewrite_rule( '^otp-verification/?$', 'index.php?lov_otp=1', 'top' );
		add_filter( 'query_vars', static function ( $vars ) {
			$vars[] = 'lov_otp';

			return $vars;
		} );
	}

	// ------------------------------------------

	public function enqueueAssets(): void {
		if ( (int) get_query_var( 'lov_otp' ) === 1 ) {
			\Addons\Asset::enqueueScript( 'login-otp-js', ADDONS_URL . 'assets/js/login-otp.js', [], null, true, [ 'module', 'defer' ] );
		}
	}

	// ------------------------------------------

	public function captureLogin(): void {
		if ( ! empty( $_POST['log'] ) && ! empty( $_POST['pwd'] ) ) {
			$user = wp_authenticate( sanitize_text_field( $_POST['log'] ), sanitize_text_field( $_POST['pwd'] ) );

			if ( is_wp_error( $user ) ) {
				return;
			}

			// Mark the user as pending and redirect to the OTP page
			update_user_meta( $user->ID, '_lov_pending', true );
			$this->_generate_otp( $user->ID );

			// Log user in with a session flag (not fully authenticated yet)
			wp_set_current_user( $user->ID );
			wp_redirect( home_url( '/otp-verification' ) );
			exit;
		}
	}


	// ------------------------------------------

	public function blockPasswordSave(): void {
		echo <<<HTML
		<script>
			document.querySelectorAll("input[name='pwd']").forEach(el => {
				el.setAttribute("autocomplete", "off");
				el.setAttribute("readonly", true);
				setTimeout(() => el.removeAttribute("readonly"), 500);
			});
		</script>
		<style>
			input[name="pwd"]::-webkit-credentials-auto-fill-button {
				display: none !important;
			}
		</style>
		HTML;
	}

	// ------------------------------------------
}
