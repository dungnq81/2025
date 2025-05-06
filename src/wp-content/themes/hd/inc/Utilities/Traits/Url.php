<?php

namespace HD\Utilities\Traits;

\defined( 'ABSPATH' ) || die;

trait Url {
	// --------------------------------------------------

	/**
	 * @param string $uri
	 * @param int $status
	 *
	 * @return bool|void
	 */
	public static function redirect( string $uri = '', int $status = 301 ) {
		$uri = esc_url_raw( $uri );
		if ( ! $uri ) {
			return false;
		}

		if ( ! headers_sent() ) {
			wp_safe_redirect( $uri, $status );
			exit;
		}

		echo '<script>window.location.href="' . esc_js( $uri ) . '";</script>';
		echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $uri ) . '" /></noscript>';

		return true;
	}

	// --------------------------------------------------

	/**
	 * @param $ip
	 * @param $range
	 *
	 * @return bool
	 */
	public static function ipInRange( $ip, $range ): bool {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		$ipPattern    = '/^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})$/';
		$rangePattern = '/^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})-(\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])$/';
		$cidrPattern  = '/^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\/(\d|[1-2]\d|3[0-2])$/';

		// Check if it's a single IP address
		if ( preg_match( $ipPattern, $range ) ) {
			return (string) $ip === (string) $range;
		}

		// Check if it's an IP range
		if ( preg_match( $rangePattern, $range, $matches ) ) {
			$startIP = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
			$endIP   = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[5]}";

			return self::_compareIPs( $startIP, $endIP ) < 0 && self::_compareIPs( $startIP, $ip ) <= 0 && self::_compareIPs( $ip, $endIP ) <= 0;
		}

		// Check if it's a CIDR notation
		if ( preg_match( $cidrPattern, $range ) ) {
			[ $subnet, $maskLength ] = explode( '/', $range );

			return self::_ipCIDRCheck( $ip, $subnet, $maskLength );
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param $ip
	 * @param $subnet
	 * @param $maskLength
	 *
	 * @return bool
	 */
	private static function _ipCIDRCheck( $ip, $subnet, $maskLength ): bool {
		$ip     = ip2long( $ip );
		$subnet = ip2long( $subnet );
		$mask   = - 1 << ( 32 - $maskLength );
		$subnet &= $mask; // Align the subnet to the mask

		return ( $ip & $mask ) === $subnet;
	}

	// --------------------------------------------------

	/**
	 * @param $range
	 *
	 * @return bool
	 */
	public static function isValidIPRange( $range ): bool {
		$ipPattern    = '/^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})$/';
		$rangePattern = '/^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})-(\d|[1-9]\d|1\d{2}|2[0-4]\d|25[0-5])$/';
		$cidrPattern  = '/^(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\.(25[0-5]|2[0-4]\d|1\d{2}|\d{1,2})\/(\d|[1-2]\d|3[0-2])$/';

		if ( preg_match( $ipPattern, $range ) ) {
			return true;
		}

		if ( preg_match( $rangePattern, $range, $matches ) ) {
			$startIP = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
			$endIP   = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[5]}";

			return self::_compareIPs( $startIP, $endIP ) < 0;
		}

		if ( preg_match( $cidrPattern, $range ) ) {
			return true;
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param $ip1
	 * @param $ip2
	 *
	 * @return int
	 */
	private static function _compareIPs( $ip1, $ip2 ): int {
		$ip1Long = (int) ip2long( $ip1 );
		$ip2Long = (int) ip2long( $ip2 );

		if ( $ip1Long < $ip2Long ) {
			return - 1;
		}

		if ( $ip1Long > $ip2Long ) {
			return 1;
		}

		return 0;
	}

	// --------------------------------------------------

	/**
	 * @return mixed|string|null
	 */
	public static function serverIpAddress(): mixed {
		if ( ! empty( $_SERVER['SERVER_ADDR'] ) && filter_var( $_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP ) ) {
			return $_SERVER['SERVER_ADDR'];
		}

		$hostname = gethostname();
		if ( $hostname ) {
			$ipv4 = gethostbyname( $hostname );
			if ( $ipv4 !== $hostname && filter_var( $ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				return $ipv4;
			}
		}

		$dnsRecords = dns_get_record( $hostname, DNS_AAAA );
		if ( ! empty( $dnsRecords ) ) {
			foreach ( $dnsRecords as $record ) {
				if ( ! empty( $record['ipv6'] ) && filter_var( $record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
					return $record['ipv6'];
				}
			}
		}

		return null;
	}

	// --------------------------------------------------

	/**
	 * @return string
	 */
	public static function ipAddress(): string {
		// Check for CloudFlare's connecting IP
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return $_SERVER['HTTP_CF_CONNECTING_IP'];
		}

		// Check for forwarded IP (proxy) and get the first valid IP
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			foreach ( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) as $ip ) {
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		// Check for client IP
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && filter_var( $_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP ) ) {
			return $_SERVER['HTTP_CLIENT_IP'];
		}

		// Fallback to a remote address
		if ( isset( $_SERVER['REMOTE_ADDR'] ) && filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP ) ) {
			return $_SERVER['REMOTE_ADDR'];
		}

		// Fallback to localhost IP
		return '127.0.0.1';
	}

	// --------------------------------------------------

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public static function urlToPath( string $url ): string {
		// Ensure the URL is absolute before converting
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		// Get the home path and make the URL relative
		$homePath     = rtrim( get_home_path(), '/' );
		$relativePath = wp_make_link_relative( $url );

		// Combine the home path and relative path
		return $homePath . $relativePath;
	}

	// --------------------------------------------------

	/**
	 * @param string $dir
	 *
	 * @return string
	 */
	public static function pathToUrl( string $dir ): string {
		$dirs = wp_upload_dir();

		// Ensure the directory path starts with the base directory
		if ( ! str_starts_with( $dir, $dirs['basedir'] ) ) {
			return '';
		}

		// Replace basedir with baseurl and ABSPATH with home URL
		return str_replace(
			[ $dirs['basedir'], ABSPATH ],
			[ $dirs['baseurl'], self::siteURL() ],
			$dir
		);
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 * @param $scheme
	 *
	 * @return string
	 */
	public static function home( string $path = '', $scheme = null ): string {
		return apply_filters( 'hd_home_url_filter', esc_url( home_url( $path, $scheme ) ), $path );
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 * @param $scheme
	 *
	 * @return string
	 */
	public static function siteURL( string $path = '', $scheme = null ): string {
		return apply_filters( 'hd_site_url_filter', esc_url( site_url( $path, $scheme ) ), $path );
	}

	// --------------------------------------------------

	/**
	 * @param string $path
	 *
	 * @return string|null
	 */
	public static function adminCurrentUrl( string $path = 'admin.php' ): ?string {
		$parsed_url  = parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$current_url = admin_url( $path );
		if ( $parsed_url ) {
			$current_url .= '?' . $parsed_url['query'];
		}

		return $current_url;
	}

	// --------------------------------------------------

	/**
	 * @param bool $nopaging
	 * @param bool $get_vars
	 *
	 * @return string
	 */
	public static function current( bool $nopaging = true, bool $get_vars = true ): string {
		global $wp;

		$current_url = self::siteURL( $wp->request );

		// get the position where '/page. ' text start.
		$pos = strpos( $current_url, '/page' );

		// remove string from the specific position
		if ( $nopaging && $pos ) {
			$current_url = trailingslashit( substr( $current_url, 0, $pos ) );
		}

		if ( $get_vars ) {
			$queryString = http_build_query( $_GET );

			if ( $queryString && mb_strpos( $current_url, '?' ) ) {
				$current_url .= '&' . $queryString;
			} elseif ( $queryString ) {
				$current_url .= '?' . $queryString;
			}
		}

		return $current_url;
	}

	// --------------------------------------------------

	/**
	 * Normalize the given path. On Windows servers backslash will be replaced
	 * with slash. Removes unnecessary double slashes and double dots. Removes
	 * last slash if it exists.
	 *
	 * Examples:
	 * path::normalize("C:\\any\\path\\") returns "C:/any/path"
	 * path::normalize("/your/path/..//home/") returns "/your/home"
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public static function normalizePath( string $path ): string {
		$path  = str_replace( '\\', '/', $path );
		$parts = explode( '/', $path );
		$stack = [];

		foreach ( $parts as $part ) {
			if ( $part === '' || $part === '.' ) {
				continue;
			}
			if ( $part === '..' ) {
				if ( ! empty( $stack ) ) {
					array_pop( $stack );
				}
			} else {
				$stack[] = $part;
			}
		}

		return '/' . implode( '/', $stack );
	}

	// --------------------------------------------------

	/**
	 * @param string $url
	 *
	 * @return array
	 */
	public static function urlQueries( string $url ): array {
		$queries = [];
		parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $queries );

		return $queries;
	}

	// --------------------------------------------------

	/**
	 * @param string $url
	 * @param $param
	 * @param null $fallback
	 *
	 * @return mixed|null
	 */
	public static function urlQuery( string $url, $param, $fallback = null ): mixed {
		$queries = self::urlQueries( $url );

		return $queries[ $param ] ?? $fallback;
	}

	// --------------------------------------------------

	/**
	 * Check the HTTP status of a remote URL.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return int|false The HTTP response code on success, false on error.
	 */
	public static function remoteStatusCheck( string $url ): int|false {
		$response = wp_safe_remote_head( $url, [
			'timeout'   => 5,
			'sslverify' => false,
		] );

		if ( is_wp_error( $response ) || ! isset( $response['response']['code'] ) ) {
			return false;
		}

		return (int) $response['response']['code'];
	}
}
