<?php

namespace Addons\LoginSecurity;

use Addons\Helper;

\defined( 'ABSPATH' ) || exit;

class LoginRestricted {
	/* ---------- CONFIG -------------------------------------------------- */

	public ?array $allowlist_ips = [];
	public ?array $blocked_ips = [];

	/* ---------- CONSTRUCT ----------------------------------------------- */

	public function __construct() {
		add_action( 'login_init', [ $this, 'restrictLoginToIps' ], PHP_INT_MIN );
	}

	/* ---------- PUBLIC -------------------------------------------------- */

	/**
	 * @return bool
	 */
	public function restrictLoginToIps(): bool {
		if ( ! $this->_restricted() ) {
			return true;
		}

		$user_ip = Helper::ipAddress();

		// Check if the current IP is in the allowed list, block all other IPs not in the list.
		if ( ! empty( $this->allowlist_ips ) ) {
			foreach ( $this->allowlist_ips as $allowed_ip ) {
				if ( $this->_ipInRange( $user_ip, $allowed_ip ) ) {
					return true;
				}
			}

			// Update the total blocked logins counter.
			Helper::updateOption( '_security_total_blocked_logins', (int) Helper::getOption( '_security_total_blocked_logins', 0 ) + 1 );

			Helper::errorLog( 'Restricted login page: access currently not permitted - ' . $user_ip );
			Helper::wpDie(
				esc_html__( 'You don’t have access to this page. Please contact the administrator of this website for further assistance.', ADDONS_TEXTDOMAIN ),
				esc_html__( 'Restricted access', ADDONS_TEXTDOMAIN ),
				[
					'addon_error'   => true,
					'response'      => 403,
					'blocked_login' => true,
				]
			);
		}

		// Block all IPs in the list.
		if ( ! empty( $this->blocked_ips ) ) {
			foreach ( $this->blocked_ips as $blocked_ip ) {
				if ( $this->_ipInRange( $user_ip, $blocked_ip ) ) {
					// Update the total blocked logins counter.
					Helper::updateOption( '_security_total_blocked_logins', (int) Helper::getOption( '_security_total_blocked_logins', 0 ) + 1 );

					Helper::errorLog( 'Restricted login page: access currently not permitted - ' . $user_ip );
					Helper::wpDie(
						esc_html__( 'You don’t have access to this page. Please contact the administrator of this website for further assistance.', ADDONS_TEXTDOMAIN ),
						esc_html__( 'Restricted access', ADDONS_TEXTDOMAIN ),
						[
							'addon_error'   => true,
							'response'      => 403,
							'blocked_login' => true,
						]
					);
				}
			}
		}

		return false;
	}

	/* ---------- INTERNAL ------------------------------------------------ */

	/**
	 * @return bool
	 */
	private function _restricted(): bool {
		$_options             = Helper::getOption( 'login_security__options' );
		$custom_allowlist_ips = $_options['login_ips_access'] ?? [];
		$custom_blocked_ips   = $_options['disable_ips_access'] ?? [];

		$_options_default           = Helper::filterSettingOptions( 'security', false );
		$allowlist_ips_login_access = $_options_default['allowlist_ips_login_access'] ?? [];
		$blocked_ips_login_access   = $_options_default['blocked_ips_login_access'] ?? [];

		$this->allowlist_ips = array_filter( array_merge( (array) $allowlist_ips_login_access, (array) $custom_allowlist_ips ) );
		$this->blocked_ips   = array_filter( array_merge( (array) $blocked_ips_login_access, (array) $custom_blocked_ips ) );

		return ! empty( $this->allowlist_ips ) || ! empty( $this->blocked_ips );
	}

	/**
	 * @param $ip
	 * @param $range
	 *
	 * @return bool
	 */
	private function _ipInRange( $ip, $range ): bool {
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

			return $this->_compareIPs( $startIP, $endIP ) < 0 &&
			       $this->_compareIPs( $startIP, $ip ) <= 0 &&
			       $this->_compareIPs( $ip, $endIP ) <= 0;
		}

		// Check if it's a CIDR notation
		if ( preg_match( $cidrPattern, $range ) ) {
			[ $subnet, $maskLength ] = explode( '/', $range );

			return $this->_ipCIDRCheck( $ip, $subnet, $maskLength );
		}

		return false;
	}

	/**
	 * @param $ip1
	 * @param $ip2
	 *
	 * @return int
	 */
	private function _compareIPs( $ip1, $ip2 ): int {
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

	/**
	 * @param $ip
	 * @param $subnet
	 * @param $maskLength
	 *
	 * @return bool
	 */
	private function _ipCIDRCheck( $ip, $subnet, $maskLength ): bool {
		$ip     = ip2long( $ip );
		$subnet = ip2long( $subnet );
		$mask   = - 1 << ( 32 - $maskLength );
		$subnet &= $mask;

		return ( $ip & $mask ) === $subnet;
	}
}
