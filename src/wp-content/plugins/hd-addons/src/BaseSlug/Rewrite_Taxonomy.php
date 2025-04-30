<?php

namespace Addons\BaseSlug;

\defined( 'ABSPATH' ) || exit;

class Rewrite_Taxonomy {
	private mixed $base_slug_taxonomy;

	public function __construct() {
		$custom_base_slug_options = \Addons\Helper::getOption( 'base_slug__options', [] );
		$this->base_slug_taxonomy = $custom_base_slug_options['base_slug_taxonomy'] ?? [];
	}

	// ------------------------------------------------------

	public function run(): void {
		if ( ! empty( $this->base_slug_taxonomy ) ) {
			add_filter( 'term_link', [ $this, 'term_link' ], 10, 3 );

			foreach ( $this->base_slug_taxonomy as $base_slug ) {
				add_action( 'created_' . $base_slug, [ $this, 'flush_rules' ] );
				add_action( 'delete_' . $base_slug, [ $this, 'flush_rules' ] );
				add_action( 'edited_' . $base_slug, [ $this, 'flush_rules' ] );
			}

			add_filter( 'query_vars', [ $this, 'query_vars' ] );
			add_filter( 'request', [ $this, 'request' ] );
		}
	}

	// ------------------------------------------------------

	/**
	 * @return mixed|void
	 */
	public function term_link( $link, $term, $taxonomy ) {
		global $wp_rewrite;

		$taxonomies = get_taxonomies(
			[
				'show_ui' => true,
				'public'  => true,
			],
			'objects'
		);

		foreach ( $taxonomies as $type => $custom_tax ) {
			if ( $type !== $taxonomy ) {
				continue;
			}

			if ( $custom_tax->public &&
			     $custom_tax->show_ui &&
			     in_array( $custom_tax->name, $this->base_slug_taxonomy, false )
			) {
				$category_base = trim( str_replace( '%' . $custom_tax->name . '%', '', $wp_rewrite->get_extra_permastruct( $custom_tax->name ) ), '/' );

				// woocommerce
				if ( $custom_tax->name === 'product_cat' && \Addons\Helper::checkPluginActive( 'woocommerce/woocommerce.php' ) ) {
					$permalink_structure = wc_get_permalink_structure();
					$category_base       = trim( $permalink_structure['category_rewrite_slug'], '/' );
				}

				// Remove initial slash.
				if ( str_starts_with( $category_base, '/' ) ) {
					$category_base = substr( $category_base, 1 );
				}

				$category_base .= '/';

				return preg_replace( '`' . preg_quote( $category_base, '`' ) . '`u', '', $link, 1 );
			}
		}

		return $link;
	}

	// ------------------------------------------------------

	public function query_vars( $query_vars ): mixed {
		$query_vars[] = 'addons_category_redirect';

		return $query_vars;
	}

	// ------------------------------------------------------

	/**
	 * @return mixed|void
	 */
	public function request( $query_vars ) {
		if ( isset( $query_vars['addons_category_redirect'] ) ) {
			$cat_link = trailingslashit( get_option( 'home' ) ) . user_trailingslashit( $query_vars['addons_category_redirect'], 'category' );
			\Addons\Helper::redirect( $cat_link );
			exit;
		}

		return $query_vars;
	}

	// ------------------------------------------------------

	public function flush_rules(): void {
		flush_rewrite_rules( false );
	}
}
