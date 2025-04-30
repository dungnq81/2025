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
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 999 );

		// Admin / login assets
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ], 39, 1 );
		add_filter( 'login_headertext', [ $this, 'login_headertext' ] );
		add_filter( 'login_headerurl', [ $this, 'login_headerurl' ] );
		add_action( 'login_enqueue_scripts', [ $this, 'login_enqueue_script' ], 31 );

		// Script attribute helper
		add_action( 'script_loader_tag', [ $this, 'script_loader_tag' ], 11, 3 );
	}

	// -------------------------------------------------------------

	/**
	 * Main bootstrap after all plugins loaded
	 *
	 * @return void
	 */
	public function plugins_loaded(): void {
		// Classic Editor
		if ( Helper::checkPluginActive( 'classic-editor/classic-editor.php' ) ) {
			remove_action( 'admin_init', [ \Classic_Editor::class, 'register_settings' ] );
		}

		// Load modules from YAML (cache to avoid I/O)
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
	public function script_loader_tag( string $tag, string $handle, string $src ): string {
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
	public function admin_enqueue_scripts( $hook ): void {
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

		wp_enqueue_style( '_vendor-css', ADDONS_URL . 'assets/css/_vendor.css', [], $version );
		wp_enqueue_style( 'addon-css', ADDONS_URL . 'assets/css/addon-css.css', [ '_vendor-css' ], $version );

		wp_register_script( 'select2-js', ADDONS_URL . 'assets/js/select2.full.min.js', [ 'jquery' ], $version, true );
		wp_enqueue_script( 'addon-js', ADDONS_URL . 'assets/js/addon.js', [
			'select2-js',
			'wp-color-picker'
		], $version, true );
		wp_script_add_data( 'addon-js', 'addon', [ 'module', 'defer' ] );

		wp_enqueue_style( 'wp-codemirror' );
		wp_localize_script( 'addon-js', 'codemirror_settings', [
			'codemirror_css'  => wp_enqueue_code_editor( [ 'type' => 'text/css' ] ),
			'codemirror_html' => wp_enqueue_code_editor( [ 'type' => 'text/html' ] ),
		] );
	}

	// -------------------------------------------------------------

	/**
	 * @return void
	 */
	public function login_enqueue_script(): void {
		$version = Helper::version();

		wp_enqueue_style( 'login-css', ADDONS_URL . 'assets/css/login-css.css', [], $version );
		wp_enqueue_script( 'login-js', ADDONS_URL . 'assets/js/login.js', [ 'jquery' ], $version, true );
		wp_script_add_data( 'login-js', 'addon', [ 'module', 'async' ] );

		$default_logo    = '';
		$default_bg = '';

		//$default_logo = ADDONS_URL . 'assets/img/logo.png';
		//$default_bg   = ADDONS_URL . 'assets/img/login-bg.jpg';

		// scripts / styles
		$logo     = esc_url_raw( Helper::getThemeMod( 'login_page_logo_setting' ) ?: $default_logo );
		$bg_img   = esc_url_raw( Helper::getThemeMod( 'login_page_bgimage_setting' ) ?: $default_bg );
		$bg_color = sanitize_hex_color( Helper::getThemeMod( 'login_page_bgcolor_setting' ) );

		$css = new \Addons\CSS();

		if ( $bg_img ) {
			$css->set_selector( 'body.login' )
			    ->add_property( 'background-image', "url({$bg_img})" );
		}

		if ( $bg_color ) {
			$css->set_selector( 'body.login' )
			    ->add_property( 'background-color', $bg_color )
			    ->set_selector( 'body.login:before' )
			    ->add_property( 'background', 'none' )
			    ->add_property( 'opacity', 1 );
		}

		if ( $logo ) {
			$css->set_selector( 'body.login #login h1 a' )
			    ->add_property( 'background-image', "url({$logo})" );
		}

		if ( $inline = $css->css_output() ) {
			wp_add_inline_style( 'login-css', $inline );
		}
	}

	// -------------------------------------------------------------

	/**
	 * @return mixed|string|null
	 */
	public function login_headertext(): mixed {
		return Helper::getThemeMod( 'login_page_headertext_setting' ) ?: get_bloginfo( 'name' );
	}

	// -------------------------------------------------------------

	/**
	 * @return mixed|string|null
	 */
	public function login_headerurl(): mixed {
		return Helper::getThemeMod( 'login_page_headerurl_setting' ) ?: site_url( '/' );
	}
}
