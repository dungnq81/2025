<?php

namespace Addons\Security;

\defined( 'ABSPATH' ) || exit;

/**
 * @author SiteGround Security
 * Modified by Gaudev
 */
final class Readme {
	// --------------------------------------------------

	public function __construct() {
		add_action( '_core_updated_successfully', [ $this, 'delete_readme' ] );
	}

	// --------------------------------------------------

	public function readme_exist(): bool {
		// Check if the readme.html file exists at the root of the application.
		return file_exists( ABSPATH . 'readme.html' );
	}

	// --------------------------------------------------

	public function delete_readme(): bool {
		// Check if the readme.html file exists in the root of the application.
		if ( ! $this->readme_exist() ) {
			return true;
		}

		// Check if file permissions are set accordingly.
		if ( (int) substr( sprintf( '%o', fileperms( ABSPATH . 'readme.html' ) ), - 3 ) <= 600 ) {
			return false;
		}

		// Try to remove the file.
		if ( @unlink( ABSPATH . 'readme.html' ) === false ) {
			return false;
		}

		return true;
	}
}
