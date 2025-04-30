<?php

namespace Addons\ThirdParty;

\defined( 'ABSPATH' ) || exit;

final class Faker {
	// -------------------------------------------------------------

	public function __construct() {
		//add_filter( 'pre_http_request', [ $this, 'acf_license_request' ], 10, 3 ); // ACF pro
		add_action( 'wp_loaded', [ $this, 'wordfence_pre' ], 99 );                 // Wordfence Security
		add_action( 'wp_loaded', [ $this, 'cf7_gsc_pro' ], 99 );                   // CF7 Google Sheet Connector Pro
		add_action( 'wp_loaded', [ $this, 'woocommerce_gsc_pro' ], 99 );           // WooCommerce GSheetConnector PRO
	}

	// -------------------------------------------------------------

	/**
	 * @param $preempt
	 * @param $parsed_args
	 * @param $url
	 *
	 * @return array|mixed|void
	 * @throws \JsonException
	 */
	public function acf_license_request( $preempt, $parsed_args, $url ) {
		if ( ! \Addons\Helper::isAcfProActive() ) {
			return $preempt;
		}

		// Check update request
		$acf_update_check_disabled = \Addons\Helper::getOption( '_acf_update_check_disabled', true );
		if (
			$acf_update_check_disabled
			&& str_contains( $url, 'https://connect.advancedcustomfields.com/v2/plugins/update-check' )
		) {
			return [
				'headers'  => [],
				'body'     => json_encode( [ 'checked' => [] ], JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR ),
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		}

		// Intercept ACF activation request
		if ( str_contains( $url, 'https://connect.advancedcustomfields.com/v2/plugins/activate?p=pro' ) ) {
			return [
				'headers'  => [],
				'body'     => json_encode(
					[
						'message'        => 'Licence key activated. Updates are now enabled',
						'license'        => 'GPL001122334455AA6677BB8899CC000',
						'license_status' => [
							'status'            => 'active',
							'lifetime'          => true,
							'name'              => 'Agency',
							'view_licenses_url' => 'https://www.advancedcustomfields.com/my-account/view-licenses/',
						],
						'status'         => 1,
					],
					JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR
				),
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		}

		// Intercept ACF validation request
		if ( str_contains( $url, 'https://connect.advancedcustomfields.com/v2/plugins/validate?p=pro' ) ) {
			return [
				'headers'  => [],
				'body'     => json_encode(
					[
						'expiration'     => 864000,
						'license_status' => [
							'status'            => 'active',
							'lifetime'          => true,
							'name'              => 'Agency',
							'view_licenses_url' => 'https://www.advancedcustomfields.com/my-account/view-licenses/',
						],
						'status'         => 1,
					], JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR
				),
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		}

		// Intercept ACF get-info request
		if ( str_contains( $url, 'https://connect.advancedcustomfields.com/v2/plugins/get-info?p=pro' ) ) {
			return [
				'headers'  => [],
				'body'     => json_encode(
					[
						'name'    => 'Advanced Custom Fields PRO',
						'slug'    => 'advanced-custom-fields-pro',
						'version' => '6.x.x',
					], JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR
				),
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		}

		return $preempt;
	}

	// -------------------------------------------------------------

	/**
	 * @return void
	 */
	public function cf7_gsc_pro(): void {
		if ( ! \Addons\Helper::checkPluginActive( 'cf7-google-sheets-connector-pro/google-sheet-connector-pro.php' ) ) {
			return;
		}

		$options = [
			'gs_license_key'    => 'license',
			'gs_license_status' => 'valid',
		];

		foreach ( $options as $option_name => $new_value ) {
			$current_value = \Addons\Helper::getOption( $option_name );
			if ( $current_value === false || $current_value !== $new_value ) {
				\Addons\Helper::updateOption( $option_name, $new_value );
			}
		}
	}

	// -------------------------------------------------------------

	/**
	 * @return void
	 */
	public function woocommerce_gsc_pro(): void {
		if ( ! \Addons\Helper::checkPluginActive( 'wc-gsheetconnector-pro/wc-gsheetconnector-pro.php' ) ) {
			return;
		}

		$options = [
			'gs_woo_license_key'    => 'license',
			'gs_woo_license_status' => 'valid',
		];

		foreach ( $options as $option_name => $new_value ) {
			$current_value = \Addons\Helper::getOption( $option_name );
			if ( $current_value === false || $current_value !== $new_value ) {
				\Addons\Helper::updateOption( $option_name, $new_value );
			}
		}
	}

	// -------------------------------------------------------------

	/**
	 * @return void
	 * @throws \DateMalformedStringException
	 */
	public function wordfence_pre(): void {
		if ( ! \Addons\Helper::checkPluginActive( 'wordfence/wordfence.php' ) ) {
			return;
		}

		global $wpdb;

		$RemainingDays = 365;
		$table_name    = $wpdb->prefix . 'wfconfig';

		$data = [
			'name'     => 'scan_exclude',
			'val'      => '/hd-addons/*',
			'autoload' => 'yes',
		];

		$existing_entry = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE name = %s",
			$data['name']
		) );

		if ( $existing_entry === 0 ) {
			// Insert the new record if no entry exists
			$wpdb->insert( $table_name, $data, [ '%s', '%s', '%s' ] );
		} else {
			$existing_val = $wpdb->get_var( $wpdb->prepare(
				"SELECT val FROM $table_name WHERE name = %s",
				$data['name']
			) );

			if ( empty( $existing_val ) ) {
				// Insert the new value
				$wpdb->update( $table_name, [ 'val' => $data['val'] ], [ 'name' => $data['name'] ], [ '%s' ], [ '%s' ] );
			} elseif ( ! str_contains( $existing_val, $data['val'] ) ) {
				// Append the new value to the existing value
				$wpdb->update( $table_name, [ 'val' => $existing_val . ',' . $data['val'] ], [ 'name' => $data['name'] ], [ '%s' ], [ '%s' ] );
			}
		}

		// wfLicense
		if ( class_exists( 'wfLicense' ) ) {
			$date = new \DateTime();
			$date->modify( "+{$RemainingDays} days" );

			try {
				\wfOnboardingController::_markAttempt1Shown();
				\wfConfig::set( 'onboardingAttempt3', \wfOnboardingController::ONBOARDING_LICENSE );
				if ( empty( \wfConfig::get( 'apiKey' ) ) ) {
					\wordfence::ajax_downgradeLicense_callback();
				}
				\wfConfig::set( 'isPaid', true );
				\wfConfig::set( 'keyType', \wfLicense::KEY_TYPE_PAID_CURRENT );
				\wfConfig::set( 'premiumNextRenew', $date->getTimestamp() );
				\wfWAF::getInstance()->getStorageEngine()->setConfig( 'wafStatus', \wfFirewall::FIREWALL_MODE_ENABLED );

				$wfLicense = \wfLicense::current();
				if ( $wfLicense ) {
					$wfLicense->setType( \wfLicense::TYPE_RESPONSE );
					$wfLicense->setPaid( true );
					$wfLicense->setRemainingDays( $RemainingDays );
					$wfLicense->setConflicting( false );
					$wfLicense->setDeleted( false );
					$wfLicense->getKeyType();
				}
			} catch ( \Exception $exception ) {
				// Handle the exception if needed
			}
		}
	}
}
