<?php

declare( strict_types=1 );

namespace HD\Utilities\Helpers;

\defined( 'ABSPATH' ) || die;

/**
 * Collect & enqueue CSS/JS.
 *
 * @author Gaudev
 */
final class Asset {
	// ----------------------------------------

	/**
	 * @param string $handle
	 * @param string $src
	 * @param array $deps
	 * @param string|bool|null $ver
	 * @param string $media
	 *
	 * @return void
	 */
	public static function enqueueStyle( string $handle, string $src, array $deps = [], string|bool|null $ver = null, string $media = 'all' ): void {
		if ( empty( $src ) ) {
			return;
		}

		$args = [
			'src'   => $src,
			'deps'  => $deps,
			'ver'   => $ver,
			'media' => $media,
		];

		wp_register_style( $handle, $args['src'], $args['deps'], $args['ver'], $args['media'] );
		wp_enqueue_style( $handle );
	}

	// ----------------------------------------

	/**
	 * @param string $handle
	 * @param string $src
	 * @param array $deps
	 * @param string|bool|null $ver
	 * @param bool $in_footer
	 * @param array $extra - Ex. [ 'module', 'defer' ]
	 *
	 * @return void
	 */
	public static function enqueueScript( string $handle, string $src, array $deps = [], string|bool|null $ver = null, bool $in_footer = true, array $extra = [] ): void {
		if ( empty( $src ) ) {
			return;
		}

		$args = [
			'src'       => $src,
			'deps'      => $deps,
			'ver'       => $ver,
			'in_footer' => $in_footer,
			'extra'     => $extra,
		];

		wp_register_script( $handle, $args['src'], $args['deps'], $args['ver'], $args['in_footer'] );
		wp_enqueue_script( $handle );

		if ( ! empty( $args['extra'] ) ) {
			wp_script_add_data( $handle, 'extra', $args['extra'] );
		}
	}

	// ----------------------------------------

	/**
	 * @param string $handle
	 * @param string $object_name
	 * @param array|null $l10n
	 *
	 * @return void
	 */
	public static function localize( string $handle, string $object_name, ?array $l10n ): void {
		if ( empty( $object_name ) || empty( $l10n ) ) {
			return;
		}

		wp_localize_script( $handle, $object_name, $l10n );
	}

	// ----------------------------------------

	/**
	 * @param string $handle
	 * @param string $css
	 *
	 * @return void
	 */
	public static function inlineStyle( string $handle, string $css ): void {
		if ( empty( $css ) ) {
			return;
		}

		wp_add_inline_style( $handle, $css );
	}

	// ----------------------------------------

	/**
	 * @param string $handle
	 * @param string $code
	 * @param string $position
	 *
	 * @return void
	 */
	public static function inlineScript( string $handle, string $code, string $position = 'after' ): void {
		if ( empty( $code ) ) {
			return;
		}

		wp_add_inline_script( $handle, $code, $position );
	}
}
