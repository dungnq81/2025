<?php

namespace HD\Integration\ACF;

use HD\Utilities\Traits\Singleton;

\defined( 'ABSPATH' ) || die;

/**
 * Advanced Custom Fields
 *
 * @author Gaudev
 */
final class ACF {
	use Singleton;

	// -------------------------------------------------------------

	private function init(): void {
		// Hide the ACF Admin UI
		if ( ! \HD_Helper::development() ) {
			add_filter( 'acf/settings/show_admin', '__return_false' );
		}

		add_filter( 'acf/format_value/type=textarea', [ \HD_Helper::class, 'removeInlineJsCss' ], 11 );
		add_filter( 'acf/fields/wysiwyg/toolbars', [ $this, 'wysiwyg_toolbars' ], 98, 1 );

		add_filter( 'teeny_mce_buttons', [ $this, 'teeny_mce_buttons' ], 99, 2 );

		// auto required fields
		$fields_dir = __DIR__ . DIRECTORY_SEPARATOR . 'fields';
		\HD_Helper::createDirectory( $fields_dir );
		\HD_Helper::FQNLoad( $fields_dir, true );

		add_filter( 'wp_nav_menu_objects', [ $this, 'wp_nav_menu_objects' ], 1000, 2 );
	}

	// -------------------------------------------------------------

	/**
	 * @param $toolbars
	 *
	 * @return mixed
	 */
	public function wysiwyg_toolbars( $toolbars ): mixed {
		// Add a new toolbar called "Minimal" - this toolbar has only 1 row of buttons
		//		$toolbars['Minimal']    = [];
		//		$toolbars['Minimal'][1] = [
		//			'formatselect',
		//			'bold',
		//			'underline',
		//			'bullist',
		//			'numlist',
		//			'link',
		//			'unlink',
		//			'forecolor',
		//			//'blockquote',
		//			'table',
		//			'codesample',
		//			'subscript',
		//			'superscript',
		//			'ml_tinymce_language_select_button', // wpglobus plugin
		//			'fullscreen',
		//		];

		// remove the 'Basic' toolbar completely (if you want)
		//unset( $toolbars['Full'] );
		//unset( $toolbars['Basic'] );

		return $toolbars;
	}

	// -------------------------------------------------------------

	/**
	 * @param $teeny_mce_buttons
	 * @param $editor_id
	 *
	 * @return string[]
	 */
	public function teeny_mce_buttons( $teeny_mce_buttons, $editor_id ): array {
		return [
			'formatselect',
			'bold',
			'underline',
			'bullist',
			'numlist',
			'link',
			'unlink',
			'forecolor',
			//'blockquote',
			'table',
			'codesample',
			'subscript',
			'superscript',
			'fullscreen',
		];
	}

	// -------------------------------------------------------------

	/**
	 * @param $items
	 * @param $args
	 *
	 * @return mixed
	 */
	public function wp_nav_menu_objects( $items, $args ): mixed {
		foreach ( $items as &$item ) {
			$title = $item?->title;
			$ACF   = \HD_Helper::getFields( $item, true );

			if ( $ACF ) {
				$menu_mega             = $ACF->menu_mega ?? false;
				$menu_glyph            = $ACF->menu_glyph ?? '';
				$menu_link_class       = $ACF->menu_link_class ?? '';
				$menu_svg              = $ACF->menu_svg ?? '';
				$menu_image            = $ACF->menu_image ?? '';
				$menu_label_text       = $ACF->menu_label_text ?? '';
				$menu_label_color      = $ACF->menu_label_color ?? '';
				$menu_label_background = $ACF->menu_label_background ?? '';

				if ( $menu_mega ) {
					$item->classes[] = 'menu-mega menu-masonry';
				}

				if ( $menu_glyph ) {
					$item->classes[] = 'menu-glyph';
					$title           = '<span data-fa="' . \HD_Helper::escAttr( $menu_glyph ) . '">' . $title . '</span>';
				}

				if ( $menu_link_class ) {
					$item->menu_link_class = $menu_link_class;
				}

				if ( $menu_svg ) {
					$item->classes[] = 'menu-svg';
					$title           = $menu_svg . $title;
				}

				if ( $menu_image ) {
					$item->classes[] = 'menu-thumb';
					$title           = \HD_Helper::attachmentImageHTML( $menu_image, 'thumbnail', [ 'alt' => $item?->title ], true ) . $title;
				}

				if ( $menu_label_text ) {
					$item->classes[] = 'menu-label';

					$_css = '';
					if ( $menu_label_color ) {
						$_css .= 'color:' . $menu_label_color . ';';
					}
					if ( $menu_label_background ) {
						$_css .= 'background-color:' . $menu_label_background . ';';
					}

					$_style = $_css ? ' style="' . \HD_Helper::CSSMinify( $_css, true ) . '"' : '';
					$title  .= '<sup' . $_style . '>' . $menu_label_text . '</sup>';
				}

				$item->title = $title;
				unset( $ACF );
			}
		}

		return $items;
	}
}
