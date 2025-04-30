<?php

namespace Addons\Optimizer;

\defined( 'ABSPATH' ) || exit;

/**
 * @author SiteGround
 * Modified by Gaudev
 */
final class Font {
	private const GOOGLE_API_URL = 'https://fonts.googleapis.com/';
	private const GOOGLE_FONTS_DISPLAY = 'swap';

	private array $options;
	private ?string $assetsDir = null; // cache/addons/fonts
	private $fs; // WP_Filesystem_Direct | WP_Filesystem_Base
	private string $regex; // Regex parts merged in constructor

	// ------------------------------------------------------

	public function __construct() {
		$this->options = \Addons\Helper::getOption( 'optimizer__options', [] );
		$this->_buildRegex();
		$this->_initFilesystem();
		$this->_initAssetsDir();
	}

	// ------------------------------------------------------

	public function run( string $html ): string {
		// Inject user‑defined preload links first.
		$html = $this->_injectFontPreload( $html );

		// Stop here if optimize disabled.
		if ( empty( $this->options['font_optimize'] ) ) {
			return $html;
		}

		$matches = $this->_collectFontLinks( $html );
		if ( ! $matches ) {
			return $html;
		}

		$parts = $this->_beautify( $this->_parseFonts( $matches ) );
		$urls  = $this->_prepareUrls( $parts );
		$tag   = $this->_getCombinedCss( $urls );

		// Insert combined tag.
		$newHtml = preg_replace( '/<\/head>/i', $tag . '</head>', $html, 1 );
		if ( is_string( $newHtml ) ) {
			$html = $newHtml;
		}

		// Remove original font links.
		foreach ( $matches as $m ) {
			$html = str_replace( $m[0], '', $html );
		}

		// Add preconnect at once.
		$preconnect = '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="preconnect" href="https://fonts.googleapis.com">';
		$newHtml    = preg_replace( '/<\/title>/i', '</title>' . $preconnect, $html, 1 );
		if ( is_string( $newHtml ) ) {
			$html = $newHtml;
		}

		return $html;
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	private function _buildRegex(): void {
		$this->regex = '~<link(?:\s+(?:(?!href\s*=\s*)[^>])+)?\s+href\s*=\s*(?P<q>["\'])((?:https?:)?//fonts\.googleapis\.com/(?P<t>css2?)(?:(?!(?P=q)).)+)(?P=q)[^>]*>~ims';
	}

	// ------------------------------------------------------

	/**
	 * @param string $html
	 *
	 * @return array
	 */
	private function _collectFontLinks( string $html ): array {
		preg_match_all( $this->regex, $html, $m, PREG_SET_ORDER );

		return $m;
	}

	// ------------------------------------------------------

	/**
	 * @param array $fonts
	 *
	 * @return array
	 */
	private function _parseFonts( array $fonts ): array {
		$out = [];
		foreach ( $fonts as $f ) {
			$url   = html_entity_decode( $f[2] );
			$query = wp_parse_url( $url, PHP_URL_QUERY );
			if ( ! $query ) {
				continue;
			}
			$args                      = wp_parse_args( $query );
			$out[ $f['t'] ]['fonts'][] = $args['family'] ?? '';
			if ( isset( $args['subset'] ) ) {
				$out[ $f['t'] ]['subset'][] = $args['subset'];
			}
		}

		return $out;
	}

	// ------------------------------------------------------

	/**
	 * @param array $parts
	 *
	 * @return array
	 */
	private function _beautify( array $parts ): array {
		foreach ( $parts as $k => $set ) {
			if ( $k === 'css2' ) {
				continue;
			}
			$set         = array_map( static fn( array $a ) => array_map( 'rawurlencode', array_unique( array_filter( $a ) ) ), $set );
			$parts[ $k ] = $set;
		}

		return $parts;
	}

	// ------------------------------------------------------

	/**
	 * @param array $fonts
	 *
	 * @return array
	 */
	private function _prepareUrls( array $fonts ): array {
		$display = self::GOOGLE_FONTS_DISPLAY ?: 'swap';
		$urls    = [];
		foreach ( $fonts as $type => $data ) {
			$url = self::GOOGLE_API_URL . $type;
			if ( $type === 'css' ) {
				$url .= '?family=' . implode( '%7C', $data['fonts'] );
			} else {
				$first = true;
				foreach ( $data['fonts'] as $fam ) {
					$url   .= ( $first ? '?' : '&' ) . 'family=' . $fam;
					$first = false;
				}
			}
			$subset = ! empty( $data['subset'] ) ? implode( ',', $data['subset'] ) : '';
			$url    .= '&display=' . $display . ( $subset ? '&subset=' . $subset : '' );
			$urls[] = $url;
		}

		return $urls;
	}

	// ------------------------------------------------------

	/**
	 * @param array $urls
	 *
	 * @return string
	 */
	private function _getCombinedCss( array $urls ): string {
		$inline = empty( $this->options['font_combined_css'] );
		$css    = '';
		$tags   = [];

		foreach ( $urls as $u ) {
			if ( $inline ) {
				$css .= $this->_fetchRemote( $u ) ?: '';
			}
			$tags[] = '<link rel="stylesheet" href="' . esc_url( $u ) . '">';
		}

		if ( $css === '' || ! $inline || ( function_exists( 'ampforwp_is_amp_endpoint' ) && \ampforwp_is_amp_endpoint() ) ) {
			return implode( '', $tags );
		}

		return '<style>' . $css . '</style>';
	}

	// ------------------------------------------------------

	/**
	 * @param string $html
	 *
	 * @return string
	 */
	private function _injectFontPreload( string $html ): string {
		$urls = $this->options['font_preload'] ?? [];
		if ( ! $urls ) {
			return $html;
		}
		$links = '';
		foreach ( $urls as $u ) {
			$links .= '<link rel="preload" as="font" href="' . esc_url( $u ) . '" crossorigin>';
		}
		$newHtml = preg_replace( '/<\/title>/i', '</title>' . $links, $html, 1 );

		return is_string( $newHtml ) ? $newHtml : $html;
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	private function _initFilesystem(): void {
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$this->fs = $wp_filesystem;
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	private function _initAssetsDir(): void {
		if ( $this->assetsDir ) {
			return;
		}
		$dir = WP_CONTENT_DIR . '/cache/addons/fonts';
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			$dir = WP_CONTENT_DIR . '/cache';
		}
		$this->assetsDir = trailingslashit( $dir );
	}

	// ------------------------------------------------------

	/**
	 * @param string $url
	 *
	 * @return string|null
	 */
	private function _fetchRemote( string $url ): ?string {
		$hash = md5( $url );
		$path = $this->assetsDir . $hash . '.css';

		if ( $this->fs->exists( $path ) ) {
			$content = $this->fs->get_contents( $path );
			if ( $content ) {
				return $content;
			}
		}

		/** @var array|\WP_Error $res */
		$res = wp_remote_get( $url, [ 'timeout' => 8 ] );
		if ( is_wp_error( $res ) || wp_remote_retrieve_response_code( $res ) !== 200 ) {
			return null;
		}
		$body = wp_remote_retrieve_body( $res );
		if ( $body === '' ) {
			return null;
		}
		if ( ! $this->fs->exists( $this->assetsDir ) ) {
			$this->fs->mkdir( $this->assetsDir, 0755 );
		}
		$this->fs->put_contents( $path, $body );

		return $body;
	}

	// ------------------------------------------------------
}
