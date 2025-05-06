<?php

namespace HD\Utilities\Traits;

\defined( 'ABSPATH' ) || die;

trait Encryption {

	private static ?string $method = null;
	private static ?string $secretKey = null;

	// -------------------------------------------------------------

	/**
	 * @return void
	 */
	private static function loadKeys(): void {
		if ( is_null( self::$method ) || is_null( self::$secretKey ) ) {
			$keyFile = INC_PATH . 'encryption-key.php';

			if ( ! is_file( $keyFile ) ) {
				throw new \RuntimeException( "Key file not found: $keyFile" );
			}

			// Include the key file and validate its content
			$keys = include $keyFile;
			if ( ! isset( $cipher_method, $secret_key ) ) {
				throw new \RuntimeException( "Invalid key file format: $keyFile" );
			}

			// Assign values or use defaults
			self::$method    = $cipher_method ?? 'AES-128-CBC';
			self::$secretKey = $secret_key ?? 'd24eebeca3db6407c18d4de572fff114';
		}
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $data
	 *
	 * @return string|null
	 * @throws \Random\RandomException
	 */
	public static function encode( ?string $data ): ?string {
		if ( is_null( $data ) ) {
			return null;
		}

		self::loadKeys();

		$ivLength = openssl_cipher_iv_length( self::$method );
		if ( $ivLength === false ) {
			throw new \RuntimeException( "Invalid cipher method: " . self::$method );
		}

		$iv        = random_bytes( $ivLength );
		$key       = substr( hash( 'sha256', self::$secretKey ), 0, 16 );
		$encrypted = openssl_encrypt( $data, self::$method, $key, 0, $iv );

		if ( $encrypted === false ) {
			throw new \RuntimeException( "Encryption failed." );
		}

		return base64_encode( $iv . $encrypted );
	}

	// -------------------------------------------------------------

	/**
	 * @param string|null $encryptedData
	 *
	 * @return string|null
	 */
	public static function decode( ?string $encryptedData ): ?string {
		if ( is_null( $encryptedData ) ) {
			return null;
		}

		self::loadKeys();

		$data = base64_decode( $encryptedData, true );
		if ( $data === false ) {
			throw new \RuntimeException( "Invalid base64 encoded data." );
		}

		$ivLength = openssl_cipher_iv_length( self::$method );
		if ( $ivLength === false ) {
			throw new \RuntimeException( "Invalid cipher method: " . self::$method );
		}

		$iv        = substr( $data, 0, $ivLength );
		$encrypted = substr( $data, $ivLength );

		$key       = substr( hash( 'sha256', self::$secretKey ), 0, 16 );
		$decrypted = openssl_decrypt( $encrypted, self::$method, $key, 0, $iv );

		if ( $decrypted === false ) {
			throw new \RuntimeException( "Decryption failed." );
		}

		return $decrypted;
	}
}
