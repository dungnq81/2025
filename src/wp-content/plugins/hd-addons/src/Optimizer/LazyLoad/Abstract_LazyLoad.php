<?php

namespace Addons\Optimizer\LazyLoad;

\defined( 'ABSPATH' ) || exit;

/**
 * @author SiteGround
 * Modified by Gaudev
 */
abstract class Abstract_LazyLoad {

	// Regex for class matching.
	public string $regex_classes = '/class=["\'](.*?)["\']/is';

	public string $regexp;
	public string $regex_replaced;
	public array $patterns;
	public array $replacements;

	// ------------------------------------------------------

	// Add a class-name to the HTML element.
	abstract public function add_lazyload_class( $element );

	// ------------------------------------------------------

	/**
	 * @param $content
	 *
	 * @return bool
	 */
	public function should_process( $content ): bool {
		return empty( $content ) ||
		       is_admin() ||
		       wp_doing_ajax() ||
		       \Addons\Helper::isAmpEnabled( $content );
	}

	// ------------------------------------------------------

	/**
	 * @param $content
	 *
	 * @return mixed
	 */
	public function filter_html( $content ): mixed {
		// Bail if it's feed, ajax, admin, amp... or if the content is empty.
		if ( $this->should_process( $content ) ) {
			return $content;
		}

		$lazyload_exclude  = \Addons\Helper::filterSettingOptions( 'lazyload_exclude', [] );
		$optimizer_options = \Addons\Helper::getOption( 'optimizer__options' );

		// Check for items.
		preg_match_all( $this->regexp, $content, $matches );

		$search  = [];
		$replace = [];

		// Check for specific asset being excluded.
		$excluded_all = array_unique(
			array_merge(
				$lazyload_exclude ?? [],
				$optimizer_options['lazyload_exclude'] ?? []
			)
		);

		foreach ( $matches[0] as $item ) {
			// Skip already replaced item.
			if ( preg_match( $this->regex_replaced, $item ) ) {
				continue;
			}

			// Check if we have a filter for excluding specific asset from being lazily loaded.
			if ( ! empty( $excluded_all ) ) {
				// Match the url of the asset.
				preg_match( '~(?:src=")([^"]*)"~', $item, $src_match );

				// If we have a match and the array is part of the excluded assets bail from lazy loading.
				if ( ! empty( $src_match ) ) {
					$item_filename = basename( $src_match[1] );
					if ( in_array( $item_filename, $excluded_all, false ) ) {
						continue;
					}
				}
			}

			// Process <picture> elements containing <img>
			if ( preg_match( '/<picture([^>]*)>(.*?)<img([^>]*)\/>(.*?)<\/picture>/is', $item, $picture_match ) ) {
				$picture_open  = '<picture' . $picture_match[1] . '>';
				$picture_inner = $picture_match[2];
				$img_tag       = '<img' . $picture_match[3] . '/>';
				$picture_close = '</picture>';

				// Add "lazy" class to the <img> inside <picture>
				if ( preg_match( '/class="([^"]*)"/i', $img_tag, $class_match ) ) {
					$existing_classes = $class_match[1];

					// Ensure "lazy" is not duplicated
					$modified_img = ! str_contains( $existing_classes, 'lazy' ) ? str_replace( $class_match[0], 'class="' . $existing_classes . ' lazy"', $img_tag ) : $img_tag;

				} else {
					$modified_img = $this->add_lazyload_class( $img_tag );
				}

				$orig_item = $picture_open . $picture_inner . $modified_img . $picture_close;
			} else {

				// Do some checking if there are any class matches.
				preg_match( $this->regex_classes, $item, $class_matches );

				if ( ! empty( $class_matches[1] ) ) {
					$classes      = $class_matches[1];
					$item_classes = explode( ' ', $class_matches[1] );

					// Check if the item has ignored class and bail if it has.
					if ( array_intersect( $item_classes, $excluded_all ) ) {
						continue;
					}

					// Add lazy class to the existing classes.
					$orig_item = ! str_contains( $classes, 'lazy' ) ? str_replace( $classes, $classes . ' lazy', $item ) : $item;

				} else {
					$orig_item = $this->add_lazyload_class( $item );
				}
			}

			// Finally, do the search/replace and return modified content.
			$new_item = preg_replace(
				$this->patterns,
				$this->replacements,
				$orig_item
			);

			$search[]  = $item;
			$replace[] = $new_item;
		}

		return str_replace( $search, $replace, $content );
	}
}
