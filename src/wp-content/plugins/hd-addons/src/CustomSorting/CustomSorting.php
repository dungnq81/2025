<?php

namespace Addons\CustomSorting;

use Addons\Helper;

\defined( 'ABSPATH' ) || exit;

/**
 * Order Items (Posts, Pages, and Custom Post Types) using a Drag-and-Drop Sortable JavaScript.
 *
 * @author Colorlib
 * Modified by Gaudev
 */
final class CustomSorting {

	private mixed $order_post_type;
	private mixed $order_taxonomy;

	// ------------------------------------------------------

	public function __construct() {
		if ( ! Helper::getThemeMod( '_custom_sorting_' ) ) {
			global $wpdb;

			if ( ! $wpdb->query( "DESCRIBE {$wpdb->terms} `term_order`" ) ) {
				$query = "ALTER TABLE {$wpdb->terms} ADD `term_order` INT( 4 ) NULL DEFAULT '0'";
				$wpdb->query( $query );
			}

			Helper::setThemeMod( '_custom_sorting_', 1 );
		}

		// Check custom order
		$custom_sorting_options = Helper::getOption( 'custom_sorting__options', [] );

		$this->order_post_type = $custom_sorting_options['order_post_type'] ?? [];
		$this->order_taxonomy  = $custom_sorting_options['order_taxonomy'] ?? [];

		if ( ! empty( $this->order_post_type ) || ! empty( $this->order_taxonomy ) ) {
			$this->_init_run();
		}
	}

	// ------------------------------------------------------

