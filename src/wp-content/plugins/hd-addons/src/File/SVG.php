<?php

namespace Addons\File;

use enshrined\svgSanitize\data\AllowedAttributes;
use enshrined\svgSanitize\data\AllowedTags;
use enshrined\svgSanitize\Sanitizer;

\defined( 'ABSPATH' ) || exit;

/**
 * SVG support in WordPress
 *
 * @author ShortPixel
 * Modified by Gaudev
 */
final class SVG {

	private Sanitizer $sanitizer;
	private string $svg_option;

	// ------------------------------------------------------

	public function __construct() {
		$file_options     = \Addons\Helper::getOption( 'file__options' );
		$this->svg_option = $file_options['svgs'] ?? 'disable';

		if ( $this->svg_option !== 'disable' ) {
			$this->_init_svg();
		}
	}

	// ------------------------------------------------------

	private function _init_svg(): void {
		$this->sanitizer = new Sanitizer();
		$this->sanitizer->removeXMLTag( true );
		$this->sanitizer->minify( true );

		add_action( 'admin_init', [ $this, 'add_svg_support' ] );
		add_action( 'admin_footer', [ $this, 'fix_svg_thumbnail_size' ] );

		add_filter( 'wp_handle_upload_prefilter', [ $this, 'wp_handle_upload_prefilter' ] );
		add_filter( 'wp_check_filetype_and_ext', [ $this, 'wp_check_filetype_and_ext' ], 100, 4 );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'wp_generate_attachment_metadata' ], 10, 2 );

		add_filter( 'upload_mimes', [ $this, 'add_svg_mime' ] );
		add_filter( 'fl_module_upload_regex', [ $this, 'fl_module_upload_regex' ], 10, 4 );
		add_filter( 'render_block', [ $this, 'fix_missing_width_height_on_image_block' ], 10, 2 );
		add_filter( 'intermediate_image_sizes_advanced', [ $this, 'disable_upload_sizes' ], 101, 3 );
	}

	// ------------------------------------------------------

	/**
	 * @param array $sizes
	 * @param array $metadata
	 * @param int $attachment_id
	 *
	 * @return array
	 */
	public function disable_upload_sizes( array $sizes, array $metadata, int $attachment_id ): array {
		if ( get_post_mime_type( $attachment_id ) === 'image/svg+xml' ) {
			$sizes = [];
		}

		// Return sizes you want to create from image (None if image is svg, svgz.)
		return $sizes;
	}

	// ------------------------------------------------------

	/**
	 * @param $block_content
	 * @param $block
	 *
	 * @return mixed
	 */
	public function fix_missing_width_height_on_image_block( $block_content, $block ): mixed {
		if ( $block['blockName'] === 'core/image' &&
		     isset( $block['attrs']['id'] ) &&
		     ! str_contains( $block_content, 'width=' ) &&
		     ! str_contains( $block_content, 'height=' ) &&
		     get_post_mime_type( $block['attrs']['id'] ) === 'image/svg+xml'
		) {
			$svg_path   = get_attached_file( $block['attrs']['id'] );
			$dimensions = $this->svg_dimensions( $svg_path );

			$block_content = str_replace( '<img ', '<img width="' . $dimensions->width . '" height="' . $dimensions->height . '" ', $block_content );
		}

		return $block_content;
	}

	// ------------------------------------------------------

	/**
	 * @param $regex
	 * @param $type
	 * @param $ext
	 * @param $file
	 *
	 * @return mixed
	 */
	public function fl_module_upload_regex( $regex, $type, $ext, $file ): mixed {
		if ( $ext === 'svg' || $ext === 'svgz' ) {
			$regex['photo'] = str_replace( '|png|', '|png|svgz?|', $regex['photo'] );
		}

		return $regex;
	}

	// ------------------------------------------------------

	/**
	 * @param $metadata
	 * @param $attachment_id
	 *
	 * @return mixed
	 */
	public function wp_generate_attachment_metadata( $metadata, $attachment_id ): mixed {
		if ( get_post_mime_type( $attachment_id ) === 'image/svg+xml' ) {
			$svg_path           = get_attached_file( $attachment_id );
			$dimensions         = $this->svg_dimensions( $svg_path );
			$metadata['width']  = $dimensions->width;
			$metadata['height'] = $dimensions->height;
		}

		return $metadata;
	}

	// ------------------------------------------------------

	/**
	 * @param $filetype_ext_data
	 * @param $file
	 * @param $filename
	 * @param $mimes
	 *
	 * @return mixed
	 */
	public function wp_check_filetype_and_ext( $filetype_ext_data, $file, $filename, $mimes ): mixed {
		if ( $this->svg_option !== 'disable' && current_user_can( 'upload_files' ) ) {
			if ( str_ends_with( $filename, '.svg' ) ) {
				$filetype_ext_data['ext']  = 'svg';
				$filetype_ext_data['type'] = 'image/svg+xml';
			} elseif ( str_ends_with( $filename, '.svgz' ) ) {
				$filetype_ext_data['ext']  = 'svgz';
				$filetype_ext_data['type'] = 'image/svg+xml';
			}
		}

		return $filetype_ext_data;
	}

	// ------------------------------------------------------

	/**
	 * @param array $mimes
	 *
	 * @return array
	 */
	public function add_svg_mime( array $mimes = [] ): array {
		if ( $this->svg_option !== 'disable' && current_user_can( 'upload_files' ) ) {
			$mimes['svg']  = 'image/svg+xml';
			$mimes['svgz'] = 'image/svg+xml';
		}

		return $mimes;
	}

	// ------------------------------------------------------

	public function fix_svg_thumbnail_size(): void {
		echo '<style>.attachment-info .thumbnail img[src$=".svg"],#postimagediv .inside img[src$=".svg"]{width:100%;height:auto}</style>';
	}

	// ------------------------------------------------------

	public function add_svg_support(): void {
		ob_start( static function ( $content ) {
			return apply_filters( 'final_output', $content );
		} );

		add_filter( 'final_output', [ $this, 'final_output' ] );
		add_filter( 'wp_prepare_attachment_for_js', [ $this, 'wp_prepare_attachment_for_js' ], 10, 3 );
	}

	// ------------------------------------------------------

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function final_output( $content ): string {
		return str_replace( [
			'<# } else if ( \'image\' === data.type && data.sizes && data.sizes.full ) { #>',
			'<# } else if ( \'image\' === data.type && data.sizes ) { #>',
		], [
			'<# } else if ( \'svg+xml\' === data.subtype ) { #>
					<img class="details-image" src="{{ data.url }}" draggable="false" />
				<# } else if ( \'image\' === data.type && data.sizes && data.sizes.full ) { #>',
			'<# } else if ( \'svg+xml\' === data.subtype ) { #>
					<div class="centered">
						<img src="{{ data.url }}" class="thumbnail" draggable="false" />
					</div>
				<# } else if ( \'image\' === data.type && data.sizes ) { #>',
		], $content );
	}

	// ------------------------------------------------------

	/**
	 * @param $response
	 * @param $attachment
	 * @param $meta
	 *
	 * @return mixed
	 */
	public function wp_prepare_attachment_for_js( $response, $attachment, $meta ): mixed {
		if ( (string) $response['mime'] === 'image/svg+xml' && empty( $response['sizes'] ) ) {
			$svg_path = get_attached_file( $attachment->ID );
			if ( ! file_exists( $svg_path ) ) {
				$svg_path = $response['url'];
			}

			$dimensions        = $this->svg_dimensions( $svg_path );
			$response['sizes'] = [
				'full' => [
					'url'         => $response['url'],
					'width'       => $dimensions->width,
					'height'      => $dimensions->height,
					'orientation' => $dimensions->width > $dimensions->height ? 'landscape' : 'portrait',
				],
			];
		}

		return $response;
	}

	// ------------------------------------------------------

	/**
	 * @param $svg
	 *
	 * @return object
	 */
	public function svg_dimensions( $svg ): object {
		$svg    = simplexml_load_string( file_get_contents( $svg ) );
		$width  = 0;
		$height = 0;
		if ( $svg ) {
			$attributes = $svg->attributes();
			if ( isset( $attributes->width, $attributes->height ) ) {
				if ( ! str_ends_with( trim( $attributes->width ), '%' ) ) {
					$width = (float) $attributes->width;
				}
				if ( ! str_ends_with( trim( $attributes->height ), '%' ) ) {
					$height = (float) $attributes->height;
				}
			}
			if ( ( ! $width || ! $height ) && isset( $attributes->viewBox ) ) {
				$sizes = explode( ' ', $attributes->viewBox );
				if ( isset( $sizes[2], $sizes[3] ) ) {
					$width  = (float) $sizes[2];
					$height = (float) $sizes[3];
				}
			}
		}

		return (object) [ 'width' => $width, 'height' => $height ];
	}

	// ------------------------------------------------------

	/**
	 * @param $file
	 *
	 * @return mixed
	 */
	public function wp_handle_upload_prefilter( $file ): mixed {
		if ( (string) $file['type'] === 'image/svg+xml' &&
		     (string) $this->svg_option === 'sanitized' &&
		     current_user_can( 'upload_files' ) &&
		     ! $this->sanitize( $file['tmp_name'] )
		) {
			$file['error'] = __( 'This SVG can not be sanitized.', ADDONS_TEXTDOMAIN );
		}

		return $file;
	}

	// ------------------------------------------------------

	/**
	 * @param $file
	 *
	 * @return bool
	 */
	public function sanitize( $file ): bool {
		$svg_code = file_get_contents( $file );
		if ( $is_zipped = $this->is_gzipped( $svg_code ) ) {
			$svg_code = gzdecode( $svg_code );

			if ( ! $svg_code ) {
				return false;
			}
		}

		$this->sanitizer->setAllowedTags( new AllowedTags() );
		$this->sanitizer->setAllowedAttrs( new AllowedAttributes() );

		$clean_svg_code = $this->sanitizer->sanitize( $svg_code );

		if ( ! $clean_svg_code ) {
			return false;
		}

		if ( $is_zipped ) {
			$clean_svg_code = gzencode( $clean_svg_code );
		}

		file_put_contents( $file, $clean_svg_code );

		return true;
	}

	// ------------------------------------------------------

	/**
	 * @param $svg_code
	 *
	 * @return bool
	 */
	public function is_gzipped( $svg_code ): bool {
		if ( function_exists( 'mb_strpos' ) ) {
			return mb_strpos( $svg_code, "\x1f" . "\x8b" . "\x08" ) === 0;
		}

		return str_starts_with( $svg_code, "\x1f" . "\x8b" . "\x08" );
	}
}
