<?php

namespace HD\Utilities\Navigation;

\defined( 'ABSPATH' ) || die;

class HorizontalNavWalker extends \Walker_Nav_Menu {
	/**
	 * @param string $output
	 * @param int $depth
	 * @param \stdClass $args An object of wp_nav_menu() arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
			$t = '';
			$n = '';
		} else {
			$t = "\t";
			$n = "\n";
		}
		$indent = str_repeat( $t, $depth );

		// Default class.
		$classes = [ 'sub-menu', 'vertical', 'menu' ];

		/**
		 * Filters the CSS class(es) applied to a menu list element.
		 *
		 * @param string[] $classes Array of the CSS classes that are applied to the menu `<ul>` element.
		 * @param \stdClass $args An object of `wp_nav_menu()` arguments.
		 * @param int $depth Depth of menu item. Used for padding.
		 *
		 * @since 4.8.0
		 *
		 */
		$class_names = implode( ' ', apply_filters( 'nav_menu_submenu_css_class', $classes, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$output .= "{$n}{$indent}<ul$class_names>{$n}";
	}
}
