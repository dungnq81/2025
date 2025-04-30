<?php

namespace Addons\BaseSlug;

\defined( 'ABSPATH' ) || exit;

final class BaseSlug {

	private mixed $base_slug_post_type;
	private mixed $base_slug_taxonomy;

	// ------------------------------------------------------

	public function __construct() {
		$base_slug_options = \Addons\Helper::getOption( 'base_slug__options' );

		$this->base_slug_post_type = $base_slug_options['base_slug_post_type'] ?? [];
		$this->base_slug_taxonomy  = $base_slug_options['base_slug_taxonomy'] ?? [];

		( new Rewrite_PostType() )->run();
		( new Rewrite_Taxonomy() )->run();

		// rewrite_rules_array
		if ( ! empty( $this->base_slug_taxonomy ) || in_array( 'product', $this->base_slug_post_type, false ) ) {
			add_filter( 'rewrite_rules_array', [ $this, 'add_rewrite_rules' ], 99 );
		}
	}

	// ------------------------------------------------------

	/**
	 * @param $rules
	 *
	 * @return array
	 */
	public function add_rewrite_rules( $rules ): array {
		global $wp_rewrite;
		wp_cache_flush();

		$this->_lang_remove_term_filters();

		$category_rules    = [];
		$tag_rules         = [];
		$product_rules     = [];
		$product_cat_rules = [];
		$product_tag_rules = [];
		$taxonomy_rules    = [];

		$taxonomies = get_taxonomies(
			[
				'show_ui' => true,
				'public'  => true,
			],
			'objects'
		);

		foreach ( $taxonomies as $custom_tax ) {

			// ----------------------------------
			// built-in
			// ----------------------------------
			if ( $custom_tax->_builtin && in_array( $custom_tax->name, $this->base_slug_taxonomy, false ) ) {

				// ----------------------------------
				// category
				// ----------------------------------
				if ( $custom_tax->name === 'category' ) {

					// Redirect support from the old category base
					$old_category_base = trim( str_replace( '%category%', '(.+)', $wp_rewrite->get_category_permastruct() ), '/' );

					$categories = get_categories( [ 'hide_empty' => false ] );
					if ( $categories ) {

						foreach ( $categories as $category ) {
							$category_slug = $category->slug;

							if ( (int) $category->parent === (int) $category->cat_ID ) {
								$category->parent = 0;
							} elseif ( (int) $category->parent !== 0 ) {
								$category_slug = get_category_parents( $category->parent, false, '/', true ) . $category_slug;
							}

							$category_rules += [
								'(' . $category_slug . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'                => 'index.php?category_name=$matches[1]&feed=$matches[2]',
								'(' . $category_slug . ')/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$' => 'index.php?category_name=$matches[1]&paged=$matches[2]',
								'(' . $category_slug . ')/embed/?$'                                             => 'index.php?category_name=$matches[1]&embed=true',
								'(' . $category_slug . ')/?$'                                                   => 'index.php?category_name=$matches[1]',
								$old_category_base . '$'                                                        => 'index.php?addons_category_redirect=$matches[1]',
							];
						}
					}
				}

				// ----------------------------------
				// post_tag
				// ----------------------------------
				if ( $custom_tax->name === 'post_tag' ) {
					$tags = get_tags( [ 'hide_empty' => false ] );
					if ( $tags ) {

						foreach ( $tags as $tag ) {
							$old_base = trim( str_replace( '%post_tag%', '(.+)', $wp_rewrite->get_tag_permastruct() ), '/' );

							$tag_rules += [
								'(' . $tag->slug . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'                => 'index.php?tag=$matches[1]&feed=$matches[2]',
								'(' . $tag->slug . ')/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$' => 'index.php?tag=$matches[1]&paged=$matches[2]',
								'(' . $tag->slug . ')/?$'                                                   => 'index.php?tag=$matches[1]',
								$old_base . '$'                                                             => 'index.php?addons_category_redirect=$matches[1]',
							];
						}
					}
				}
			}

			// ----------------------------------
			// product_cat
			// ----------------------------------
			if ( $custom_tax->name === 'product_cat' && \Addons\Helper::checkPluginActive( 'woocommerce/woocommerce.php' ) ) {
				$permalink_structure = wc_get_permalink_structure();

				$old_category_base = trim( $permalink_structure['category_rewrite_slug'], '/' );
				$category_base     = in_array( 'product_cat', $this->base_slug_taxonomy, false ) ? '' : $old_category_base . '/';
				$use_parent_slug   = str_contains( $permalink_structure['product_rewrite_slug'], '%product_cat%' );

				foreach ( $this->_get_categories( 'product_cat' ) as $category ) {
					$cat_path = $this->_get_category_fullpath( $category, 'product_cat' );
					$cat_slug = $category_base . $cat_path;

					$product_cat_rules += [
						'(' . $cat_slug . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'                => 'index.php?product_cat=$matches[1]&feed=$matches[2]',
						'(' . $cat_slug . ')/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$' => 'index.php?product_cat=$matches[1]&paged=$matches[2]',
						'(' . $cat_slug . ')/embed/?$'                                             => 'index.php?product_cat=$matches[1]&embed=true',
						'(' . $cat_slug . ')/?$'                                                   => 'index.php?product_cat=$matches[1]',
						$old_category_base . '/(.+)$'                                              => 'index.php?addons_category_redirect=$matches[1]',
					];

					if ( $use_parent_slug && in_array( 'product', $this->base_slug_post_type, false ) ) {
						$product_rules += [
							$cat_path . '/([^/]+)/?$'                                                           => 'index.php?product=$matches[1]',
							$cat_path . '/([^/]+)/' . $wp_rewrite->comments_pagination_base . '-([0-9]{1,})/?$' => 'index.php?product=$matches[1]&cpage=$matches[2]',
						];
					}
				}
			}

			// ----------------------------------
			// product_tag
			// ----------------------------------
			if (
				$custom_tax->name === 'product_tag' &&
				in_array( 'product_tag', $this->base_slug_taxonomy, false ) &&
				\Addons\Helper::checkPluginActive( 'woocommerce/woocommerce.php' )
			) {
				$permalink_structure = wc_get_permalink_structure();
				$old_category_base   = trim( $permalink_structure['tag_rewrite_slug'], '/' );

				foreach ( $this->_get_categories( 'product_tag' ) as $category ) {
					$cat_slug          = $category['slug'];
					$product_tag_rules += [
						'(' . $cat_slug . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'                => 'index.php?product_tag=$matches[1]&feed=$matches[2]',
						'(' . $cat_slug . ')/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$' => 'index.php?product_tag=$matches[1]&paged=$matches[2]',
						'(' . $cat_slug . ')/embed/?$'                                             => 'index.php?product_tag=$matches[1]&embed=true',
						'(' . $cat_slug . ')/?$'                                                   => 'index.php?product_tag=$matches[1]',
						$old_category_base . '/(.+)$'                                              => 'index.php?addons_category_redirect=$matches[1]',
					];
				}
			}

			// ----------------------------------
			// Custom taxonomy
			// ----------------------------------
			if (
				! $custom_tax->_builtin &&
				$custom_tax->name !== 'product_cat' &&
				$custom_tax->name !== 'product_tag' &&
				in_array( $custom_tax->name, $this->base_slug_taxonomy, false )
			) {
				// Redirect support from the old category base
				$old_taxonomy_base = trim( str_replace( '%' . $custom_tax->name . '%', '(.+)', $wp_rewrite->get_extra_permastruct( $custom_tax->name ) ), '/' );

				$taxonomies = $this->_get_categories( $custom_tax->name );
				foreach ( $taxonomies as $taxonomy ) {
					$taxonomy_slug = $this->_get_category_fullpath( $taxonomy, $custom_tax->name );

					$taxonomy_rules += [
						'(' . $taxonomy_slug . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'                => 'index.php?' . $custom_tax->name . '=$matches[1]&feed=$matches[2]',
						'(' . $taxonomy_slug . ')/embed/?$'                                             => 'index.php?' . $custom_tax->name . '=$matches[1]&embed=true',
						'(' . $taxonomy_slug . ')/' . $wp_rewrite->pagination_base . '/?([0-9]{1,})/?$' => 'index.php?' . $custom_tax->name . '=$matches[1]&paged=$matches[2]',
						'(' . $taxonomy_slug . ')/?$'                                                   => 'index.php?' . $custom_tax->name . '=$matches[1]',
						$old_taxonomy_base . '$'                                                        => 'index.php?addons_category_redirect=$matches[1]',
					];
				}
			}
		}

		$this->_lang_restore_term_filters();

		$rules       = empty( $rules ) ? [] : $rules;
		$added_rules = array_merge( $category_rules + $tag_rules + $product_rules + $product_cat_rules + $product_tag_rules + $taxonomy_rules );

		return array_merge( $added_rules, $rules );
	}

