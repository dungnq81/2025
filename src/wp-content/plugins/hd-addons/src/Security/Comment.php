<?php

namespace Addons\Security;

\defined( 'ABSPATH' ) || exit;

final class Comment {
	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function disable(): void {
		add_action( 'admin_init', [ $this, 'disable_comments_post_types_support' ] );
		add_action( 'admin_init', [ $this, 'disable_comments_admin_menu_redirect' ] );
		add_filter( 'comments_open', '__return_false', 20, 2 );
		add_filter( 'pings_open', '__return_false', 20, 2 );
		add_action( 'admin_menu', [ $this, 'disable_comments_admin_menu' ] );
		add_action( 'wp_dashboard_setup', [ $this, 'disable_comments_dashboard' ] );
		add_action( 'wp_before_admin_bar_render', [ $this, 'remove_comments_admin_bar' ], 60 );
		add_filter( 'comments_template', '__return_empty_string' );
		add_action( 'pre_comment_on_post', [ $this, 'block_comments_submission' ] );
		add_filter( 'rest_endpoints', [ $this, 'disable_comments_rest_api' ] );
	}

	// --------------------------------------------------

	public function disable_comments_post_types_support(): void {
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	// --------------------------------------------------

	public function disable_comments_admin_menu_redirect(): void {
		global $pagenow;

		if ( $pagenow === 'edit-comments.php' ) {
			wp_redirect( admin_url() );
			exit;
		}
	}

	// --------------------------------------------------

	public function disable_comments_admin_menu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	// --------------------------------------------------

	public function disable_comments_dashboard(): void {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	// --------------------------------------------------

	public function remove_comments_admin_bar(): void {
		global $wp_admin_bar;
		if ( is_admin_bar_showing() ) {
			$wp_admin_bar->remove_menu( 'comments' );
		}
	}

	// --------------------------------------------------

	public function block_comments_submission(): void {
		wp_die( 'Comments are closed.' );
	}

	// --------------------------------------------------

	/**
	 * @param $endpoints
	 *
	 * @return array
	 */
	public function disable_comments_rest_api( $endpoints ): array {
		if ( isset( $endpoints['/wp/v2/comments'] ) ) {
			unset( $endpoints['/wp/v2/comments'] );
		}

		return $endpoints;
	}
}
