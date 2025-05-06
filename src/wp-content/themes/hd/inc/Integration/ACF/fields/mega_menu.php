<?php

\defined( 'ABSPATH' ) || die;

add_action( 'acf/include_fields', static function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$location = [];
	$acf_menu = \HD_Helper::filterSettingOptions( 'acf_menu', [] );

	foreach ( $acf_menu['acf_mega_menu_locations'] ?? [] as $menu_item ) {
		if ( $menu_item ) {
			$location[] = [
				[
					'param'    => 'nav_menu_item',
					'operator' => '==',
					'value'    => 'location/' . \HD_Helper::toString( $menu_item ),
				],
			];
		}
	}

	acf_add_local_field_group( [
		'key'                   => 'group_64c8be6be97d0',
		'title'                 => 'Attributes of Menu',
		'fields'                => [
			[
				'key'               => 'field_64c8be6c6147a',
				'label'             => 'Mega menu',
				'name'              => 'menu_mega',
				'aria-label'        => '',
				'type'              => 'true_false',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => [
					'width' => '',
					'class' => 'checkbox',
					'id'    => '',
				],
				'message'           => 'Mega menu (optional)',
				'default_value'     => 0,
				'ui'                => 0,
				'ui_on_text'        => '',
				'ui_off_text'       => '',
			],
		],
		'location'              => $location,
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => true,
		'description'           => '',
		'show_in_rest'          => 0,
	] );
} );