	// ------------------------------------------------------

	/**
	 * @param string $taxonomy
	 *
	 * @return array
	 */
	private function _get_categories( string $taxonomy = 'category' ): array {
		$categories = get_categories(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		$slugs = [];
		foreach ( $categories as $category ) {
			$slugs[ $category->term_id ] = [
				'parent' => $category->parent,
				'slug'   => $category->slug,
			];
		}

		return $slugs;
	}

	// ------------------------------------------------------

	/**
	 * @param array $category
	 * @param string $taxonomy
	 *
	 * @return string
	 */
	private function _get_category_fullpath( array $category, string $taxonomy = 'category' ): string {
		$categories = $this->_get_categories( $taxonomy );
		$parent     = $category['parent'];

		if ( $parent > 0 && array_key_exists( $parent, $categories ) ) {
			return $this->_get_category_fullpath( $categories[ $parent ], $taxonomy ) . '/' . $category['slug'];
		}

		return $category['slug'];
	}

	// ------------------------------------------------------

	private function _lang_remove_term_filters(): void {
		// WPML
		// Polylang
	}

	// ------------------------------------------------------

	private function _lang_restore_term_filters(): void {
		// WPML
		// Polylang
	}

	// ------------------------------------------------------

	public function flush_rules(): void {
		( new Rewrite_Taxonomy() )->flush_rules();
	}

	// ------------------------------------------------------

	public function reset_all(): void {
		$custom_base_slug_options = [
			'base_slug_post_type' => [],
			'base_slug_taxonomy'  => [],
		];

		update_option( 'base_slug__options', $custom_base_slug_options );

		$this->flush_rules();
	}
}
