<?php

namespace Addons\AspectRatio;

\defined( 'ABSPATH' ) || exit;

final class AspectRatio {
	// --------------------------------------------------

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'aspect_ratio_enqueue_scripts' ], 99 );
	}

	// --------------------------------------------------

	public function aspect_ratio_enqueue_scripts(): void {
		$classes = [];
		$styles  = '';

		$aspect_ratio_settings = \Addons\Helper::filterSettingOptions( 'aspect_ratio', [] );
		$post_type_term        = $aspect_ratio_settings['post_type_term'] ?? [];

		if ( $post_type_term ) {
			foreach ( $post_type_term as $ar_post_type ) {

				$ratio_obj   = $this->_get_aspect_ratio( $ar_post_type );
				$ratio_class = $ratio_obj->class ?: '';
				$ratio_style = $ratio_obj->style ?: '';

				if ( $ratio_style && ! in_array( $ratio_class, $classes, false ) ) {
					$classes[] = $ratio_class;
					$styles    .= $ratio_style;
				}
			}
		}

		if ( $styles ) {
			wp_add_inline_style( 'index-css', $styles );
		}
	}

	// --------------------------------------------------

	/**
	 * @param string $post_type
	 * @param string $option
	 * @param string $default
	 *
	 * @return object
	 */
	private function _get_aspect_ratio( string $post_type = 'post', string $option = '', string $default = 'ar-3-2' ): object {
		$ratio = $this->_aspect_ratio_option( $post_type, $option );

		$ratio_x = $ratio[0] ?? '';
		$ratio_y = $ratio[1] ?? '';

		if ( ! $ratio_x || ! $ratio_y ) {
			return (object) [
				'class' => $default,
				'style' => '',
			];
		}

		$ratio_style = '';
		$ratio_class = 'ar-' . $ratio_x . '-' . $ratio_y;

		$aspect_ratio_settings   = \Addons\Helper::filterSettingOptions( 'aspect_ratio', [] );
		$ar_aspect_ratio_default = $aspect_ratio_settings['aspect_ratio_default'] ?? [];

		if (
			is_array( $ar_aspect_ratio_default ) &&
			! in_array( $ratio_x . '-' . $ratio_y, $ar_aspect_ratio_default, false )
		) {
			$css = new \Addons\Css();
			$css->set_selector( '.' . $ratio_class )
			    ->add_property( 'aspect-ratio', $ratio_x . '/' . $ratio_y );

			$ratio_style = $css->css_output();
		}

		return (object) [
			'class' => $ratio_class,
			'style' => $ratio_style,
		];
	}

	// --------------------------------------------------

	/**
	 * @param string $post_type
	 * @param string $option
	 *
	 * @return array|string
	 */
	private function _aspect_ratio_option( string $post_type, string $option ): array|string {
		$post_type = $post_type ?: 'post';
		$option    = $option ?: 'aspect_ratio__options';

		$aspect_ratio_options = \Addons\Helper::getOption( $option );
		$width                = $aspect_ratio_options[ 'ar-' . $post_type . '-width' ] ?? '';
		$height               = $aspect_ratio_options[ 'ar-' . $post_type . '-height' ] ?? '';

		return ( $width && $height ) ? [ $width, $height ] : '';
	}
}
