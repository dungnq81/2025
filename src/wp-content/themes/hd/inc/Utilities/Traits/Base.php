<?php

namespace HD\Utilities\Traits;

\defined( 'ABSPATH' ) || die;

trait Base {
	// -------------------------------------------------------------

	/**
	 * @param string $msg
	 * @param bool $autoHide
	 *
	 * @return void
	 */
	public static function messageSuccess( string $msg = 'Values saved', bool $autoHide = false ): void {
		$text  = esc_html__( $msg, TEXT_DOMAIN );
		$class = 'notice notice-success is-dismissible' . ( $autoHide ? ' dismissible-auto' : '' );
		printf(
			'<div class="%1$s"><p><strong>%2$s</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>',
			self::escAttr( $class ),
			$text
		);
	}

	// -------------------------------------------------------------

	/**
	 * @param string $msg
	 * @param bool $autoHide
	 *
	 * @return void
	 */
	public static function messageError( string $msg = 'Values error', bool $autoHide = false ): void {
		$text  = esc_html__( $msg, TEXT_DOMAIN );
		$class = 'notice notice-error is-dismissible' . ( $autoHide ? ' dismissible-auto' : '' );
		printf(
			'<div class="%1$s"><p><strong>%2$s</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>',
			self::escAttr( $class ),
			$text
		);
	}

	// -------------------------------------------------------------

	/**
	 * @param string|\WP_Error $message
	 * @param string|int $title
	 * @param string|array|int $args
	 *
	 * @return void
	 */
	public static function wpDie( string|\WP_Error $message = '', string|int $title = '', string|array|int $args = [] ): void {
		// Intentionally calling wp_die as a final error handler.
		wp_die( $message, $title, $args );
	}

	// -------------------------------------------------------------

	/**
	 * Throttled error logging with a 1‑minute throttle per unique message.
	 *
	 * @param string      $message
	 * @param int         $type
	 * @param string|null $dest
	 * @param string|null $headers
	 *
	 * @return void
	 */
	public static function errorLog( string $message, int $type = 0, ?string $dest = null, ?string $headers = null ): void {
		$key = 'hd_err_' . md5( $message );
		if ( false === get_transient( $key ) ) {
			set_transient( $key, 1, MINUTE_IN_SECONDS );
			// Intentionally calling error_log for throttled logging.
			error_log( $message, $type, $dest, $headers );
		}
	}

	// -------------------------------------------------------------

	/**
	 * Check if the current page is using a specific page template.
	 *
	 * @param string|null $template
	 *
	 * @return bool
	 */
	public static function isPageTemplate( ?string $template = null ): bool {
		if ( $template === null || ! is_page() ) {
			return false;
		}

		$current_template_slug = get_page_template_slug( get_the_ID() );
		if ( ! $current_template_slug ) {
			return false;
		}

		return $current_template_slug === trim( $template );
	}

	// -------------------------------------------------------------

	/**
	 * Check if the current page is a category page and belongs to a specific taxonomy.
	 *
	 * @param string|null $taxonomy
	 *
	 * @return bool
	 */
	public static function isTaxonomy( ?string $taxonomy = null ): bool {
		$queried_object = get_queried_object();

		if ( $taxonomy === null ) {
			return $queried_object && ! empty( $queried_object?->taxonomy );
		}

		// Validate queried object and its taxonomy.
		return $queried_object && isset( $queried_object->taxonomy ) && $queried_object->taxonomy === $taxonomy;
	}

	// --------------------------------------------------

	/**
	 * @param $content
	 *
	 * @return false|int
	 */
	public static function isXml( $content ): false|int {
		// Get the first 30 chars of the file to make the preg_match check faster.
		$xml_part = substr( $content, 0, 30 );

		return preg_match( '/<\?xml version="/', $xml_part );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $url
	 *
	 * @return bool
	 */
	public static function isUrl( ?string $url ): bool {
		// Basic URL validation using filter_var
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		// Ensure the URL has a valid scheme (http or https)
		$scheme = parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return false;
		}

		$host = parse_url( $url, PHP_URL_HOST );

		return (bool) filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME );
	}

	// --------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isMobile(): bool {
		// Fallback to WordPress function
		return wp_is_mobile();
	}

	// --------------------------------------------------

	/**
	 * @param string $version
	 *
	 * @return  bool
	 */
	public static function isPhp( string $version = '7.4' ): bool {
		static $phpVer = [];

		if ( ! isset( $phpVer[ $version ] ) ) {
			$phpVer[ $version ] = version_compare( PHP_VERSION, $version, '>=' );
		}

		return $phpVer[ $version ];
	}

	// --------------------------------------------------

	/**
	 * @param mixed $input
	 *
	 * @return bool
	 */
	public static function isInteger( mixed $input ): bool {
		return filter_var( $input, FILTER_VALIDATE_INT ) !== false;
	}

	// --------------------------------------------------

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function isEmpty( mixed $value ): bool {
		if ( is_string( $value ) ) {
			return trim( $value ) === '';
		}

		// Check for numeric and boolean values, and use empty() for others
		return ! is_numeric( $value ) && ! is_bool( $value ) && empty( $value );
	}

	// -------------------------------------------------------------

	/**
	 * Determines whether the current request is a WP_CLI request.
	 *
	 * This function checks if the WP_CLI constant is defined and true,
	 * indicating that the code is being executed in the context of
	 * the WordPress Command Line Interface.
	 *
	 * @return bool True if the current request is a WP_CLI request, false otherwise.
	 */
	public static function isWpCli(): bool {
		return defined( 'WP_CLI' ) && \WP_CLI;
	}

	// -------------------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isAdmin(): bool {
		return is_admin();
	}

	// -------------------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isLogin(): bool {
		return in_array( $GLOBALS['pagenow'], [ 'wp-login.php', 'wp-register.php' ] );
	}

	// -------------------------------------------------------------

	/**
	 * Check if a plugin is installed by getting all plugins from the plugins dir
	 *
	 * @param string $plugin_slug
	 *
	 * @return bool
	 */
	public static function checkPluginInstalled( string $plugin_slug ): bool {

		// Ensure required functions are available
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get the list of installed plugins
		$installed_plugins = get_plugins();

		// Check if the plugin slug exists in the installed plugins array
		return array_key_exists( $plugin_slug, $installed_plugins );
	}

	// -------------------------------------------------------------

	/**
	 * Check if the plugin is installed
	 *
	 * @param string $plugin_slug
	 *
	 * @return bool
	 */
	public static function checkPluginActive( string $plugin_slug ): bool {
		return self::checkPluginInstalled( $plugin_slug ) && is_plugin_active( $plugin_slug );
	}

	// -------------------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isAcfActive(): bool {
		return self::checkPluginActive( 'secure-custom-fields/secure-custom-fields.php' ) ||
		       self::checkPluginActive( 'advanced-custom-fields/acf.php' ) ||
		       self::checkPluginActive( 'advanced-custom-fields-pro/acf.php' );
	}

	// -------------------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isAcfProActive(): bool {
		return self::checkPluginActive( 'advanced-custom-fields-pro/acf.php' );
	}

	// -------------------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isPolylangActive(): bool {
		return self::checkPluginActive( 'polylang/polylang.php' ) ||
		       self::checkPluginActive( 'polylang-pro/polylang.php' );
	}

	// -------------------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isWoocommerceActive(): bool {
		return self::checkPluginActive( 'woocommerce/woocommerce.php' );
	}

	// -------------------------------------------------------------

	/**
	 * @return bool
	 */
	public static function isCf7Active(): bool {
		return self::checkPluginActive( 'contact-form-7/wp-contact-form-7.php' );
	}
}
