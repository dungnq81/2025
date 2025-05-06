<?php

namespace HD\Utilities\Traits;

\defined( 'ABSPATH' ) || die;

trait Cast {
	use Base;
	use Arr;

	// --------------------------------------------------

	/**
	 * @param mixed $delimiters
	 * @param string|null $string
	 * @param bool $remove_empty
	 *
	 * @return null[]|string[]
	 */
	public static function explodeMulti( mixed $delimiters, ?string $string, bool $remove_empty = true ): array {
		$string = (string) $string;
		if ( is_string( $delimiters ) ) {
			return explode( $delimiters, $string );
		}

		if ( is_array( $delimiters ) ) {
			$ready  = str_replace( $delimiters, $delimiters[0], $string );
			$launch = explode( $delimiters[0], $ready );

			return $remove_empty ? array_values( array_filter( $launch ) ) : array_values( $launch );
		}

		return [ $string ];
	}

	// --------------------------------------------------

	/**
	 * Convert a mixed value to an integer.
	 *
	 * @param mixed $value The value to be converted.
	 *
	 * @return int The converted integer value.
	 */
	public static function toInt( mixed $value ): int {
		// Convert the value to float first
		$floatValue = self::toFloat( $value );

		// Round and return as integer
		return (int) round( $floatValue );
	}

	// --------------------------------------------------

	/**
	 * Convert a mixed value to a float.
	 *
	 * @param mixed $value The value to be converted.
	 *
	 * @return float The converted float value or 0.0 if invalid.
	 */
	public static function toFloat( mixed $value ): float {
		// Attempt to validate and convert the value to float
		$floatValue = filter_var( $value, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND );

		// If the conversion fails, return 0.0 or handle the error accordingly
		return $floatValue !== false ? (float) $floatValue : 0.0;
	}

	// --------------------------------------------------

	/**
	 * Convert a mixed value to an array.
	 *
	 * @param mixed $value The value to convert.
	 * @param bool $explode If true, attempt to convert scalar values (like strings) to an array.
	 *
	 * @return array The converted array.
	 */
	public static function toArray( mixed $value, bool $explode = true ): array {
		if ( is_bool( $value ) ) {
			return [ $value ];
		}

		// If the value is scalar and explosion is allowed
		if ( is_scalar( $value ) && $explode ) {
			return self::convertFromString( $value );
		}

		// If the value is an object
		if ( is_object( $value ) ) {
			$reflection = new \ReflectionObject( $value );
			$value      = $reflection->hasMethod( 'toArray' )
				? $value->toArray()
				: get_object_vars( $value );
		}

		return (array) $value;
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 * @param bool $explode
	 *
	 * @return array
	 */
	public static function toArrayDeep( mixed $value, bool $explode = true ): array {
		$values = static::toArray( $value, $explode );

		foreach ( $values as $key => $item ) {
			if ( is_object( $item ) ) {
				$values[ $key ] = static::toArrayDeep( $item, $explode );
			}
		}

		try {
			$json = json_encode( $values, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR );

			return json_decode( $json, true, 512, JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR );
		} catch ( \JsonException $error ) {
			self::errorLog( 'Message: ' . $error->getMessage() );

			return [];
		}
	}

	// --------------------------------------------------

	/**
	 * Convert a mixed value to a string.
	 *
	 * @param mixed $value The value to convert.
	 * @param bool $strict If true, return an empty string for non-scalar values; if false, serialize them.
	 *
	 * @return string The converted string.
	 */
	public static function toString( mixed $value, bool $strict = true ): string {
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		// Handle object conversion using __toString method
		if ( is_object( $value ) && in_array( '__toString', get_class_methods( $value ) ) ) {
			return (string) $value->__toString();
		}

		// Return an empty string for empty values
		if ( self::isEmpty( $value ) ) {
			return '';
		}

		// Handle indexed and flat arrays
		if ( self::isIndexedAndFlat( $value ) ) {
			return implode( ', ', $value );
		}

		return $strict ? '' : maybe_serialize( $value );
	}

	// --------------------------------------------------

	/**
	 * Convert a mixed value to a boolean.
	 *
	 * @param mixed $value The value to convert.
	 *
	 * @return bool True for "truthy" values, false otherwise.
	 */
	public static function toBool( mixed $value ): bool {
		// Handle null explicitly if needed (optional)
		if ( is_null( $value ) ) {
			return false;
		}

		// Use filter_var to convert value to boolean
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? false;
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 *
	 * @return mixed|object
	 */
	public static function toObject( mixed $value ): mixed {
		if ( ! is_object( $value ) ) {

			// Attempt to convert to array, catching any potential JsonException
			return (object) self::toArray( $value );
		}

		return $value;
	}
}
