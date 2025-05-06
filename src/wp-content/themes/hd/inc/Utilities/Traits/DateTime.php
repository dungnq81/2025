<?php

namespace HD\Utilities\Traits;

\defined( 'ABSPATH' ) || die;

trait DateTime {
	// -------------------------------------------------------------

	/**
	 * @param $time_string
	 *
	 * @return bool
	 */
	public function isFutureTime( $time_string ): bool {
		$time_converted = self::convertDatetimeFormat( $time_string, 'U' );
		if ( false === $time_converted ) {
			return false;
		}

		$current = current_time( 'U', 0 );

		return $time_converted >= $current;
	}

	// -------------------------------------------------------------

	/**
	 * Humanizes the time difference between two timestamps.
	 *
	 * @param mixed $post Optional. The post-ID to get the time from. Default is null.
	 * @param false|int|string $from Optional. The starting timestamp. Default is null.
	 * @param false|int|string $to Optional. The ending timestamp. Default is current time.
	 *
	 * @return string The human-readable time difference.
	 */
	public static function humanizeTime( mixed $post = null, false|int|string $from = false, false|int|string $to = false ): string {
		if ( empty( $to ) ) {
			$to = current_time( 'U', 0 );
		}

		if ( empty( $from ) && $post ) {
			$from = get_the_time( 'U', $post );
		}

		// If $from is still empty, return an empty string or handle accordingly
		if ( empty( $from ) ) {
			return '';
		}

		//return human_time_diff( $from, $to );
		return sprintf( __( '%s ago', TEXT_DOMAIN ), human_time_diff( $from, $to ) );
	}

	// --------------------------------------------------

	/**
	 * Calculates the ISO 8601 duration between two date-time strings.
	 *
	 * @param string $date_time_1 First date-time string.
	 * @param string $date_time_2 Second date-time string.
	 *
	 * @return string ISO 8601 duration string.
	 * @throws \Exception If the date-time strings are invalid.
	 */
	public static function isoDuration( string $date_time_1, string $date_time_2 ): string {
		// Create DateTime objects
		$_date_time_1 = new \DateTime( $date_time_1 );
		$_date_time_2 = new \DateTime( $date_time_2 );

		// Calculate the interval
		$interval = $_date_time_1->diff( $_date_time_2 );

		// Start building the ISO duration string
		$isoDuration = 'P';
		$isoDuration .= ( $interval->y > 0 ) ? $interval->y . 'Y' : '';
		$isoDuration .= ( $interval->m > 0 ) ? $interval->m . 'M' : '';
		$isoDuration .= ( $interval->d > 0 ) ? $interval->d . 'D' : '';

		// Check if there are any time components to add
		$timePart = 'T';
		$timePart .= ( $interval->h > 0 ) ? $interval->h . 'H' : '';
		$timePart .= ( $interval->i > 0 ) ? $interval->i . 'M' : '';
		$timePart .= ( $interval->s > 0 ) ? $interval->s . 'S' : '';

		// If there are no time components, reset the time part
		if ( $timePart === 'T' ) {
			$timePart = 'T0S'; // Indicates zero duration in time
		}

		return $isoDuration . $timePart;
	}

	// -------------------------------------------------------------

	/**
	 * @param int|string $date_string
	 * @param string $format
	 *
	 * @return false|int|string
	 */
	public static function convertToUTC( int|string $date_string, string $format = 'Y-m-d H:i:s' ): false|int|string {
		if ( self::isInteger( $date_string ) ) {
			$date_string = '@' . $date_string;
		}
		$timezone = wp_timezone();
		$datetime = date_create( $date_string, $timezone );
		if ( false === $datetime ) {
			return false;
		}
		if ( 'timestamp' === $format || 'U' === $format ) {
			return $datetime->getTimestamp();
		}
		if ( 'mysql' === $format ) {
			$format = 'Y-m-d H:i:s';
		}

		return $datetime->setTimezone( new \DateTimeZone( 'UTC' ) )->format( $format );
	}

	// -------------------------------------------------------------

	/**
	 * @param int|string $date_string
	 * @param string $format
	 *
	 * @return false|int|string
	 */
	public static function convertFromUTC( int|string $date_string, string $format = 'Y-m-d H:i:s' ): false|int|string {
		if ( self::isInteger( $date_string ) ) {
			$date_string = '@' . $date_string;
		}
		$datetime = date_create( $date_string, new \DateTimeZone( 'UTC' ) );
		if ( false === $datetime ) {
			return false;
		}
		$timezone = wp_timezone();
		$datetime->setTimezone( $timezone );
		if ( 'timestamp' === $format || 'U' === $format ) {
			return $datetime->getTimestamp() + $datetime->getOffset();
		}
		if ( 'mysql' === $format ) {
			$format = 'Y-m-d H:i:s';
		}

		return $datetime->format( $format );
	}

	// -------------------------------------------------------------

	/**
	 * @param int|string $date_string
	 * @param string $format
	 *
	 * @return false|int|string
	 */
	public static function convertDatetimeFormat( int|string $date_string, string $format = 'Y-m-d H:i:s' ): false|int|string {
		if ( self::isInteger( $date_string ) ) {
			$date_string = '@' . $date_string;
		}
		$timezone = wp_timezone();
		$datetime = date_create( $date_string, $timezone );
		if ( false === $datetime ) {
			return false;
		}
		if ( 'timestamp' === $format || 'U' === $format ) {
			return $datetime->getTimestamp() + $datetime->getOffset();
		}
		if ( 'mysql' === $format ) {
			$format = 'Y-m-d H:i:s';
		}

		return $datetime->format( $format );
	}

	// -------------------------------------------------------------

	/**
	 * @param string $date_string
	 *
	 * @return array|string[]
	 * @throws \Exception
	 */
	public static function timeDifference( string $date_string ): array {
		$timezone   = wp_timezone();
		$targetTime = \DateTime::createFromFormat( 'Y-m-d\TH:i:s', $date_string, $timezone );
		if ( $targetTime === false ) {
			return [
				'days'    => '00',
				'hours'   => '00',
				'minutes' => '00',
				'seconds' => '00',
			];
		}
		$now      = new \DateTime( 'now', $timezone );
		$interval = $now->diff( $targetTime );

		return [
			'days'    => str_pad( $interval->format( '%a' ), 2, '0', STR_PAD_LEFT ),
			'hours'   => str_pad( $interval->format( '%h' ), 2, '0', STR_PAD_LEFT ),
			'minutes' => str_pad( $interval->format( '%i' ), 2, '0', STR_PAD_LEFT ),
			'seconds' => str_pad( $interval->format( '%s' ), 2, '0', STR_PAD_LEFT ),
		];
	}

	// --------------------------------------------------
}
