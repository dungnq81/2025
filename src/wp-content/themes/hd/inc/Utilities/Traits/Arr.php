<?php

namespace HD\Utilities\Traits;

\defined( 'ABSPATH' ) || die;

trait Arr {
	// --------------------------------------------------

	/**
	 * @param string|null $string
	 * @param string $separator
	 *
	 * @return array
	 */
	public static function separatedToArray( ?string $string, string $separator = ',' ): array {
		if ( empty( $string ) ) {
			return [];
		}

		$vars = explode( $separator, $string );

		return array_filter( array_map( 'trim', $vars ), static function ( $value ) {
			return $value !== '';
		} );
	}

	// --------------------------------------------------

	/**
	 * @param array $arr1
	 * @param array $arr2
	 *
	 * @return bool
	 */
	public static function compare( array $arr1, array $arr2 ): bool {
		if ( count( $arr1 ) !== count( $arr2 ) ) {
			return false;
		}

		sort( $arr1 );
		sort( $arr2 );

		return $arr1 === $arr2;
	}

	// --------------------------------------------------

	/**
	 * @param $value
	 * @param $callback
	 *
	 * @return array
	 */
	public static function convertFromString( $value, $callback = null ): array {
		if ( is_scalar( $value ) ) {
			if ( trim( $value ) === '' ) {
				return [];
			}

			$value = array_map( 'trim', explode( ',', (string) $value ) );
		}

		return self::reIndex( array_filter( (array) $value, is_callable( $callback ) ? $callback : null ) );
	}

	// --------------------------------------------------

	/**
	 * @param $array
	 *
	 * @return array
	 */
	public static function reIndex( $array ): array {
		return self::isIndexedAndFlat( $array ) ? array_values( $array ) : $array;
	}

	// --------------------------------------------------

	/**
	 * @param $array
	 *
	 * @return bool
	 */
	public static function isIndexedAndFlat( $array ): bool {
		if ( ! is_array( $array ) || array_filter( $array, 'is_array' ) ) {
			return false;
		}

		if ( function_exists( 'wp_is_numeric_array' ) ) {
			return wp_is_numeric_array( $array );
		}

		return array_keys( $array ) === range( 0, count( $array ) - 1 );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $key
	 * @param array $array
	 * @param array $insert_array
	 *
	 * @return array
	 */
	public static function insertAfter( ?string $key, array $array, array $insert_array ): array {
		return self::insert( $array, $insert_array, $key, 'after' );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $key
	 * @param array $array
	 * @param array $insert_array
	 *
	 * @return array
	 */
	public static function insertBefore( ?string $key, array $array, array $insert_array ): array {
		return self::insert( $array, $insert_array, $key, 'before' );
	}

	// --------------------------------------------------

	/**
	 * @param array $array
	 * @param array $insert_array
	 * @param string|null $key
	 * @param string $position
	 *
	 * @return array
	 */
	public static function insert( array $array, array $insert_array, ?string $key, string $position = 'before' ): array {
		if ( $key === null ) {
			return array_merge( $array, $insert_array );
		}

		$keyPosition = array_search( $key, array_keys( $array ), true );
		if ( $keyPosition === false ) {
			return array_merge( $array, $insert_array );
		}

		if ( 'after' === $position ) {
			$keyPosition ++;
		}

		return array_merge(
			array_slice( $array, 0, $keyPosition ),
			$insert_array,
			array_slice( $array, $keyPosition )
		);
	}

	// --------------------------------------------------

	/**
	 * @param array $array
	 * @param $value
	 * @param $key
	 *
	 * @return array
	 */
	public static function prepend( array $array, $value, $key = null ): array {
		if ( isset( $key ) ) {
			$array = [ $key => $value ] + $array;
		} else {
			array_unshift( $array, $value );
		}

		return $array;
	}
}
