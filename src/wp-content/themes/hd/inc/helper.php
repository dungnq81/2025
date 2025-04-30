<?php
/**
 * Helper functions
 *
 * @author Gaudev
 */

\defined( 'ABSPATH' ) || die;

// --------------------------------------------------
// Custom functions
// --------------------------------------------------

if ( ! function_exists( '_is_valid_phone' ) ) {
	/**
	 * @param $phone
	 *
	 * @return bool
	 */
	function _is_valid_phone( $phone ): bool {
		if ( ! is_string( $phone ) || trim( $phone ) === '' ) {
			return false;
		}

		//$pattern = '/^\(?\+?(0|84?)\)?[\s.-]?(3[2-9]|5[689]|7[06-9]|8[0-689]|9[0-4|6-9])(\d{7}|\d[\s.-]?\d{3}[\s.-]?\d{3})$/';
		$pattern = '/^\(?\+?(0|84)\)?[\s.\-]?(3[2-9]|5[689]|7[06-9]|(?:8[0-689]|87)|9[0-4|6-9])(\d{7}|\d[\s.\-]?\d{3}[\s.\-]?\d{3})$/';

		return preg_match( $pattern, $phone ) === 1;
	}
}

// --------------------------------------------------

if ( ! function_exists( '_sanitize_image' ) ) {
	/**
	 * @param $file
	 *
	 * @return mixed
	 */
	function _sanitize_image( $file ): mixed {
		$mimes = [
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'bmp'          => 'image/bmp',
			'webp'         => 'image/webp',
			'tif|tiff'     => 'image/tiff',
			'ico'          => 'image/x-icon',
			'svg'          => 'image/svg+xml',
		];

		// check a file type from the file name
		$file_ext = wp_check_filetype( $file, $mimes );

		// if a file has a valid mime type, return it, otherwise return default
		return $file_ext['ext'] ? $file : '';
	}
}

// --------------------------------------------------

if ( ! function_exists( '_remove_cookie' ) ) {
	/**
	 * @param string $name
	 * @param bool $httponly
	 *
	 * @return void
	 */
	function _remove_cookie( string $name, bool $httponly = false ): void {
		setcookie( $name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), $httponly );
	}
}

// --------------------------------------------------

if ( ! function_exists( '_add_cookie' ) ) {
	/**
	 * @param string $name
	 * @param $value
	 * @param int $minute
	 * @param bool $httponly
	 *
	 * @return void
	 */
	function _add_cookie( string $name, $value, int $minute = 1440, bool $httponly = false ): void {
		if ( is_scalar( $value ) ) {
			setcookie( $name, $value, time() + $minute * MINUTE_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), $httponly );
		}
	}
}

// --------------------------------------------------

if ( ! function_exists( '_in_array_checked' ) ) {
	/**
	 * Conditionally adds an HTML attribute based on array membership.
	 *
	 * @param array $checked_arr
	 * @param $current
	 * @param bool $display
	 * @param string $type
	 *
	 * @return string|null
	 */
	function _in_array_checked( array $checked_arr, $current, bool $display = true, string $type = 'checked' ): ?string {
		$type   = preg_match( '/^[a-zA-Z0-9\-]+$/', $type ) ? $type : 'checked';
		$result = in_array( $current, $checked_arr, false ) ? " $type='$type'" : '';

		// Echo or return the result
		if ( $display ) {
			echo $result;

			return null;
		}

		return $result;
	}
}

// --------------------------------------------------

if ( ! function_exists( '_in_array_toggle_class' ) ) {
	/**
	 * Conditionally toggles an HTML class based on the presence of a key in an array.
	 *
	 * @param array $arr
	 * @param $key
	 * @param string $html_class
	 * @param true $display
	 *
	 * @return string|null
	 */
	function _in_array_toggle_class( array $arr, $key, string $html_class = '!hidden', true $display = true ): ?string {
		$html_class = trim( $html_class );
		if ( empty( $html_class ) || preg_match( '/[^a-zA-Z0-9\-_ ]/', $html_class ) ) {
			// Invalid HTML class; return or echo an empty string
			if ( $display ) {
				echo '';

				return null;
			}

			return '';
		}

		$class = in_array( $key, $arr, false ) ? ' ' . $html_class : '';
		if ( $display ) {
			echo $class;

			return null;
		}

		return $class;
	}
}

// --------------------------------------------------

if ( ! function_exists( '_recaptcha_verify' ) ) {
	/**
	 * @param string $version
	 * @param string $recaptcha_response
	 *
	 * @return false|mixed
	 * @throws \JsonException
	 */
	function _recaptcha_verify( string $version, string $recaptcha_response ): mixed {
		$recaptcha_options = \HD_Helper::getOption( 'recaptcha__options' );
		if ( ! $recaptcha_options ) {
			return false;
		}

		// reCaptcha v2
		if ( 'v2' === $version ) {
			$recaptchaResponse = $recaptcha_response;
			$secretKey         = $recaptcha_options['recaptcha_v2_secret_key'] ?? '';
			$verifyUrl         = 'https://www.google.com/recaptcha/api/siteverify';

			$options = [
				'http' => [
					'method'  => 'POST',
					'header'  => 'Content-Type: application/x-www-form-urlencoded',
					'content' => http_build_query( [
						'secret'   => $secretKey,
						'response' => $recaptchaResponse,
					] ),
				],
			];

			// Send the verification request
			$result = file_get_contents( $verifyUrl, false, stream_context_create( $options ) );

			return json_decode( $result, false, 512, JSON_THROW_ON_ERROR );
		}

		// reCaptcha v3
		if ( 'v3' === $version ) {
			$secretKey = $recaptcha_options['recaptcha_v3_secret_key'] ?? '';
			$score     = $recaptcha_options['recaptcha_v3_score'] ?? 0.5;

			return false;
		}

		return false;
	}
}
