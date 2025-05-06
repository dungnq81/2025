<?php

\defined( 'ABSPATH' ) || die;

add_action( 'acf/include_fields', static function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$location = [];
	$roles    = [ 'administrator', 'editor' ];

	foreach ( $roles as $role ) {
		$location[] = [
			[
				'param'    => 'user_role',
				'operator' => '==',
				'value'    => \HD_Helper::toString( $role ),
			],
		];
	}

	acf_add_local_field_group( [
		'key'                   => 'group_67dbdc6a82d99',
		'title'                 => 'Author Box',
		'fields'                => [
			[
				'key'               => 'field_67dbdc6b3d3de',
				'label'             => 'Alternative Name',
				'name'              => 'author_alt_name',
				'aria-label'        => '',
				'type'              => 'text',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => [
					'width' => '',
					'class' => '',
					'id'    => '',
				],
				'default_value'     => '',
				'maxlength'         => '',
				'allow_in_bindings' => 0,
				'placeholder'       => '',
				'prepend'           => '',
				'append'            => '',
			],
			[
				'key'               => 'field_67dbde8acc4c4',
				'label'             => 'Alternative Profile Picture',
				'name'              => 'author_alt_profile_picture',
				'aria-label'        => '',
				'type'              => 'image',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => [
					'width' => '',
					'class' => '',
					'id'    => '',
				],
				'return_format'     => 'id',
				'library'           => 'all',
				'min_width'         => '',
				'min_height'        => '',
				'min_size'          => '',
				'max_width'         => '',
				'max_height'        => '',
				'max_size'          => '',
				'mime_types'        => '',
				'allow_in_bindings' => 0,
				'preview_size'      => 'thumbnail',
			],
			[
				'key'               => 'field_67dbdf4ecc4c5',
				'label'             => 'Alternative Biographical Info',
				'name'              => 'author_alt_biographical_info',
				'aria-label'        => '',
				'type'              => 'wysiwyg',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => [
					'width' => '',
					'class' => '',
					'id'    => '',
				],
				'default_value'     => '',
				'allow_in_bindings' => 0,
				'tabs'              => 'all',
				'toolbar'           => 'basic',
				'media_upload'      => 0,
				'delay'             => 0,
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
