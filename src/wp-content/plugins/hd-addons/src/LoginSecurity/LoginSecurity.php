<?php

namespace Addons\LoginSecurity;

use Addons\Asset;
use Addons\CSS;
use Addons\Helper;

\defined( 'ABSPATH' ) || exit;

final class LoginSecurity {
	// ------------------------------------------------------

	public function __construct() {
		add_action( 'login_enqueue_scripts', [ $this, 'loginEnqueueAssets' ], 31 );
		add_filter( 'login_headertext', [ $this, 'loginHeadertext' ] );
		add_filter( 'login_headerurl', [ $this, 'loginHeaderurl' ] );

		// csrf login-form
		add_action( 'login_form', [ $this, 'addCsrfLoginForm' ] );
		add_filter( 'authenticate', [ $this, 'verifyCsrfLogin' ], 30, 3 );

		// csrf lost-password
		add_action( 'lostpassword_form', [ $this, 'addCsrfLostpasswordForm' ] );
		add_action( 'lostpassword_post', [ $this, 'verifyCsrfLostpasswordPost' ], 30, 2 );

		( new LoginRestricted() );
		( new LoginIllegalUsers() );
		( new LoginAttempts() );
		( new LoginOtpVerification() );
	}

	// -------------------------------------------------------------

	/**
	 * @return void
	 */
	public function loginEnqueueAssets(): void {
		$version = Helper::version();

		Asset::enqueueStyle( 'login-css', ADDONS_URL . 'assets/css/login.css', [], $version );
		Asset::enqueueScript( 'login-js', ADDONS_URL . 'assets/js/login.js', [ 'jquery' ], $version, true, [ 'module', 'defer' ] );

		$default_logo = '';
		$default_bg   = '';

		//$default_logo = ADDONS_URL . 'assets/img/logo.png';
		//$default_bg   = ADDONS_URL . 'assets/img/login-bg.jpg';

		// scripts / styles
		$logo     = esc_url_raw( Helper::getThemeMod( 'login_page_logo_setting' ) ?: $default_logo );
		$bg_img   = esc_url_raw( Helper::getThemeMod( 'login_page_bgimage_setting' ) ?: $default_bg );
		$bg_color = sanitize_hex_color( Helper::getThemeMod( 'login_page_bgcolor_setting' ) );

		$css = new CSS();

		if ( $bg_img ) {
			$css->set_selector( 'body.login' )
			    ->add_property( 'background-image', "url({$bg_img})" );
		}

		if ( $bg_color ) {
			$css->set_selector( 'body.login' )
			    ->add_property( 'background-color', $bg_color )
			    ->set_selector( 'body.login:before' )
			    ->add_property( 'background', 'none' )
			    ->add_property( 'opacity', 1 );
		}

		if ( $logo ) {
			$css->set_selector( 'body.login #login h1 a' )
			    ->add_property( 'background-image', "url({$logo})" );
		}

		if ( $inline = $css->css_output() ) {
			Asset::inlineStyle( 'login-css', $inline );
		}
	}

	// -------------------------------------------------------------

	/**
	 * @return mixed|string|null
	 */
	public function loginHeadertext(): mixed {
		return Helper::getThemeMod( 'login_page_headertext_setting' ) ?: get_bloginfo( 'name' );
	}

	// -------------------------------------------------------------

	/**
	 * @return mixed|string|null
	 */
	public function loginHeaderurl(): mixed {
		return Helper::getThemeMod( 'login_page_headerurl_setting' ) ?: site_url( '/' );
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function addCsrfLoginForm(): void {
		echo Helper::CSRFToken( 'login_csrf_token' );
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
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return $user;
		}

		if ( empty( $username ) ) {
			return $user;
		}

		if ( empty( $_POST['_csrf_token'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_csrf_token'] ), 'login_csrf_token' ) ) {
			return new \WP_Error( 'csrf_error', __( 'Invalid CSRF token. Please try again.' ) );
		}

		return $user;
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function addCsrfLostpasswordForm(): void {
		echo Helper::CSRFToken( 'lostpassword_csrf_token' );
	}

	// ------------------------------------------------------

	/**
	 * @param \WP_Error $errors
	 * @param $user_data
	 *
	 * @return void
	 */
	public function verifyCsrfLostpasswordPost( \WP_Error $errors, $user_data ): void {
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}

		if ( empty( $_POST['_csrf_token'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_csrf_token'] ), 'lostpassword_csrf_token' ) ) {
			$errors->add( 'csrf_error', __( 'Invalid CSRF token, please try again.', TEXT_DOMAIN ) );
		}
	}
}
