<?php

namespace Addons\ThirdParty;

\defined( 'ABSPATH' ) || exit;

/**
 * RankMath SEO
 *
 * @author Gaudev
 */
final class RankMath {
	// --------------------------------------------------

	public function __construct() {
		add_filter( 'rank_math/frontend/breadcrumb/args', [ $this, 'breadcrumb_args' ] );
		add_filter( 'rank_math/frontend/show_keywords', '__return_true' );

		// Remove admin bar
		add_action( 'wp_before_admin_bar_render', static function () {
			if ( is_admin_bar_showing() ) {
				global $wp_admin_bar;
				$wp_admin_bar->remove_menu( 'rank-math' );
			}
		} );

		/**
		 * Filter to add plugins to the RMS TOC.
		 */

		/** Fixed TOC */
		if ( \Addons\Helper::checkPluginActive( 'fixed-toc/fixed-toc.php' ) ) {
			add_filter( 'rank_math/researches/toc_plugins', static function ( $toc_plugins ) {
				$toc_plugins['fixed-toc/fixed-toc.php'] = 'Fixed TOC';

				return $toc_plugins;
			} );
		}

		/** Tocer */
		if ( \Addons\Helper::checkPluginActive( 'tocer/tocer.php' ) ) {
			add_filter( 'rank_math/researches/toc_plugins', static function ( $toc_plugins ) {
				$toc_plugins['tocer/tocer.php'] = 'Tocer';

				return $toc_plugins;
			} );
		}

		/** Easy Table of Contents */
		if ( \Addons\Helper::checkPluginActive( 'easy-table-of-contents/easy-table-of-contents.php' ) ) {
			add_filter( 'rank_math/researches/toc_plugins', static function ( $toc_plugins ) {
				$toc_plugins['easy-table-of-contents/easy-table-of-contents.php'] = 'Easy Table of Contents';

				return $toc_plugins;
			} );
		}
	}

	// --------------------------------------------------

	/**
	 * @param $args
	 *
	 * @return array
	 */
	public function breadcrumb_args( $args ): array {
		return [
			'delimiter'   => '',
			'wrap_before' => '<ul id="breadcrumbs" class="breadcrumbs" aria-label="Breadcrumbs">',
			'wrap_after'  => '</ul>',
			'before'      => '<li><span property="itemListElement" typeof="ListItem">',
			'after'       => '</span></li>',
		];
	}
}
