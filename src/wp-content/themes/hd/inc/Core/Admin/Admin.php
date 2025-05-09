<?php

declare( strict_types=1 );

namespace HD\Core\Admin;

use HD\Utilities\Traits\Singleton;

\defined( 'ABSPATH' ) || die;

/**
 * Admin Class
 *
 * @author Gaudev
 */
final class Admin {
	use Singleton;

	// --------------------------------------------------

	private function init(): void {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );

		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_init', [ $this, 'admin_init' ], 11 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ], 30 );
		add_action( 'admin_footer', [ $this, 'admin_footer_script' ] );

		/** Show a clear cache message */
		add_action( 'admin_notices', static function () {
			if ( $message = get_transient( '_clear_cache_message' ) ) {
				\HD_Helper::messageSuccess( $message, false );

				if ( ! isset( $_GET['clear_cache'] ) ) {
					delete_transient( '_clear_cache_message' );
				}
			}
		} );
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function admin_footer_script(): void { ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let postTitleInput = document.querySelector('input[name="post_title"]');
                if (postTitleInput) {
                    postTitleInput.setAttribute('required', 'required');
                }

                // popup confirmation for trash action
                const links = document.querySelectorAll('a');
                links.forEach(function (link) {
                    const href = link.getAttribute('href');

                    if (href && href.includes('action=trash')) {
                        link.addEventListener('click', function (e) {
                            const confirmAction = confirm('Are you sure you want to move this post to the trash?');
                            if (!confirmAction) {
                                e.preventDefault();
                            }
                        });
                    }
                });
            });
        </script>
		<?php
	}

	// --------------------------------------------------

	/**
	 * Gutenberg editor
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets(): void {
        \HD_Asset::enqueueStyle( 'editor-style-css', ASSETS_URL . 'css/editor-style.css' );
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function admin_enqueue_scripts(): void {
		$version = \HD_Helper::version();

        \HD_Asset::enqueueStyle( 'admin-css', ASSETS_URL . 'css/admin.css', [], $version );
		\HD_Asset::enqueueScript( 'admin', ASSETS_URL . 'js/admin.js', [ 'jquery' ], $version, true, [ 'module', 'defer' ] );
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function admin_menu(): void {
		//global $menu, $submenu;
		// dump($menu);
		// dump($submenu);

		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );

		$admin_menu_settings = \HD_Helper::filterSettingOptions( 'admin_menu', [] );

		$admin_hide_menu             = $admin_menu_settings['admin_hide_menu'] ?? [];
		$admin_hide_submenu          = $admin_menu_settings['admin_hide_submenu'] ?? [];
		$admin_hide_menu_ignore_user = $admin_menu_settings['admin_hide_menu_ignore_user'] ?? [];

		$user_id = get_current_user_id();

		// admin menu
		if ( $admin_hide_menu && ! in_array( $user_id, $admin_hide_menu_ignore_user, false ) ) {
			foreach ( $admin_hide_menu as $menu_slug ) {
				if ( $menu_slug ) {
					remove_menu_page( $menu_slug );
				}
			}
		}

		// admin submenu
		if ( $admin_hide_submenu && ! in_array( $user_id, $admin_hide_menu_ignore_user, false ) ) {
			foreach ( $admin_hide_submenu as $menu_slug => $_submenu ) {
				foreach ( $_submenu as $_submenu_item ) {
					if ( $_submenu_item ) {
						remove_submenu_page( $menu_slug, $_submenu_item );
					}
				}
			}
		}

		// Other settings
		$remove_menu_setting = \HD_Helper::getThemeMod( 'remove_menu_setting' );
		if ( $remove_menu_setting ) {
			foreach ( explode( "\n", $remove_menu_setting ) as $menu_slug ) {
				if ( $menu_slug ) {
					remove_menu_page( $menu_slug );
				}
			}
		}
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function admin_init(): void {
		// editor-style for Classic Editor
		add_editor_style( ASSETS_URL . 'css/editor-style-css.css' );

		$admin_list_table = \HD_Helper::filterSettingOptions( 'admin_list_table', [] );

		$term_row_actions                = $admin_list_table['term_row_actions'] ?? [];
		$post_row_actions                = $admin_list_table['post_row_actions'] ?? [];
		$term_thumb_columns              = $admin_list_table['term_thumb_columns'] ?? [];
		$post_type_exclude_thumb_columns = $admin_list_table['post_type_exclude_thumb_columns'] ?? [];

		// https://wordpress.stackexchange.com/questions/77532/how-to-add-the-category-id-to-admin-page
		if ( $term_row_actions ) {
			foreach ( $term_row_actions as $term ) {
				add_filter( "{$term}_row_actions", [ $this, '_term_row_actions' ], 11, 2 );
			}
		}

		// customize row_actions
		if ( $post_row_actions ) {
			foreach ( $post_row_actions as $post_type ) {
				add_filter( "{$post_type}_row_actions", [ $this, '_post_type_row_actions' ], 11, 2 );
			}
		}

		// exclude post columns
		if ( $post_type_exclude_thumb_columns ) {
			foreach ( $post_type_exclude_thumb_columns as $post ) {
				add_filter( "manage_{$post}_posts_columns", [ $this, '_manage_columns_exclude_header' ], 12, 1 );
			}
		}

		// thumb terms
		if ( $term_thumb_columns ) {
			foreach ( $term_thumb_columns as $term ) {
				add_filter( "manage_edit-{$term}_columns", [ $this, '_manage_term_columns_header' ], 11, 1 );
				add_filter( "manage_{$term}_custom_column", [ $this, '_manage_term_columns_content' ], 11, 3 );
			}
		}

		// customize post, page
		add_filter( 'manage_posts_columns', [ $this, '_manage_columns_header' ], 11, 1 );
		add_filter( 'manage_posts_custom_column', [ $this, '_manage_columns_content' ], 11, 2 );
		add_filter( 'manage_pages_columns', [ $this, '_manage_columns_header' ], 5, 1 );
		add_filter( 'manage_pages_custom_column', [ $this, '_manage_columns_content' ], 5, 2 );
	}

	// --------------------------------------------------

	/**
	 * @param $actions
	 * @param $_object
	 *
	 * @return array
	 */
	public function _term_row_actions( $actions, $_object ): array {
		return \HD_Helper::prepend( $actions, 'Id: ' . $_object->term_id, 'action_id' );
	}

	// --------------------------------------------------

	/**
	 * @param $actions
	 * @param $_object
	 *
	 * @return mixed
	 */
	public function _post_type_row_actions( $actions, $_object ): mixed {
		if ( ! in_array( $_object->post_type, [ 'product', 'site-review' ] ) ) {
			$actions = \HD_Helper::prepend( $actions, 'Id:' . $_object->ID, 'action_id' );
		}

		return $actions;
	}

	// --------------------------------------------------

	/**
	 * @param $columns
	 *
	 * @return array
	 */
	public function _manage_columns_header( $columns ): array {
		$in = [
			'post_thumb' => sprintf( '<span class="wc-image tips">%1$s</span>', __( 'Thumb', TEXT_DOMAIN ) ),
		];

		return \HD_Helper::insertBefore( 'title', $columns, $in );
	}

	// --------------------------------------------------

	/**
	 * @param $column_name
	 * @param $post_id
	 */
	public function _manage_columns_content( $column_name, $post_id ): void {
		switch ( $column_name ) {
			case 'post_thumb':
				$post_type = get_post_type( $post_id );
				$thumbnail = \HD_Helper::postImageHTML( $post_id, 'thumbnail' );

				if ( ! in_array( $post_type, [ 'video', 'product' ] ) ) {
					if ( ! $thumbnail ) {
						$thumbnail = \HD_Helper::placeholderSrc();
					}
					echo $thumbnail;
				} elseif ( 'video' === $post_type ) {
					if ( $thumbnail ) {
						echo $thumbnail;
					} elseif ( $url = \HD_Helper::getField( 'url', $post_id ) ) {
						$img_src = \HD_Helper::youtubeImage( esc_url( $url ), 3 );
						if ( $img_src ) {
							echo '<img loading="lazy" alt="video" src="' . $img_src . '" />';
						}
					}
				}

				break;

			default:
				break;
		}
	}

	// --------------------------------------------------

	/**
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function _manage_columns_exclude_header( $columns ): mixed {
		unset( $columns['post_thumb'] );

		return $columns;
	}

	// --------------------------------------------------

	/**
	 * @param $columns
	 *
	 * @return array|mixed
	 */
	public function _manage_term_columns_header( $columns ): mixed {
		if ( \HD_Helper::isAcfActive() ) {
			$thumb   = [
				'term_thumb' => sprintf( '<span class="wc-image tips">%1$s</span>', __( 'Thumb', TEXT_DOMAIN ) ),
			];
			$columns = \HD_Helper::insertBefore( 'name', $columns, $thumb );
		}

		return $columns;
	}

	// --------------------------------------------------

	/**
	 * @param $out
	 * @param $column
	 * @param $term_id
	 *
	 * @return int|mixed|string|null
	 */
	public function _manage_term_columns_content( $out, $column, $term_id ): mixed {
		switch ( $column ) {
			case 'term_thumb':
				$term_thumb = \HD_Helper::acfTermThumb( $term_id, $column, 'thumbnail', true );
				if ( ! $term_thumb ) {
					$term_thumb = \HD_Helper::placeholderSrc();
				}

				$out = $term_thumb;

				return $out;

			default:
				return $out;
		}
	}
}
