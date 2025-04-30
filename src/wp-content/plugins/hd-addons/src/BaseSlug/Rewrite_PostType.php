<?php

namespace Addons\BaseSlug;

\defined( 'ABSPATH' ) || exit;

class Rewrite_PostType {
	private mixed $base_slug_post_type;

	public function __construct() {
		$custom_base_slug_options  = \Addons\Helper::getOption( 'base_slug__options' );
		$this->base_slug_post_type = $custom_base_slug_options['base_slug_post_type'] ?? [];
	}

	// ------------------------------------------------------

	public function run(): void {
		if ( ! empty( $this->base_slug_post_type ) ) {
			add_filter( 'post_type_link', [ $this, 'post_type_link' ], 10, 2 ); // remove base slug from URLs

			add_action( 'wp', [ $this, 'redirect' ] ); // auto redirect old URLs to non-base versions
			add_action( 'request', [ $this, 'request' ], 11, 1 ); // Permalink Manager.
		}
	}

	// ------------------------------------------------------

	/**
	 * @return array|string|string[]|void
	 */
	public function post_type_link( $permalink, $post ) {
		global $wp_post_types;

		foreach ( $wp_post_types as $type => $custom_post ) {
			if ( $type !== $post->post_type || ! get_option( 'permalink_structure' ) ) {
				continue;
			}

			if ( ! $custom_post->_builtin &&
			     $custom_post->public &&
			     $custom_post->show_ui &&
			     in_array( $custom_post->name, $this->base_slug_post_type, false )
			) {
				// woocommerce
				if ( $post->post_type === 'product' && \Addons\Helper::checkPluginActive( 'woocommerce/woocommerce.php' ) ) {
					return str_replace( $this->_get_product_base(), '/', $permalink );
				}

				return str_replace( '/' . trim( $custom_post->rewrite['slug'], '/' ) . '/', '/', $permalink );
			}
		}

		return $permalink;
	}

	// ------------------------------------------------------

	public function redirect(): void {
		global $post;

		if ( is_object( $post ) &&
		     ! is_preview() &&
		     ! is_admin() &&
		     is_single() &&
		     in_array( $post->post_type, $this->base_slug_post_type, false )
		) {
			$new_url  = get_permalink();
			$real_url = \Addons\Helper::getCurrentUrl();

			if ( ! str_contains( $real_url, $new_url ) && substr_count( $new_url, '/' ) !== substr_count( $real_url, '/' ) ) {
				remove_filter( 'post_type_link', [ $this, 'post_type_link' ], 10 );
				$old_url = get_permalink();

				add_filter( 'post_type_link', [ $this, 'post_type_link' ], 10, 2 );
				$fixed_url = str_replace( $old_url, $new_url, $real_url );

				wp_safe_redirect( $fixed_url, 301 );
			}
		}
	}

	// ------------------------------------------------------

	/**
	 * @return array|mixed
	 */
	public function request( $request ): mixed {
		global $wp;
		$url_request = $wp->request;

		if ( empty( $url_request ) || is_admin() ) {
			return $request;
		}

		$replace   = [];
		$url_parts = explode( '/', $url_request );
		$slug      = end( $url_parts );

		if ( $slug === 'feed' || $slug === 'amp' ) {
			$replace[ $slug ] = $slug;
		}

		if ( str_starts_with( $slug, 'comment-page-' ) ) {
			$replace['cpage'] = substr( $slug, strlen( 'comment-page-' ) );
		}

		if ( str_starts_with( $slug, 'schema-preview' ) ) {
			$replace['schema-preview'] = '';
		}

		// test for posts and pages
		$post_data = get_page_by_path( $url_request, OBJECT, 'post' );
		if ( ! ( $post_data instanceof \WP_Post ) && ! is_object( get_page_by_path( $url_request ) ) ) {
			$post_data = get_page_by_path( $url_request, OBJECT, $this->base_slug_post_type );

			if ( is_object( $post_data ) ) {
				$post_name = $post_data->post_name;
				$ancestors = get_post_ancestors( $post_data->ID );

				foreach ( $ancestors as $ancestor ) {
					if ( ! empty( $ancestor ) ) {
						$post_name = get_post_field( 'post_name', $ancestor ) . '/' . $post_name;
					}
				}

				$replace['page']                  = '';
				$replace['name']                  = $post_name;
				$replace['post_type']             = $post_data->post_type;
				$replace[ $post_data->post_type ] = $post_name;

				return $replace;

			} else {

				/**
				 * @author KubiQ
				 */

				global $wp_rewrite;

				foreach ( $this->base_slug_post_type as $post_type ) {
					$query_var = get_post_type_object( $post_type )->query_var;
					foreach ( $wp_rewrite->rules as $pattern => $rewrite ) {
						// current rules
						if ( str_contains( $pattern, $query_var ) ) {
							if ( ! str_contains( $pattern, '(' . $query_var . ')' ) ) {
								preg_match_all( '#' . $pattern . '#', '/' . $query_var . '/' . $url_request, $matches, PREG_SET_ORDER );
							} else {
								preg_match_all( '#' . $pattern . '#', $query_var . '/' . $url_request, $matches, PREG_SET_ORDER );
							}

							if ( isset( $matches[0] ) && count( $matches ) !== 0 ) {
								// build URL query array
								$rewrite = str_replace( 'index.php?', '', $rewrite );
								parse_str( $rewrite, $url_query );
								foreach ( $url_query as $key => $value ) {
									$value = (int) str_replace( [ '$matches[', ']' ], '', $value );
									if ( isset( $matches[0][ $value ] ) ) {
										$value             = $matches[0][ $value ];
										$url_query[ $key ] = $value;
									}
								}

								// new path
								if ( isset( $url_query[ $query_var ] ) ) {
									$post_data = get_page_by_path( '/' . $url_query[ $query_var ], OBJECT, $this->base_slug_post_type );

									if ( is_object( $post_data ) ) {
										$replace['page']                  = '';
										$replace['name']                  = $url_request;
										$replace['post_type']             = $post_data->post_type;
										$replace[ $post_data->post_type ] = $url_request;

										// rewrites, pagination, etc.
										foreach ( $url_query as $key => $value ) {
											if ( $key !== 'post_type' && ! str_starts_with( $value, '$matches' ) ) {
												$replace[ $key ] = $value;
											}
										}

										return $replace;
									}
								}
							}
						}
					}
				}
			}
		}

		return $request;
	}

	// ------------------------------------------------------

	/**
	 * Get product base.
	 */
	private function _get_product_base(): string {
		$permalink_structure = wc_get_permalink_structure();
		$product_base        = $permalink_structure['product_rewrite_slug'];

		if ( str_contains( $product_base, '%product_cat%' ) ) {
			$product_base = str_replace( '%product_cat%', '', $product_base );
		}

		return '/' . trim( $product_base, '/' ) . '/';
	}
}
