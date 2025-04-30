<?php

namespace Addons\ThirdParty\AcfField;

\defined( 'ABSPATH' ) || exit;

/**
 * @author Galaxy Weblinks
 * https://wordpress.org/plugins/acf-nav-menu/
 *
 * Modified by Gaudev
 */
class NavMenu extends \acf_field {
	public function __construct() {
		$this->name     = 'nav_menu';
		$this->label    = esc_html__( 'Nav Menu', ADDONS_TEXTDOMAIN );
		$this->category = 'choice';
		$this->defaults = [
			'save_format' => 'menu',
			'allow_null'  => 0,
			'container'   => 'div',
		];

		parent::__construct();
	}

	// ----------------------------------------------

	/**
	 * @param $field
	 *
	 * @return void
	 */
	public function render_field_settings( $field ): void {
		// Register the Return Value format setting
		acf_render_field_setting( $field, [
			'label'        => esc_html__( 'Return Value', ADDONS_TEXTDOMAIN ),
			'instructions' => esc_html__( 'Specify the returned value on front end', ADDONS_TEXTDOMAIN ),
			'type'         => 'radio',
			'name'         => 'save_format',
			'layout'       => 'horizontal',
			'choices'      => [
				'menu'   => esc_html__( 'Nav Menu HTML', ADDONS_TEXTDOMAIN ),
				'object' => esc_html__( 'Nav Menu Object', ADDONS_TEXTDOMAIN ),
				'id'     => esc_html__( 'Nav Menu ID', ADDONS_TEXTDOMAIN ),
			],
		] );

		// Register the Menu Container setting
		acf_render_field_setting( $field, [
			'label'        => esc_html__( 'Menu Container', ADDONS_TEXTDOMAIN ),
			'instructions' => esc_html__( "What to wrap the Menu's ul with (when returning HTML only)", ADDONS_TEXTDOMAIN ),
			'type'         => 'select',
			'name'         => 'container',
			'choices'      => $this->_get_allowed_nav_container_tags(),
		] );

		// Register the Allow Null setting
		acf_render_field_setting( $field, [
			'label'   => esc_html__( 'Allow Null?', ADDONS_TEXTDOMAIN ),
			'type'    => 'radio',
			'name'    => 'allow_null',
			'layout'  => 'horizontal',
			'choices' => [
				1 => esc_html__( 'Yes', ADDONS_TEXTDOMAIN ),
				0 => esc_html__( 'No', ADDONS_TEXTDOMAIN ),
			],
		] );
	}

	// ----------------------------------------------

	/**
	 * @return string[]
	 */
	private function _get_allowed_nav_container_tags(): array {
		$tags           = apply_filters( 'wp_nav_menu_container_allowedtags', [ 'div', 'nav' ] );
		$formatted_tags = [
			'0' => 'None',
		];

		foreach ( $tags as $tag ) {
			$formatted_tags[ $tag ] = ucfirst( $tag );
		}

		return $formatted_tags;
	}

	// ----------------------------------------------

	/**
	 * @param $field
	 *
	 * @return void
	 */
	public function render_field( $field ): void {
		$allow_null = $field['allow_null'];
		$nav_menus  = $this->_get_nav_menus( $allow_null );

		if ( empty( $nav_menus ) ) {
			return;
		}

		?>
        <div class="custom-acf-nav-menu">
            <select id="<?= \Addons\Helper::escAttr( $field['id'] ) ?>"
                    class="<?= \Addons\Helper::escAttr( $field['class'] ) ?>"
                    name="<?= \Addons\Helper::escAttr( $field['name'] ) ?>" title>
				<?php foreach ( $nav_menus as $nav_menu_id => $nav_menu_name ) : ?>
                    <option value="<?= \Addons\Helper::escAttr( $nav_menu_id ) ?>" <?php selected( $field['value'], $nav_menu_id ); ?>>
						<?= esc_html( $nav_menu_name ) ?>
                    </option>
				<?php endforeach; ?>
            </select>
        </div>
		<?php
	}

	// ----------------------------------------------

	/**
	 * @param bool $allow_null
	 *
	 * @return array
	 */
	private function _get_nav_menus( bool $allow_null = false ): array {
		$navs      = get_terms( 'nav_menu', [ 'hide_empty' => false ] );
		$nav_menus = [];

		if ( $allow_null ) {
			$nav_menus[''] = esc_html__( '- Select -', ADDONS_TEXTDOMAIN );
		}

		foreach ( $navs as $nav ) {
			$nav_menus[ $nav->term_id ] = $nav->name;
		}

		return $nav_menus;
	}

	// ----------------------------------------------

	/**
	 * @param $value
	 * @param $post_id
	 * @param $field
	 *
	 * @return \stdClass|string|false The Nav Menu ID, or the Nav Menu HTML, or the Nav Menu Object, or false.
	 */
	public function format_value( $value, $post_id, $field ): \stdClass|string|false {
		// bail early if no value
		if ( empty( $value ) ) {
			return false;
		}

		// check a format
		if ( 'object' === $field['save_format'] ) {
			$wp_menu_object = wp_get_nav_menu_object( $value );
			if ( empty( $wp_menu_object ) ) {
				return false;
			}

			$menu_object        = new \stdClass;
			$menu_object->ID    = $wp_menu_object->term_id;
			$menu_object->name  = $wp_menu_object->name;
			$menu_object->slug  = $wp_menu_object->slug;
			$menu_object->count = $wp_menu_object->count;

			return $menu_object;
		}

		if ( 'menu' === $field['save_format'] ) {
			return wp_nav_menu( [
				'echo'            => false,
				'menu'            => $value,
				'container_class' => 'acf-nav-menu',
				'container'       => $field['container'],
				'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			] );
		}

		// Just return the Nav Menu ID
		return $value;
	}

	// ----------------------------------------------

	/**
	 * @param $value
	 * @param $post_id
	 * @param $field
	 *
	 * @return mixed
	 */
	public function load_value( $value, $post_id, $field ): mixed {
		return $value;
	}

	// ----------------------------------------------
}
