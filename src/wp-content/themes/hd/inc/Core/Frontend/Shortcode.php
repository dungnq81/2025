<?php

declare( strict_types=1 );

namespace HD\Core\Frontend;

use HD\Utilities\Traits\Singleton;

\defined( 'ABSPATH' ) || die;

/**
 * Shortcode Class
 *
 * @author Gaudev
 */
final class Shortcode {
	use Singleton;

	// --------------------------------------------------

	private function init(): void {
		$shortcodes = [
			'safe_mail'         => [ $this, 'safe_mail' ],
			'site_logo'         => [ $this, 'site_logo' ],
			'menu_logo'         => [ $this, 'menu_logo' ],
			'inline_search'     => [ $this, 'inline_search' ],
			'dropdown_search'   => [ $this, 'dropdown_search' ],
			'off_canvas_button' => [ $this, 'off_canvas_button' ],
			'horizontal_menu'   => [ $this, 'horizontal_menu' ],
			'vertical_menu'     => [ $this, 'vertical_menu' ],
			'posts'             => [ $this, 'posts' ],
		];

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function safe_mail( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'title' => '',
				'email' => '',
				'class' => '',
			],
			$atts,
			'safe_mail'
		);

		$attributes['title'] = $atts['title'] ? \HD_Helper::escAttr( $atts['title'] ) : \HD_Helper::escAttr( $atts['email'] );

		if ( $atts['class'] ) {
			$attributes['class'] = \HD_Helper::escAttr( $atts['class'] );
		}

