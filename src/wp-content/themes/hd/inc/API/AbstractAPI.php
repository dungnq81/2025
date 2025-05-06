<?php

namespace HD\API;

\defined( 'ABSPATH' ) || die;

abstract class AbstractAPI {
	public const BYPASS_NONCE = false; // bypass wpnonce, recaptcha .v.v...
	public const REST_NAMESPACE = 'wp/v2';
//	public const REST_MAX_FILE_SIZE_UPLOAD = 10 * 1024 * 1024; // 10M
//	public const REST_ALLOW_TYPES_UPLOAD = [
//		'application/pdf'
//	];

	/** ---------------------------------------- */

	abstract public function registerRestRoutes();

	/** ---------------------------------------- */

	/**
	 * @param string $route
	 *
	 * @return string
	 */
	public function restApiUrl( string $route = '' ): string {
		return esc_url_raw( rest_url( self::REST_NAMESPACE . '/' . $route ) );
	}

	/** ---------------------------------------- */

	/**
	 * @param array $result
	 * @param int $status
	 * @param array $data
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function sendResponse( array $result = [], int $status = 1, array $data = [] ): \WP_Error|\WP_REST_Response {
		// Prepare the status code, based on the optimization result.
		$status_code      = ( 1 === $status ) ? 200 : $status;
		$result['status'] = $status_code;

		if ( ! empty( $data ) ) {
			$result['data'] = $data;
		}

		if ( ! isset( $result['success'] ) ) {
			$result['success'] = true;
		}

		if ( ! isset( $result['errorCode'] ) ) {
			$result['errorCode'] = 0;
		}

		$response = rest_ensure_response( $result );
		$response->set_status( $status_code );

		if ( ! headers_sent() ) {
			$response->header( 'Content-Type', 'application/json; charset=' . get_option( 'blog_charset' ) );
		}

		return $response;
	}
}
