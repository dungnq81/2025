<?php

namespace Addons;

use Addons\ThirdParty\ACF;
use Addons\ThirdParty\CF7;
use Addons\ThirdParty\Faker;
use Addons\ThirdParty\RankMath;

\defined( 'ABSPATH' ) || exit;

/**
 * Addons Class
 *
 * @author Gaudev
 */
final class Addons {
	// -------------------------------------------------------------

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'pluginsLoaded' ], 999 );

		// Admin Assets / Script attribute
		add_action( 'admin_enqueue_scripts', [ $this, 'adminEnqueueAssets' ], 39, 1 );
		add_action( 'script_loader_tag', [ $this, 'scriptLoaderTag' ], 11, 3 );
	}

	// -------------------------------------------------------------

	/**
	 * Main bootstrap after all plugins loaded
	 *
	 * @return void
	 */
	public function pluginsLoaded(): void {
		// Classic Editor
		if ( Helper::checkPluginActive( 'classic-editor/classic-editor.php' ) ) {
			remove_action( 'admin_init', [ \Classic_Editor::class, 'register_settings' ] );
		}

		// Load modules from YAML config
		$modules = wp_cache_get( 'addons_modules' );
		if ( false === $modules ) {
			$modules = Helper::loadYaml( ADDONS_PATH . 'config.yaml' ) ?: [];
			wp_cache_set( 'addons_modules', $modules, '', 12 * HOUR_IN_SECONDS );
		}

		foreach ( $modules as $slug => $enabled ) {
			if ( ! $enabled ) {
				continue;
			}

			// Skip Woo module nếu Woo chưa active
			if ( 'woocommerce' === (string) $slug && ! Helper::checkPluginActive( 'woocommerce/woocommerce.php' ) ) {
				continue;
			}

			$className = Helper::capitalizedSlug( $slug, true );
			$classFQN  = "\\Addons\\{$className}\\{$className}";
			class_exists( $classFQN ) && ( new $classFQN() );
		}

		// Third‑party integrations
		Helper::isRankMathActive() && class_exists( RankMath::class ) && new RankMath();
		Helper::isAcfActive() && class_exists( ACF::class ) && new ACF();
		Helper::isCf7Active() && class_exists( CF7::class ) && new CF7();

		class_exists( Faker::class ) && new Faker();
	}

	// -------------------------------------------------------------

	/**
	 * Inject extra attributes (`defer`, `module`…) to script tag
	 *
	 * @param string $tag
	 * @param string $handle
	 * @param string $src
	 *
	 * @return string
	 */
	public function scriptLoaderTag( string $tag, string $handle, string $src ): string {
		$reg = wp_scripts()->registered[ $handle ] ?? null;
		if ( ! $reg || empty( $reg->extra['addon'] ) ) {
			return $tag;
		}

		$extras = is_array( $reg->extra['addon'] )
			? $reg->extra['addon']
			: explode( ' ', $reg->extra['addon'] );

		foreach ( $extras as $attr ) {
			if ( 'defer' === $attr ) {
				$attr = 'defer data-wp-strategy="defer"';
			}

			if ( 'module' === $attr && ! str_contains( $tag, 'type="module"' ) ) {
				$tag = preg_replace( '#(?=></script>)#', ' type="module"', $tag, 1 );
			} elseif ( ! preg_match( "#\s$attr(=|>|\s)#", $tag ) ) {
				$tag = preg_replace( '#(?=></script>)#', " $attr", $tag, 1 );
			}
		}

		return $tag;
	}

	// -------------------------------------------------------------

	/**
	 * @param $hook
	 *
	 * @return void
	 */
	public function adminEnqueueAssets( $hook ): void {
		$version = Helper::version();

		// addon page settings
		$allowed = [
			'toplevel_page_addon-settings',
			'addons_page_server-info',
		];

		if ( ! in_array( $hook, $allowed, true ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		Asset::enqueueStyle( '_vendor-css', ADDONS_URL . 'assets/css/_vendor.css', [], $version );
		Asset::enqueueStyle( 'addon-css', ADDONS_URL . 'assets/css/addon.css', [ '_vendor-css' ], $version );
		Asset::enqueueScript( 'addon-js', ADDONS_URL . 'assets/js/addon.js', [ 'wp-color-picker' ], $version, true, [ 'module', 'defer' ] );

		wp_enqueue_style( 'wp-codemirror' );

		$l10n = [
			'codemirror_css'  => wp_enqueue_code_editor( [ 'type' => 'text/css' ] ),
			'codemirror_html' => wp_enqueue_code_editor( [ 'type' => 'text/html' ] ),
		];
		Asset::localize( 'addon-js', 'codemirror_settings', $l10n );
	}
}
