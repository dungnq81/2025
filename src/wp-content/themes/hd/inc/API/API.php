<?php

declare( strict_types=1 );

namespace HD\API;

use HD\Utilities\Traits\Singleton;

/**
 * API Class
 *
 * @author Gaudev
 */
final class API extends AbstractAPI {
	use Singleton;

	private array $endpointClasses = [];

	// List of post-types to hide from the REST API
	private array $blockedPostTypes = [
		'post',
		'page',
		'attachment'
	];

	// List of custom routes allowed to pass through restPreDispatch
	private array $allowedRoutes = [
		'single/track_views',
		'global/lighthouse',
	];

	/** ---------------------------------------- */

	private function init(): void {
		add_action( 'init', [ $this, 'initRestClasses' ] );

		add_action( 'rest_api_init', [ $this, 'registerRestRoutes' ] );
		add_action( 'rest_api_init', [ $this, 'disableDefaultPostTypes' ] );

		add_filter( 'rest_pre_dispatch', [ $this, 'restPreDispatch' ], 10, 3 );
		add_filter( 'rest_endpoints', [ $this, 'restEndpoints' ] );
	}

	/** ---------------------------------------- */

	public function registerRestRoutes(): void {
		foreach ( $this->endpointClasses as $api ) {
			if ( method_exists( $api, 'registerRestRoutes' ) ) {
				$api->registerRestRoutes();
			}
		}
	}

	/** ---------------------------------------- */

	/**
	 * Automatically initialize classes in the Utilities/API directory.
	 *
	 * @return void
	 */
	public function initRestClasses(): void {
		$directory = __DIR__ . '/Endpoints/*.php';
		foreach ( glob( $directory, GLOB_NOSORT ) as $file ) {
			$class_name = '\\HD\\API\\Endpoints\\' . basename( $file, '.php' );

			if ( class_exists( $class_name ) ) {
				$this->endpointClasses[] = new $class_name();
			}
		}
	}

	/** ---------------------------------------- */

	/**
	 * @param $pre
	 * @param \WP_REST_Server $server
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|null
	 */
	public function restPreDispatch( $pre, \WP_REST_Server $server, \WP_REST_Request $request ): ?\WP_Error {
		$route  = ltrim( $request->get_route(), '/' );
		$routes = $server->get_routes();

		$allowedFull = array_map(
			static fn( $r ) => self::REST_NAMESPACE . '/' . $r,
			$this->allowedRoutes
		);

		if ( ! in_array( $route, $allowedFull, true ) ) {
			return $pre;
		}

		foreach ( (array) $routes[ '/' . $route ] as $args ) {
			foreach ( (array) $args['methods'] as $method => $enabled ) {
				if ( $enabled && strtoupper( $method ) === strtoupper( $request->get_method() ) ) {
					return $pre;
				}
			}
		}

		return new \WP_Error(
			'rest_forbidden',
			'Access denied for this route.',
			[ 'status' => 403 ]
		);
	}

	/** ---------------------------------------- */

	/**
	 * Disable REST API access for default post-types
	 *
	 * @return void
	 */
	public function disableDefaultPostTypes(): void {
		foreach ( $this->blockedPostTypes as $type ) {
			add_filter( "rest_{$type}_query", [ $this, 'forceEmptyRestQuery' ], 10, 2 );
			add_filter( "rest_{$type}_collection", [ $this, 'forceEmptyRestCollection' ], 10, 2 );
			add_filter( "rest_prepare_{$type}", [ $this, 'forceEmptyRestDetail' ], 10, 3 );
		}
	}

	/** ---------------------------------------- */

	/**
	 * @param $args
	 * @param $request
	 *
	 * @return mixed
	 */
	public function forceEmptyRestQuery( $args, $request ): mixed {
		$args['post__in'] = [ 0 ]; // Prevent matching any post

		return $args;
	}

	/** ---------------------------------------- */

	/**
	 * @param $response
	 *
	 * @return \WP_REST_Response
	 */
	public function forceEmptyRestCollection( $response ): \WP_REST_Response {
		return new \WP_REST_Response( [], 200 ); // Always return an empty array
	}

	/** ---------------------------------------- */

	/**
	 * @param $response
	 * @param $post
	 * @param $request
	 *
	 * @return \WP_REST_Response
	 */
	public function forceEmptyRestDetail( $response, $post, $request ): \WP_REST_Response {
		return new \WP_REST_Response( (object) [], 200 ); // Always return an empty object
	}

	/** ---------------------------------------- */

	/**
	 * Remove post-type routes from the REST endpoint list
	 *
	 * @param $endpoints
	 *
	 * @return mixed
	 */
	public function restEndpoints( $endpoints ): mixed {
		foreach ( $this->blockedPostTypes as $type ) {
			unset(
				$endpoints["/wp/v2/{$type}"],
				$endpoints["/wp/v2/{$type}/(?P<id>[\\d]+)"]
			);
		}

		unset( $endpoints['/'] );

		return $endpoints;
	}
}