	/**
	 * @return void
	 */
	private function _init_run(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ], 33 );
		add_action( 'admin_init', [ $this, 'refresh' ] );

		// posts
		add_action( 'pre_get_posts', [ $this, 'custom_order_pre_get_posts' ] );

		// dynamic hook get_(adjacent)_post_sort
		add_filter( 'get_previous_post_sort', [ $this, 'custom_order_previous_post_sort' ] );
		add_filter( 'get_next_post_sort', [ $this, 'custom_order_next_post_sort' ] );

		// dynamic hook get_(adjacent)_post_where
		add_filter( 'get_previous_post_where', [ $this, 'custom_order_previous_post_where' ] );
		add_filter( 'get_next_post_where', [ $this, 'custom_order_next_post_where' ] );

		// terms
		add_filter( 'get_terms_orderby', [ $this, 'custom_order_get_terms_orderby' ], 10, 2 );
		add_filter( 'wp_get_object_terms', [ $this, 'custom_order_get_object_terms' ] );
		add_filter( 'get_terms', [ $this, 'custom_order_get_object_terms' ] );

		// ajax
		add_action( 'wp_ajax_update-menu-order', [ $this, 'update_menu_order_ajax' ] );
		add_action( 'wp_ajax_update-menu-order-tags', [ $this, 'update_menu_order_tags_ajax' ] );
	}

	// ------------------------------------------------------

	/**
	 * @param $hook_suffix
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook_suffix ): void {
		if ( $this->_check_custom_sorting_script() ) {
			wp_enqueue_script( 'sorting-js', ADDONS_URL . 'assets/js/sorting.js', [
				'jquery-core',
				'jquery-ui-sortable'
			], \Addons\Helper::version(), true );

			wp_script_add_data( 'sorting-js', 'addon', [ 'module', 'defer' ] );
		}
	}

	// ------------------------------------------------------

	/**
	 * @return false|void
	 */
	public function refresh() {
		if ( wp_doing_ajax() ) {
			return false;
		}

		global $wpdb;

		$objects = $this->order_post_type;
		$tags    = $this->order_taxonomy;

		if ( ! empty( $objects ) ) {
			foreach ( $objects as $object ) {
				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT COUNT(*) AS `cnt`, MAX(menu_order) AS `max`, MIN(menu_order) AS `min`
						FROM $wpdb->posts
						WHERE `post_type` = %s AND `post_status` IN ('publish', 'pending', 'draft', 'private', 'future')",
						$object
					)
				);

				if ( (int) $result[0]->cnt === 0 || ( $result[0]->cnt === $result[0]->max && (int) $result[0]->min === 1 ) ) {
					continue;
				}

				$wpdb->query( 'SET @row_number = 0;' );
				$wpdb->query(
					"UPDATE {$wpdb->posts} as `pt` JOIN (
						SELECT `ID`, (@row_number:=@row_number + 1) AS `rank`
                        FROM {$wpdb->posts}
                        WHERE `post_type` = '{$object}' AND `post_status` IN ( 'publish', 'pending', 'draft', 'private', 'future' )
                        ORDER BY `menu_order` ASC
                    ) as `pt2`
                	ON pt.`id` = pt2.`id`
                	SET pt.`menu_order` = pt2.`rank`;"
				);
			}
		}

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $taxonomy ) {
				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT COUNT(*) AS `cnt`, MAX(`term_order`) AS `max`, MIN(`term_order`) AS `min`
						FROM {$wpdb->terms} AS `terms`
						INNER JOIN {$wpdb->term_taxonomy} AS `term_taxonomy` ON ( terms.`term_id` = term_taxonomy.`term_id` )
						WHERE term_taxonomy.`taxonomy` = %s",
						$taxonomy
					)
				);

				if ( (int) $result[0]->cnt === 0 || ( $result[0]->cnt === $result[0]->max && (int) $result[0]->min === 1 ) ) {
					continue;
				}

				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT terms.`term_id`
						FROM {$wpdb->terms} AS `terms`
						INNER JOIN {$wpdb->term_taxonomy} AS `term_taxonomy` ON ( terms.`term_id` = term_taxonomy.`term_id` )
						WHERE term_taxonomy.`taxonomy` = %s
						ORDER BY `term_order` ASC",
						$taxonomy
					)
				);

				foreach ( $results as $key => $result ) {
					$wpdb->update(
						$wpdb->terms,
						[ 'term_order' => $key + 1 ],
						[ 'term_id' => $result->term_id ]
					);
				}
			}
		}
	}

	// ------------------------------------------------------

	/**
	 * @return false|void
	 */
	public function update_menu_order_ajax() {
		global $wpdb;

		if ( ! wp_doing_ajax() ) {
			return false;
		}

		parse_str( $_POST['order'], $data );

		if ( ! is_array( $data ) ) {
			return false;
		}

		$id_arr = [];
		foreach ( $data as $key => $values ) {
			$id_arr = array_values( $values );
		}

		$menu_order_arr = [];
		foreach ( $id_arr as $id ) {
			$id      = (int) $id;
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT `menu_order` FROM {$wpdb->posts} WHERE `ID` = %d LIMIT 1", $id ) );

			$menu_order_arr[] = $results[0]->menu_order;
		}

		sort( $menu_order_arr );

		foreach ( $id_arr as $position => $id ) {
			$id = (int) $id;
			$wpdb->update(
				$wpdb->posts,
				[ 'menu_order' => $menu_order_arr[ $position ] ],
				[ 'ID' => $id ],
				[ '%d' ],
				[ '%d' ]
			);
		}

		do_action( 'addon_update_menu_order_post_type' );
		exit();
	}

	// ------------------------------------------------------

	public function update_menu_order_tags_ajax() {
		global $wpdb;

		if ( ! wp_doing_ajax() ) {
			return false;
		}

		parse_str( $_POST['order'], $data );

		if ( ! is_array( $data ) ) {
			return false;
		}

		$id_arr = [];
		foreach ( $data as $key => $values ) {
			$id_arr = array_values( $values );
		}

		$menu_order_arr = [];
		foreach ( $id_arr as $id ) {
			$id      = (int) $id;
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT `term_order` FROM {$wpdb->terms} WHERE `term_id` = %d LIMIT 1", $id ) );

			$menu_order_arr[] = $results[0]->term_order;
		}

		sort( $menu_order_arr );

		foreach ( $id_arr as $position => $id ) {
			$id = (int) $id;
			$wpdb->update(
				$wpdb->terms,
				[ 'term_order' => $menu_order_arr[ $position ] ],
				[ 'term_id' => $id ],
				[ '%d' ],
				[ '%d' ]
			);
		}

		do_action( 'addon_update_menu_order_taxonomy' );
		exit();
	}

	// ------------------------------------------------------

	/**
	 * @return array|mixed|string|string[]|null
	 */
	public function custom_order_previous_post_where( $where ): mixed {
		global $post;

		$objects = $this->order_post_type;
		if ( empty( $objects ) ) {
			return $where;
		}

		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects, false ) ) {
			$where = preg_replace( "/p.post_date < \'[0-9\-\s\:]+\'/i", "p.menu_order > '" . $post->menu_order . "'", $where );
		}

		return $where;
	}

	// ------------------------------------------------------

	/**
	 * @return array|mixed|string|string[]|null
	 */
	public function custom_order_next_post_where( $where ): mixed {
		global $post;

		$objects = $this->order_post_type;
		if ( empty( $objects ) ) {
			return $where;
		}

		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects, false ) ) {
			$where = preg_replace( "/p.post_date > \'[0-9\-\s\:]+\'/i", "p.menu_order < '" . $post->menu_order . "'", $where );
		}

		return $where;
	}

	// ------------------------------------------------------

	/**
	 * @return mixed|string
	 */
	public function custom_order_previous_post_sort( $orderby ): mixed {
		global $post;

		$objects = $this->order_post_type;
		if ( empty( $objects ) ) {
			return $orderby;
		}

		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects, false ) ) {
			$orderby = 'ORDER BY p.menu_order ASC LIMIT 1';
		}

		return $orderby;
	}

	// ------------------------------------------------------

	/**
	 * @return mixed|string
	 */
	public function custom_order_next_post_sort( $orderby ): mixed {
		global $post;

		$objects = $this->order_post_type;
		if ( empty( $objects ) ) {
			return $orderby;
		}

		if ( isset( $post->post_type ) && in_array( $post->post_type, $objects, false ) ) {
			$orderby = 'ORDER BY p.menu_order DESC LIMIT 1';
		}

		return $orderby;
	}

	// ------------------------------------------------------

	/**
	 * @return false|void
	 */
	public function custom_order_pre_get_posts( $wp_query ) {
		$objects = $this->order_post_type;

		if ( empty( $objects ) ) {
			return false;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			if ( isset( $wp_query->query['post_type'] ) && ! isset( $_GET['orderby'] ) && in_array( $wp_query->query['post_type'], $objects, false ) ) {
				if ( ! $wp_query->get( 'orderby' ) ) {
					$wp_query->set( 'orderby', 'menu_order' );
				}

				if ( ! $wp_query->get( 'order' ) ) {
					$wp_query->set( 'order', 'ASC' );
				}
			}
		} else {
			$active = false;

			if ( isset( $wp_query->query['post_type'] ) ) {
				if ( ! is_array( $wp_query->query['post_type'] ) && in_array( $wp_query->query['post_type'], $objects, false ) ) {
					$active = true;
				}
			} elseif ( in_array( 'post', $objects, false ) ) {
				$active = true;
			}

			if ( ! $active ) {
				return false;
			}

			if ( isset( $wp_query->query['suppress_filters'] ) ) {
				if ( $wp_query->get( 'orderby' ) === 'date' ) {
					$wp_query->set( 'orderby', 'menu_order' );
				}
				if ( $wp_query->get( 'order' ) === 'DESC' ) {
					$wp_query->set( 'order', 'ASC' );
				}
			} else {
				if ( ! $wp_query->get( 'orderby' ) ) {
					$wp_query->set( 'orderby', 'menu_order' );
				}
				if ( ! $wp_query->get( 'order' ) ) {
					$wp_query->set( 'order', 'ASC' );
				}
			}
		}
	}

	// ------------------------------------------------------

	/**
	 * @return mixed|string
	 */
	public function custom_order_get_terms_orderby( $orderby, $args ): mixed {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $orderby;
		}

		if ( ! isset( $args['taxonomy'] ) ) {
			return $orderby;
		}

		$tags = $this->order_taxonomy;

		if ( is_array( $args['taxonomy'] ) ) {
			$taxonomy = $args['taxonomy'][0] ?? false;
		} else {
			$taxonomy = $args['taxonomy'];
		}

		if ( ! in_array( $taxonomy, $tags, false ) ) {
			return $orderby;
		}

		return 't.term_order';
	}

	// ------------------------------------------------------

	/**
	 * @return mixed|void
	 */
	public function custom_order_get_object_terms( $terms ) {
		if ( isset( $_GET['orderby'] ) && is_admin() && ! wp_doing_ajax() ) {
			return $terms;
		}

		$tags = $this->order_taxonomy;

		foreach ( $terms as $term ) {
			if ( is_object( $term ) && isset( $term->taxonomy ) ) {
				$taxonomy = $term->taxonomy;
				if ( ! in_array( $taxonomy, $tags, false ) ) {
					return $terms;
				}
			} else {
				return $terms;
			}
		}

		if ( is_array( $terms ) ) {
			usort( $terms, [ $this, 'taxonomy_cmp' ] );
		}

		return $terms;
	}

	// ------------------------------------------------------

	public function taxonomy_cmp( $a, $b ): int {
		if ( $a->term_order === $b->term_order ) {
			return 0;
		}

		return ( $a->term_order < $b->term_order ) ? - 1 : 1;
	}

	// ------------------------------------------------------

	private function _check_custom_sorting_script(): bool {
		$active = false;

		if ( empty( $this->order_post_type ) && empty( $this->order_taxonomy ) ) {
			return false;
		}

		if ( isset( $_GET['orderby'] ) || str_contains( $_SERVER['REQUEST_URI'], 'action=edit' ) || str_contains( $_SERVER['REQUEST_URI'], 'wp-admin/post-new.php' ) ) {
			return false;
		}

		if ( ! empty( $this->order_post_type ) ) {
			if ( isset( $_GET['post_type'] ) && ! isset( $_GET['taxonomy'] ) && in_array( $_GET['post_type'], $this->order_post_type, false ) ) {
				$active = true;
			}
			if ( ! isset( $_GET['post_type'] ) && str_contains( $_SERVER['REQUEST_URI'], 'wp-admin/edit.php' ) && in_array( 'post', $this->order_post_type, false ) ) {
				$active = true;
			}
		}

		if ( ! empty( $this->order_taxonomy ) ) {
			if ( isset( $_GET['taxonomy'] ) && in_array( $_GET['taxonomy'], $this->order_taxonomy, false ) ) {
				$active = true;
			}
		}

		return $active;
	}

	// ------------------------------------------------------

	public function update_options(): void {
		global $wpdb;

		$objects = $this->order_post_type;
		$tags    = $this->order_taxonomy;

		if ( ! empty( $objects ) ) {
			foreach ( $objects as $object ) {
				$object = sanitize_text_field( $object );

				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT count(*) as `cnt`, max(`menu_order`) as `max`, min(`menu_order`) as `min`
						FROM {$wpdb->posts}
						WHERE `post_type` = %s AND `post_status` IN ('publish', 'pending', 'draft', 'private', 'future')",
						$object
					)
				);

				if ( $result[0]->cnt === 0 || $result[0]->cnt === $result[0]->max ) {
					continue;
				}

				if ( $object === 'page' ) {
					$results = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT `ID`
							FROM {$wpdb->posts}
							WHERE `post_type` = %s AND `post_status` IN ('publish', 'pending', 'draft', 'private', 'future')
							ORDER BY `post_title` ASC",
							$object
						)
					);
				} else {
					$results = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT `ID`
							FROM {$wpdb->posts}
							WHERE `post_type` = %s AND `post_status` IN ('publish', 'pending', 'draft', 'private', 'future')
							ORDER BY `post_date` DESC",
							$object
						)
					);
				}

				foreach ( $results as $key => $result ) {
					$wpdb->update(
						$wpdb->posts,
						[ 'menu_order' => $key + 1 ],
						[ 'ID' => $result->ID ]
					);
				}
			}
		}

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $taxonomy ) {
				$taxonomy = sanitize_text_field( $taxonomy );

				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT count(*) as cnt, max(`term_order`) as `max`, min(`term_order`) as `min` FROM {$wpdb->terms} AS `terms`
						INNER JOIN {$wpdb->term_taxonomy} AS `term_taxonomy` ON ( terms.`term_id` = term_taxonomy.`term_id` )
						WHERE term_taxonomy.`taxonomy` = %s",
						$taxonomy
					)
				);

				if ( $result[0]->cnt === 0 || $result[0]->cnt === $result[0]->max ) {
					continue;
				}

				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT terms.`term_id`
						FROM {$wpdb->terms} AS `terms`
						INNER JOIN {$wpdb->term_taxonomy} AS `term_taxonomy` ON ( terms.`term_id` = term_taxonomy.`term_id` )
						WHERE term_taxonomy.`taxonomy` = %s
						ORDER BY `name` ASC",
						$taxonomy
					)
				);

				foreach ( $results as $key => $result ) {
					$wpdb->update(
						$wpdb->terms,
						[ 'term_order' => $key + 1 ],
						[ 'term_id' => $result->term_id ]
					);
				}
			}
		}
	}

	// ------------------------------------------------------

	public function reset_all(): void {
		global $wpdb;

		// posts
		if ( ! empty( $this->order_post_type ) ) {
			$in_list     = implode( ',', array_map( static fn( $value ) => $wpdb->prepare( '%s', $value ), $this->order_post_type ) );
			$status_cond = sprintf( '`post_type` IN (%s)', $in_list );

			$wpdb->query( "UPDATE {$wpdb->posts} SET `menu_order` = 0 WHERE {$status_cond}" );
		}

		// taxonomy
		if ( ! empty( $this->order_taxonomy ) ) {
			$wpdb->query( "UPDATE {$wpdb->terms} SET `term_order` = 0" );
		}

		// reset
		$custom_order_options = [
			'order_post_type' => [],
			'order_taxonomy'  => [],
		];

		Helper::updateOption( 'custom_sorting__options', $custom_order_options );
		Helper::setThemeMod( '_custom_sorting_', 0 );
	}
}
