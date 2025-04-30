<?php

namespace Addons\Optimizer;

\defined( 'ABSPATH' ) || exit;

final class Optimizer {
	private mixed $options;

	/** Maximum HTML size (bytes) we attempt to minify. */
	private const MAX_SIZE = 1_000_000; // 1MB

	// ------------------------------------------------------

	public function __construct() {
		// Initialize lazy‑load module regardless of options
		( new \Addons\Optimizer\LazyLoad\LazyLoad() );

		$this->options = \Addons\Helper::getOption( 'optimizer__options', [] );
		$this->_maybeHookBuffer();
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	private function _maybeHookBuffer(): void {
		// Skip CLI, admin, REST
		if ( defined( 'WP_CLI' ) || is_admin() || doing_action( 'rest_api_init' ) ) {
			return;
		}

		// Determine if any optimisation enabled
		$need = ! empty( $this->options['minify_html'] ) ||
		        ! empty( $this->options['font_optimize'] ) ||
		        ! empty( $this->options['font_preload'] ) ||
		        ! empty( $this->options['dns_prefetch'] );

		if ( $need ) {
			add_action( 'wp_loaded', [ $this, 'startBuffer' ], 0 );
			add_action( 'shutdown', [ $this, 'endBuffer' ], 0 );
		}
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function startBuffer(): void {
		if ( ob_get_level() === 0 && ! headers_sent() ) {
			ob_start( [ $this, 'processOutput' ] );
		}
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	public function endBuffer(): void {
		// Flush all buffers we opened (keep nesting order)
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	}

	// ------------------------------------------------------

	/**
	 * @param string $html
	 *
	 * @return string
	 */
	public function processOutput( string $html ): string {
		// Basic sanity – must be an HTML document
		if ( ! preg_match( '/<\/html>/i', $html ) ) {
			return $html;
		}

		// Skip AMP / XML pages detected by Helper
		if ( \Addons\Helper::isAmpEnabled( $html ) || \Addons\Helper::isXml( $html ) ) {
			return $html;
		}

		return $this->_optimise( $html );
	}

	// ------------------------------------------------------'

	/**
	 * @param $html
	 *
	 * @return string
	 */
	private function _optimise( $html ): string {
		// Font helper (preload / sub‑set etc.)
		$html = ( new Font() )->run( $html );

		// DNS prefetch insertion
		$html = $this->_injectDnsPrefetch( $html );

		// Minify full HTML if option enabled and size acceptable
		if ( ! empty( $this->options['minify_html'] ) && strlen( $html ) <= self::MAX_SIZE ) {
			$html = Minify_Html::minify( $html, [
				'cssMinifier'        => static fn( string $css ) => \Addons\Helper::CSSMinify( $css, false ),
				'jsMinifier'         => static fn( string $js ) => \Addons\Helper::JSMinify( $js, false ),
				'jsCleanComments'    => true,
				'preserveLineBreaks' => false,
			] );
		}

		return $html;
	}

	// ------------------------------------------------------

	/**
	 * @param string $html
	 *
	 * @return string
	 */
	private function _injectDnsPrefetch( string $html ): string {
		$urls = $this->options['dns_prefetch'] ?? [];
		if ( ! $urls ) {
			return $html;
		}

		$links = '';
		foreach ( $urls as $url ) {
			$host = wp_parse_url( esc_url_raw( $url ), PHP_URL_HOST );
			if ( ! $host ) {
				continue;
			}
			$links .= '<link rel="dns-prefetch" href="//' . esc_attr( $host ) . '" />';
		}
		if ( $links === '' ) {
			return $html;
		}

		// Insert before closing </head> (case‑insensitive, first occurrence)
		$newHtml = preg_replace( '/<\/head>/i', $links . '</head>', $html, 1 );

		return $newHtml ?: $html;
	}

	// ------------------------------------------------------
}