		return \HD_Helper::safeMailTo( $atts['email'], $atts['title'], $attributes );
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return false|string|null
	 */
	public function posts( array $atts = [] ): false|string|null {
		$default_atts = [
			'post_type'        => 'post',
			'term_ids'         => '',
			'taxonomy'         => 'category',
			'include_children' => false,
			'posts_per_page'   => 12,

			'limit_time'    => '',
			'wrapper_tag'   => '',
			'wrapper_class' => '',

			'show' => [
				'title_tag'      => 'p',
				'thumbnail'      => true,
				'thumbnail_size' => 'medium',
				'scale'          => false,
				'time'           => true,
				'term'           => true,
				'desc'           => true,
				'view_more'      => true,
			],
		];

		$atts = shortcode_atts(
			$default_atts,
			$atts,
			'posts'
		);

		$term_ids         = $atts['term_ids'] ?: [];
		$posts_per_page   = $atts['posts_per_page'] ? absint( $atts['posts_per_page'] ) : \HD_Helper::getOption( 'posts_per_page' );
		$include_children = \HD_Helper::toBool( $atts['include_children'] );
		$orderby          = [ 'date' => 'DESC' ];
		$strtotime_str    = $atts['limit_time'] ? \HD_Helper::toString( $atts['limit_time'] ) : false;

		$r = \HD_Helper::queryByTerms(
			$term_ids,
			$atts['post_type'],
			$atts['taxonomy'],
			$include_children,
			$posts_per_page,
			$orderby,
			[],
			$strtotime_str
		);

		if ( ! $r ) {
			return null;
		}

		$wrapper_open  = $atts['wrapper'] ? '<' . $atts['wrapper'] . ' class="' . $atts['wrapper_class'] . '">' : '';
		$wrapper_close = $atts['wrapper'] ? '</' . $atts['wrapper'] . '>' : '';

		ob_start();
		$i = 0;

		// Load slides loop.
		while ( $r->have_posts() && $i < $posts_per_page ) :
			$r->the_post();

			echo $wrapper_open;
			get_template_part( 'template-parts/post/loop', null, $atts['show'] );
			echo $wrapper_close;

			++ $i;
		endwhile;
		wp_reset_postdata();

		return ob_get_clean();
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function vertical_menu( array $atts = [] ): string {
		static $vertical_menu_counter = 0;
		$id = 'menu-' . substr( md5( __METHOD__ . ++ $vertical_menu_counter ), 0, 10 );

		$atts = shortcode_atts(
			[
				'location'         => 'mobile-nav',
				'class'            => 'menu vertical vertical-menu mobile-menu',
				'extra_class'      => 'mobile-nav',
				'id'               => \HD_Helper::escAttr( $id ),
				'depth'            => 4,
				'li_class'         => '',
				'li_depth_class'   => '',
				'link_class'       => '',
				'link_depth_class' => '',
			],
			$atts,
			'vertical_menu'
		);

		$location    = $atts['location'] ? \HD_Helper::escAttr( $atts['location'] ) : 'mobile-nav';
		$class       = $atts['class'] ? \HD_Helper::escAttr( $atts['class'] ) : '';
		$extra_class = $atts['extra_class'] ? \HD_Helper::escAttr( $atts['extra_class'] ) : '';
		$depth       = $atts['depth'] ? absint( $atts['depth'] ) : 1;
		$id          = $atts['id'] ?: \HD_Helper::escAttr( $id );

		$li_class         = ! empty( $atts['li_class'] ) ? \HD_Helper::escAttr( $atts['li_class'] ) : '';
		$li_depth_class   = ! empty( $atts['li_depth_class'] ) ? \HD_Helper::escAttr( $atts['li_depth_class'] ) : '';
		$link_class       = ! empty( $atts['link_class'] ) ? \HD_Helper::escAttr( $atts['link_class'] ) : '';
		$link_depth_class = ! empty( $atts['link_depth_class'] ) ? \HD_Helper::escAttr( $atts['link_depth_class'] ) : '';

		return \HD_Helper::verticalNav( [
			'menu_id'          => $id,
			'menu_class'       => ! empty( $extra_class ) ? $class . ' ' . $extra_class : $class,
			'theme_location'   => $location,
			'depth'            => $depth,
			'li_class'         => $li_class,
			'li_depth_class'   => $li_depth_class,
			'link_class'       => $link_class,
			'link_depth_class' => $link_depth_class,
			'echo'             => false,
		] );
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function horizontal_menu( array $atts = [] ): string {
		static $horizontal_menu_counter = 0;
		$id = 'menu-' . substr( md5( __METHOD__ . ++ $horizontal_menu_counter ), 0, 10 );

		$atts = shortcode_atts(
			[
				'location'         => 'main-nav',
				'class'            => 'dropdown menu horizontal horizontal-menu desktop-menu',
				'extra_class'      => 'main-nav',
				'id'               => \HD_Helper::escAttr( $id ),
				'depth'            => 4,
				'li_class'         => '',
				'li_depth_class'   => '',
				'link_class'       => '',
				'link_depth_class' => '',
			],
			$atts,
			'horizontal_menu'
		);

		$location    = $atts['location'] ? \HD_Helper::escAttr( $atts['location'] ) : 'main-nav';
		$class       = $atts['class'] ? \HD_Helper::escAttr( $atts['class'] ) : '';
		$extra_class = $atts['extra_class'] ? \HD_Helper::escAttr( $atts['extra_class'] ) : '';
		$depth       = $atts['depth'] ? absint( $atts['depth'] ) : 1;
		$id          = $atts['id'] ?: \HD_Helper::escAttr( $id );

		$li_class         = ! empty( $atts['li_class'] ) ? \HD_Helper::escAttr( $atts['li_class'] ) : '';
		$li_depth_class   = ! empty( $atts['li_depth_class'] ) ? \HD_Helper::escAttr( $atts['li_depth_class'] ) : '';
		$link_class       = ! empty( $atts['link_class'] ) ? \HD_Helper::escAttr( $atts['link_class'] ) : '';
		$link_depth_class = ! empty( $atts['link_depth_class'] ) ? \HD_Helper::escAttr( $atts['link_depth_class'] ) : '';

		return \HD_Helper::horizontalNav( [
			'menu_id'          => $id,
			'menu_class'       => ! empty( $extra_class ) ? $class . ' ' . $extra_class : $class,
			'theme_location'   => $location,
			'depth'            => $depth,
			'li_class'         => $li_class,
			'li_depth_class'   => $li_depth_class,
			'link_class'       => $link_class,
			'link_depth_class' => $link_depth_class,
			'echo'             => false,
		] );
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function off_canvas_button( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'title'           => '',
				'hide_if_desktop' => 1,
				'class'           => '',
			],
			$atts,
			'offcanvas_button'
		);

		$title = $atts['title'] ?: __( 'Menu', TEXT_DOMAIN );
		$class = ! empty( $atts['hide_if_desktop'] ) ? ' !lg:hidden' : '';
		$class .= $atts['class'] ? ' ' . \HD_Helper::escAttr( $atts['class'] ) . $class : '';

		ob_start();

		?>
        <button class="menu-lines" type="button" data-open="offCanvasMenu" aria-label="button">
            <span class="menu-txt"><?= $title ?></span>
            <span class="line">
				<span class="line-1"></span>
				<span class="line-2"></span>
				<span class="line-3"></span>
			</span>
        </button>
		<?php

		return '<div class="off-canvas-content' . $class . '" data-off-canvas-content>' . ob_get_clean() . '</div>';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function menu_logo( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'heading' => false,
				'title'   => false,
				'class'   => 'logo',
			],
			$atts,
			'menu_logo'
		);

		return \HD_Helper::siteTitleOrLogo( false, $atts['heading'], $atts['title'], $atts['class'] );
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function site_logo( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'theme' => 'default',
				'class' => '',
			],
			$atts,
			'site_logo'
		);

