<?php

namespace HD\API\Endpoints;

use HD\API\AbstractAPI;

\defined( 'ABSPATH' ) || die;

/**
 * Class SingleEndpoints
 *
 * Registers and handles all REST API endpoints for single resources in WordPress
 * (e.g., posts, pages, attachments).
 *
 * @package HD\API\Endpoints
 */
class SingleEndpoints extends AbstractAPI {
	/** ---------------------------------------- */

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function registerRestRoutes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/single/track_views',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'trackViewsCallback' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/** ---------------------------------------- */

	/**
	 * @param $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function trackViewsCallback( $request ): \WP_Error|\WP_REST_Response {
		$data = $request->get_params();
		if ( ! self::BYPASS_NONCE ) {
			$nonce = $request->get_header( 'X-WP-Nonce' );
			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				$result = [
					'success' => false,
					'message' => 'Invalid CSRF token.'
				];

				return self::sendResponse( $result, 403 );
			}
		}

		$id = isset( $data['id'] ) ? absint( $data['id'] ) : null;
		if ( ! $id ) {
			$result = [
				'success' => false,
				'message' => 'Invalid post-ID.'
			];

			return self::sendResponse( $result, 0 );
		}

		$last_view_time = get_post_meta( $id, '_last_view_time', true );
		$current_time   = current_time( 'U', 0 );
		$views          = get_post_meta( $id, '_post_views', true );

		if ( ! $last_view_time || ( $current_time - (int) $last_view_time ) > 300 ) { // 300 s
			$views = $views ? ( (int) $views + 1 ) : 1;

			update_post_meta( $id, '_post_views', $views );
			update_post_meta( $id, '_last_view_time', $current_time );
		}

		$result = [
			'success' => true,
			'time'    => $current_time,
			'views'   => $views,
			'date'    => \HD_Helper::humanizeTime( $id ),
		];

		return self::sendResponse( $result, 200 );
	}
}
