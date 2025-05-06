<?php

namespace HD\Utilities\Traits;

\defined( 'ABSPATH' ) || die;

trait File {
	use Base;

	// --------------------------------------------------

	/**
	 * @return bool
	 */
	public static function htAccess(): bool {
		global $is_apache;

		if ( $is_apache ) {
			return true;
		}

		// Check if the custom HTACCESS environment variable is set
		if ( isset( $_SERVER['HTACCESS'] ) && $_SERVER['HTACCESS'] === 'on' ) {
			return true;
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @return mixed
	 */
	public static function wpFileSystem(): mixed {
		global $wp_filesystem;

		// Initialize the WP filesystem, no more using the 'file-put-contents' function.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	// --------------------------------------------------

	/**
	 * @param string|null $path
	 *
	 * @return bool
	 */
	public static function fileCreate( ?string $path ): bool {
		if ( empty( $path ) || ! is_string( $path ) ) {
			self::errorLog( 'Invalid file path provided for fileCreate.' );

			return false;
		}

		$wp_filesystem = self::wpFileSystem();

		if ( empty( $wp_filesystem ) ) {
			return false;
		}

		// Bail if the file already exists.
		if ( $wp_filesystem->is_file( $path ) ) {
			return true;
		}

		// Create the file.
		return $wp_filesystem->touch( $path );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $file
	 *
	 * @return string|null
	 */
	public static function fileRead( ?string $file ): ?string {
		if ( empty( $file ) || ! is_file( $file ) ) {
			self::errorLog( 'Invalid file path provided for fileRead.' );

			return null;
		}

		$wp_filesystem = self::wpFileSystem();

		if ( empty( $wp_filesystem ) ) {
			return null;
		}

		// Read `file`
		return $wp_filesystem->get_contents( $file ) ?: null;
	}

	// --------------------------------------------------

	/**
	 * @param string|null $path
	 * @param string $content
	 *
	 * @return void
	 */
	public static function fileUpdate( ?string $path, string $content = '' ): void {
		if ( empty( $path ) || ! is_string( $path ) ) {
			self::errorLog( 'Invalid file path provided for fileUpdate.' );

			return;
		}

		if ( ! is_string( $content ) ) {
			self::errorLog( 'Invalid content provided for fileUpdate.' );

			return;
		}

		$wp_filesystem = self::wpFileSystem();

		if ( empty( $wp_filesystem ) ) {
			return;
		}

		// Add the new content into the file.
		$wp_filesystem->put_contents( $path, $content );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $path
	 * @param string $content
	 *
	 * @return bool
	 */
	public static function doLockWrite( ?string $path, string $content = '' ): bool {
		if ( empty( $path ) || ! is_string( $path ) ) {
			self::errorLog( 'Invalid file path provided for doLockWrite.' );

			return false;
		}

		if ( ! is_string( $content ) ) {
			self::errorLog( 'Invalid content provided for doLockWrite.' );

			return false;
		}

		$fp = fopen( $path, 'wb+' );
		if ( $fp === false ) {
			return false;
		}

		if ( flock( $fp, LOCK_EX ) ) {
			fwrite( $fp, $content );
			flock( $fp, LOCK_UN );
			fclose( $fp );

			return true;
		}

		fclose( $fp );

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param string $filename
	 * @param bool $include_dot
	 *
	 * @return string
	 */
	public static function fileExtension( string $filename, bool $include_dot = false ): string {
		if ( empty( $filename ) ) {
			return '';
		}

		$dot = $include_dot ? '.' : '';

		return $dot . strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	}

	// --------------------------------------------------

	/**
	 * @param string $filename
	 * @param bool $include_ext
	 *
	 * @return string
	 */
	public static function fileName( string $filename, bool $include_ext = false ): string {
		if ( empty( $filename ) ) {
			return '';
		}

		return $include_ext
			? pathinfo( $filename, PATHINFO_FILENAME ) . self::fileExtension( $filename, true )
			: pathinfo( $filename, PATHINFO_FILENAME );
	}

	// --------------------------------------------------

	/**
	 * @param string $dirname
	 *
	 * @return bool
	 */
	public static function isEmptyDir( string $dirname ): bool {
		if ( ! is_dir( $dirname ) || ! is_readable( $dirname ) ) {
			return false;
		}

		$files = scandir( $dirname, SCANDIR_SORT_NONE );
		if ( $files === false ) {
			self::errorLog( 'Failed to scan directory: ' . $dirname );

			return false;
		}

		foreach ( $files as $file ) {
			if ( ! in_array( $file, [ '.', '..', '.svn', '.git' ], false ) ) {
				return false;
			}
		}

		return true;
	}

	// --------------------------------------------------

	/**
	 * @param string|null $directory
	 *
	 * @return bool
	 */
	public static function createDirectory( ?string $directory ): bool {
		if ( empty( $directory ) || ! is_string( $directory ) ) {
			self::errorLog( 'Invalid directory path provided for createDirectory.' );

			return false;
		}

		if ( ! is_writable( dirname( $directory ) ) ) {
			self::errorLog( sprintf( 'Cannot write to the parent directory: %s.', dirname( $directory ) ) );

			return false;
		}

		$is_directory_created = wp_mkdir_p( $directory );

		if ( ! $is_directory_created ) {
			self::errorLog( sprintf( 'Cannot create directory: %s.', $directory ) );
		}

		return $is_directory_created;
	}

	// --------------------------------------------------

	/**
	 * @param string|null $fileUrl
	 * @param array|null $allowedTypes
	 * @param int|null $maxFileSize
	 * @param string|null $specificDir
	 *
	 * @return array|null
	 */
	public static function uploadFileFromUrl( ?string $fileUrl, ?array $allowedTypes = null, ?int $maxFileSize = null, ?string $specificDir = null ): ?array {
		if ( empty( $fileUrl ) || ! self::isUrl( $fileUrl ) ) {
			self::errorLog( 'Invalid file URL provided for uploadFileFromUrl.' );

			return null;
		}

		$response = wp_remote_get( $fileUrl, [ 'timeout' => 10 ] );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			self::errorLog( 'Failed to retrieve file from URL: ' . $fileUrl );

			return null;
		}

		$fileContent = wp_remote_retrieve_body( $response );

		if ( empty( $fileContent ) ) {
			self::errorLog( 'Empty file content retrieved from URL: ' . $fileUrl );

			return null;
		}

		$filename  = basename( parse_url( $fileUrl, PHP_URL_PATH ) );
		$uploadDir = wp_upload_dir();

		if ( $specificDir ) {
			$directory = trailingslashit( $uploadDir['basedir'] ) . trim( $specificDir, '/' );
			if ( ! self::createDirectory( $directory ) ) {
				return null;
			}
		} else {
			$directory = $uploadDir['path'];
		}

		$filePath = trailingslashit( $directory ) . $filename;

		if ( $maxFileSize !== null && mb_strlen( $fileContent ) > $maxFileSize ) {
			self::errorLog( 'File exceeds maximum allowed size: ' . $filename );

			return null;
		}

		if ( ! self::doLockWrite( $filePath, $fileContent ) ) {
			self::errorLog( 'Failed to write file: ' . $filePath );

			return null;
		}

		$filetype = wp_check_filetype( $filePath );

		if ( $allowedTypes !== null && ( ! $filetype['type'] || ! in_array( $filetype['type'], $allowedTypes, false ) ) ) {
			self::errorLog( 'File type not allowed: ' . $filetype['type'] );

			return null;
		}

		$attachment = [
			'guid'           => $uploadDir['url'] . '/' . $filename,
			'post_mime_type' => $filetype['type'],
			'post_title'     => self::fileName( $filename, false ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachId = wp_insert_attachment( $attachment, $filePath );

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$attachData = wp_generate_attachment_metadata( $attachId, $filePath );
		wp_update_attachment_metadata( $attachId, $attachData );

		return [
			'id'  => $attachId,
			'url' => wp_get_attachment_url( $attachId ),
		];
	}

	// --------------------------------------------------

	/**
	 * @param string|null $name
	 *
	 * @return string
	 */
	public static function svg( ?string $name ): string {
		if ( ! $name ) {
			return '';
		}

		$default = [
			'tiktok'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="fill: currentColor"><path d="M448,209.91a210.06,210.06,0,0,1-122.77-39.25V349.38A162.55,162.55,0,1,1,185,188.31V278.2a74.62,74.62,0,1,0,52.23,71.18V0l88,0a121.18,121.18,0,0,0,1.86,22.17h0A122.18,122.18,0,0,0,381,102.39a121.43,121.43,0,0,0,67,20.14Z"/></svg>',
			'messenger' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="fill: currentColor"><path d="M256.55 8C116.52 8 8 110.34 8 248.57c0 72.3 29.71 134.78 78.07 177.94 8.35 7.51 6.63 11.86 8.05 58.23A19.92 19.92 0 0 0 122 502.31c52.91-23.3 53.59-25.14 62.56-22.7C337.85 521.8 504 423.7 504 248.57 504 110.34 396.59 8 256.55 8zm149.24 185.13l-73 115.57a37.37 37.37 0 0 1-53.91 9.93l-58.08-43.47a15 15 0 0 0-18 0l-78.37 59.44c-10.46 7.93-24.16-4.6-17.11-15.67l73-115.57a37.36 37.36 0 0 1 53.91-9.93l58.06 43.46a15 15 0 0 0 18 0l78.41-59.38c10.44-7.98 24.14 4.54 17.09 15.62z"/></svg>',
			'linkedin'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="fill: currentColor"><path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"/></svg>',
			'telegram'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="fill: currentColor"><path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"></path></svg>',
			'x'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="fill: currentColor"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>',
			'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" style="fill: currentColor"><path d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z"/></svg>',
			'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="fill: currentColor"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>',
			'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" style="fill: currentColor"><path d="M80 299.3V512H196V299.3h86.5l18-97.8H196V166.9c0-51.7 20.3-71.5 72.7-71.5c16.3 0 29.4 .4 37 1.2V7.9C291.4 4 256.4 0 236.2 0C129.3 0 80 50.5 80 159.4v42.1H14v97.8H80z"/></svg>',
			'zalo'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1193.88 419.19" style="fill: currentColor"><path d="M621,388c-37.24,25.71-77.78,35.82-122,28.84-38.83-6.11-71.38-24.35-97.28-53.88a162.36,162.36,0,0,1-10-201.74c26.32-36.67,62.06-59.2,106.78-66.44,44.42-7.2,85.18,2.72,122.08,28.33,1.07-1.22.66-2.38.67-3.43,0-5.22,0-10.45,0-15.67,0-2.31.14-2.43,2.48-2.43q31,0,62,0c2.49,0,2.63.19,2.74,2.61,0,.56,0,1.11,0,1.67V411.54c0,5.29,0,4.44-4.52,4.45-11,0-22-.2-33,.06a29.42,29.42,0,0,1-28.24-19.42A31.47,31.47,0,0,1,621,388Zm-96-36.69c50.26.67,95.78-40.17,95.86-95.47.08-53.59-43-95.69-96-95.85-51.69-.15-95.09,41.81-95.87,94.3C428.18,308.62,473.17,352.15,525,351.34Z"/><path d="M1026.3,90.78c91.52-2.15,168.58,72,167.57,166-1,90.73-75.66,164.49-168.67,162.35-88-2-160.57-74-160.39-164.63C865,163,939.37,91.68,1026.3,90.78Zm3.24,260.56a96.29,96.29,0,0,0,96.35-95.44c.52-54.84-43.32-97-96-97.45a96.45,96.45,0,1,0-.36,192.89Z"/><path d="M344.32,3.46c.66-1.1-.05-2.34.37-3.46H9.36a10.58,10.58,0,0,0-.53,3.91q0,32.37,0,64.73c0,.77,0,1.55,0,2.32a1.12,1.12,0,0,0,1,1.16c1.44.06,2.88.18,4.32.18l225.66,0c.09.56.18,1.12.26,1.68l0,0a.61.61,0,0,0-.57.68l0,.05a.5.5,0,0,0-.48.69L203.76,119l-57.85,71.48q-26.19,32.39-52.37,64.77-19.8,24.48-39.62,48.95C41.13,320,28.41,335.85,15.51,351.55,5.79,363.38.38,376.76.12,392.12,0,398.67.05,405.23,0,411.78,0,416.46-.11,416,4.08,416H315.73a26.16,26.16,0,0,0,12.49-3c9.38-5,15.08-12.64,15.91-23.39.39-5.19.29-10.43.28-15.65,0-7-.17-14-.22-21,0-4.57.27-4.07-4.17-4.07H106.36c-.88,0-1.77,0-2.66,0-.62,0-1.38.18-1.67-.78.4-1.39,1.52-2.36,2.4-3.47Q127.7,315.5,151,286.34q32.3-40.5,64.59-81,34.69-43.5,69.42-87c11.79-14.75,23.41-29.64,35.44-44.2,3.88-4.7,8.31-9,11.47-14.25.81-.27.91-1,1-1.66l1-1.7c.84-.32.86-1.05.88-1.78h0a.6.6,0,0,0,.44-.82l.7-1.35c.81-.36.83-1.09.85-1.82v0a5.88,5.88,0,0,0,2-4.67v0a3.2,3.2,0,0,0,1.1-2.8l0-.07c.78-.24.72-.7.32-1.25l.57-1.86c.82-2,2.25-3.8,1.52-6.17.07-.4.15-.81.22-1.22a14.33,14.33,0,0,0,1.05-7.32l.15-1.79a1.27,1.27,0,0,0,.73-1c.08-4.22.55-8.44-.19-12.64Z"/><path d="M818,0H745.36a11.94,11.94,0,0,0-.52,4.93V390.82c0,.66,0,1.33,0,2a12,12,0,0,0,1.79,6.58c.23.63.46,1.27.68,1.91-.07.08-.19.15-.2.22,0,.27.14.37.4.29l.71,1.41a.57.57,0,0,0,.48.77,25,25,0,0,0,8.64,8.7.57.57,0,0,0,.78.47l1.39.72c2.78,2.07,6,2.6,9.36,2.62,15,.05,30,0,45,0,4,0,4,0,4.06-4.09V5C817.9,3.32,818,1.66,818,0Z"/></svg>',

			'phone'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="fill: currentColor"><path d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z"/></svg>',
			'location' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" style="fill: currentColor"><path d="M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"/></svg>',
			'contact'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="fill: currentColor"><path d="M96 0C60.7 0 32 28.7 32 64l0 384c0 35.3 28.7 64 64 64l288 0c35.3 0 64-28.7 64-64l0-384c0-35.3-28.7-64-64-64L96 0zM208 288l64 0c44.2 0 80 35.8 80 80c0 8.8-7.2 16-16 16l-192 0c-8.8 0-16-7.2-16-16c0-44.2 35.8-80 80-80zm-32-96a64 64 0 1 1 128 0 64 64 0 1 1 -128 0zM512 80c0-8.8-7.2-16-16-16s-16 7.2-16 16l0 64c0 8.8 7.2 16 16 16s16-7.2 16-16l0-64zM496 192c-8.8 0-16 7.2-16 16l0 64c0 8.8 7.2 16 16 16s16-7.2 16-16l0-64c0-8.8-7.2-16-16-16zm16 144c0-8.8-7.2-16-16-16s-16 7.2-16 16l0 64c0 8.8 7.2 16 16 16s16-7.2 16-16l0-64z"/></svg>',
			'envelope' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="fill: currentColor"><path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48L48 64zM0 176L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-208L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/></svg>',
		];

		return ! empty( $default[ $name ] ) ? $default[ $name ] : '';
	}
}