		return \HD_Helper::siteLogo( $atts['theme'], $atts['class'] );
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function inline_search( array $atts = [] ): string {
		static $inline_search_counter = 0;
		$id = 'search-' . substr( md5( __METHOD__ . ++ $inline_search_counter ), 0, 10 );

		$atts = shortcode_atts(
			[
				'title'       => '',
				'placeholder' => '',
				'class'       => '',
				'id'          => \HD_Helper::escAttr( $id ),
			],
			$atts,
			'inline_search'
		);

		$title             = $atts['title'] ?: '';
		$title_for         = __( 'Search', TEXT_DOMAIN );
		$placeholder_title = $atts['placeholder'] ?: __( 'Search...', TEXT_DOMAIN );
		$id                = $atts['id'] ? \HD_Helper::escAttr( $atts['id'] ) : \HD_Helper::escAttr( $id );
		$class             = $atts['class'] ? ' ' . \HD_Helper::escAttr( $atts['class'] ) : '';

		ob_start();

		?>
        <form action="<?= \HD_Helper::home() ?>" class="frm-search" method="get" accept-charset="UTF-8" data-abide novalidate>
            <label for="<?= $id ?>" class="screen-reader-text"><?= $title_for ?></label>
            <input id="<?= $id ?>" required pattern="^(.*\S+.*)$" type="search" autocomplete="off" name="s" value="<?= get_search_query() ?>" placeholder="<?= $placeholder_title; ?>">
            <button type="submit" data-fa="" aria-label="Search"><?= $title ? '<span>' . $title . '</span>' : '' ?></button>
	        <?php echo \HD_Helper::isWoocommerceActive() ? '<input type="hidden" name="post_type" value="product">' : ''; ?>
        </form>
		<?php

		return '<div class="inline-search' . $class . '">' . ob_get_clean() . '</div>';
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function dropdown_search( array $atts = [] ): string {
		static $dropdown_search_counter = 0;
		$id = 'search-' . substr( md5( __METHOD__ . ++ $dropdown_search_counter ), 0, 10 );

		$atts = shortcode_atts(
			[
				'title' => '',
				'class' => '',
				'id'    => \HD_Helper::escAttr( $id ),
			],
			$atts,
			'dropdown_search'
		);

		$title             = $atts['title'] ?: __( 'Search', TEXT_DOMAIN );
		$title_for         = __( 'Search for', TEXT_DOMAIN );
		$placeholder_title = \HD_Helper::escAttr( __( 'Search...', TEXT_DOMAIN ) );
		$close_title       = __( 'Close', TEXT_DOMAIN );
		$class             = $atts['class'] ? ' ' . \HD_Helper::escAttr( $atts['class'] ) : '';
		$id                = $atts['id'] ? \HD_Helper::escAttr( $atts['id'] ) : \HD_Helper::escAttr( $id );

		ob_start();

		?>
        <a class="trigger-s" title="<?= \HD_Helper::escAttr( $title ) ?>" href="javascript:;" data-toggle="dropdown-<?= $id ?>" data-fa=""><span><?= $title ?></span></a>
        <div role="search" class="dropdown-pane" id="dropdown-<?= $id ?>" data-dropdown data-auto-focus="true">
            <form action="<?= \HD_Helper::home() ?>" class="frm-search" method="get" accept-charset="UTF-8" data-abide novalidate>
                <div class="frm-container">
                    <label for="<?= $id ?>" class="screen-reader-text"><?= $title_for ?></label>
                    <input id="<?= $id ?>" required pattern="^(.*\S+.*)$" type="search" name="s" value="<?= get_search_query() ?>" placeholder="<?= $placeholder_title ?>">
                    <button class="btn-s" type="submit" data-fa="" aria-label="Search"><span><?= $title ?></span></button>
                    <button class="trigger-s-close" type="button" data-fa="" aria-label="Close"><span><?= $close_title ?></span></button>
                </div>
	            <?php echo \HD_Helper::isWoocommerceActive() ? '<input type="hidden" name="post_type" value="product">' : ''; ?>
            </form>
        </div>
		<?php

		return '<div class="dropdown-search' . $class . '">' . ob_get_clean() . '</div>';
	}

	// ------------------------------------------------------
}
