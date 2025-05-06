<?php

declare( strict_types=1 );

namespace HD\Core;

use HD\Utilities\Traits\Singleton;

\defined( 'ABSPATH' ) || die;

/**
 * Customizer Class
 *
 * @author Gaudev
 */
final class Customizer {
	use Singleton;

	// --------------------------------------------------

	/**
	 * @return void
	 */
	private function init(): void {
		// Theme Customizer settings and controls.
		add_action( 'customize_register', [ $this, 'customize_register' ], 30 );
	}

	// --------------------------------------------------

	/**
	 * Register customizer options.
	 *
	 * @param \WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function customize_register( \WP_Customize_Manager $wp_customize ): void {
		$this->_logo_and_title( $wp_customize );

		// -------------------------------------------------------------

		$wp_customize->add_panel(
			'addon_menu_panel',
			[
				'priority'       => 140,
				'theme_supports' => '',
				'title'          => __( 'Addons', TEXT_DOMAIN ),
				'description'    => __( 'Controls the add-on menu', TEXT_DOMAIN ),
			]
		);

		// -------------------------------------------------------------
		// Login page
		// -------------------------------------------------------------

		$wp_customize->add_section(
			'login_page_section',
			[
				'title'    => __( 'Login page', TEXT_DOMAIN ),
				'panel'    => 'addon_menu_panel',
				'priority' => 999,
			]
		);

		$wp_customize->add_setting( 'login_page_bgcolor_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_hex_color',
		] );

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'login_page_bgcolor_control',
				[
					'label'    => __( 'Background color', TEXT_DOMAIN ),
					'section'  => 'login_page_section',
					'settings' => 'login_page_bgcolor_setting',
					'priority' => 8,
				]
			)
		);

		$wp_customize->add_setting( 'login_page_bgimage_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => '_sanitize_image',
		] );

		$wp_customize->add_control(
			new \WP_Customize_Image_Control(
				$wp_customize,
				'login_page_bgimage_control',
				[
					'label'    => __( 'Background image', TEXT_DOMAIN ),
					'section'  => 'login_page_section',
					'settings' => 'login_page_bgimage_setting',
					'priority' => 9,
				]
			)
		);

		$wp_customize->add_setting( 'login_page_logo_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => '_sanitize_image',
		] );

		$wp_customize->add_control(
			new \WP_Customize_Image_Control(
				$wp_customize,
				'login_page_logo_control',
				[
					'label'    => __( 'Logo', TEXT_DOMAIN ),
					'section'  => 'login_page_section',
					'settings' => 'login_page_logo_setting',
					'priority' => 10,
				]
			)
		);

		$wp_customize->add_setting( 'login_page_headertext_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		$wp_customize->add_control(
			'login_page_headertext_control',
			[
				'label'       => __( 'Header text', TEXT_DOMAIN ),
				'section'     => 'login_page_section',
				'settings'    => 'login_page_headertext_setting',
				'type'        => 'text',
				'priority'    => 11,
				'description' => __( 'Changing the alt text', TEXT_DOMAIN ),
			]
		);

		$wp_customize->add_setting( 'login_page_headerurl_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		$wp_customize->add_control(
			'login_page_headerurl_control',
			[
				'label'       => __( 'Header url', TEXT_DOMAIN ),
				'section'     => 'login_page_section',
				'settings'    => 'login_page_headerurl_setting',
				'type'        => 'url',
				'priority'    => 12,
				'description' => __( 'Changing the logo link', TEXT_DOMAIN ),
			]
		);

		// -------------------------------------------------------------
		// OffCanvas Menu
		// -------------------------------------------------------------

		$wp_customize->add_section(
			'offcanvas_menu_section',
			[
				'title'    => __( 'OffCanvas', TEXT_DOMAIN ),
				'panel'    => 'addon_menu_panel',
				'priority' => 1000,
			]
		);

		// Add offcanvas control
		$wp_customize->add_setting( 'offcanvas_menu_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		$wp_customize->add_control(
			'offcanvas_menu_control',
			[
				'label'    => __( 'offCanvas position', TEXT_DOMAIN ),
				'type'     => 'radio',
				'section'  => 'offcanvas_menu_section',
				'settings' => 'offcanvas_menu_setting',
				'choices'  => [
					'left'    => __( 'Left', TEXT_DOMAIN ),
					'right'   => __( 'Right', TEXT_DOMAIN ),
					'top'     => __( 'Top', TEXT_DOMAIN ),
					'bottom'  => __( 'Bottom', TEXT_DOMAIN ),
					'default' => __( 'Default (Right)', TEXT_DOMAIN ),
				],
			]
		);

		// -------------------------------------------------------------
		// Breadcrumbs
		// -------------------------------------------------------------

		$wp_customize->add_section(
			'breadcrumb_section',
			[
				'title'    => __( 'Breadcrumb', TEXT_DOMAIN ),
				'panel'    => 'addon_menu_panel',
				'priority' => 1007,
			]
		);

		// Add control
		$wp_customize->add_setting( 'breadcrumb_bg_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => '_sanitize_image',
		] );

		$wp_customize->add_control(
			new \WP_Customize_Image_Control(
				$wp_customize,
				'breadcrumb_bg_control',
				[
					'label'    => __( 'Background image', TEXT_DOMAIN ),
					'section'  => 'breadcrumb_section',
					'settings' => 'breadcrumb_bg_setting',
					'priority' => 9,
				]
			)
		);

		// Add control
		$wp_customize->add_setting( 'breadcrumb_bgcolor_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_hex_color',
		] );

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'breadcrumb_bgcolor_control',
				[
					'label'    => __( 'Background color', TEXT_DOMAIN ),
					'section'  => 'breadcrumb_section',
					'settings' => 'breadcrumb_bgcolor_setting',
					'priority' => 9,
				]
			)
		);

		// Add control
		$wp_customize->add_setting( 'breadcrumb_color_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_hex_color',
		] );

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'breadcrumb_color_control',
				[
					'label'    => __( 'Text color', TEXT_DOMAIN ),
					'section'  => 'breadcrumb_section',
					'settings' => 'breadcrumb_color_setting',
					'priority' => 9,
				]
			)
		);

		// Min height control
		$wp_customize->add_setting( 'breadcrumb_min_height_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		$wp_customize->add_control(
			'breadcrumb_min_height_control',
			[
				'label'       => __( 'Breadcrumb min-height', TEXT_DOMAIN ),
				'section'     => 'breadcrumb_section',
				'settings'    => 'breadcrumb_min_height_setting',
				'type'        => 'number',
				'description' => __( 'Min-height of breadcrumb section', TEXT_DOMAIN ),
			]
		);

		// Max height control
		$wp_customize->add_setting( 'breadcrumb_max_height_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		$wp_customize->add_control(
			'breadcrumb_max_height_control',
			[
				'label'       => __( 'Breadcrumb max-height', TEXT_DOMAIN ),
				'section'     => 'breadcrumb_section',
				'settings'    => 'breadcrumb_max_height_setting',
				'type'        => 'number',
				'description' => __( 'Max-height of breadcrumb section', TEXT_DOMAIN ),
			]
		);

		// -------------------------------------------------------------
		// Footer
		// -------------------------------------------------------------

		$wp_customize->add_section(
			'footer_section',
			[
				'title'    => __( 'Footer', TEXT_DOMAIN ),
				'panel'    => 'addon_menu_panel',
				'priority' => 1010,
			]
		);

		$wp_customize->add_setting( 'footer_credit_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		$wp_customize->add_control(
			'footer_credit_control',
			[
				'label'    => __( 'Footer copyright', TEXT_DOMAIN ),
				'section'  => 'footer_section',
				'settings' => 'footer_credit_setting',
				'type'     => 'text',
				'priority' => 10,
			]
		);

		// -------------------------------------------------------------
		// Others
		// -------------------------------------------------------------

		$wp_customize->add_section(
			'other_section',
			[
				'title'    => __( 'Other', TEXT_DOMAIN ),
				'panel'    => 'addon_menu_panel',
				'priority' => 1011,
			]
		);

		// Meta theme-color
		$wp_customize->add_setting( 'theme_color_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_hex_color',
		] );

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'theme_color_control',
				[
					'label'    => __( 'Theme Color', TEXT_DOMAIN ),
					'section'  => 'other_section',
					'settings' => 'theme_color_setting',
				]
			)
		);

		// Hide a menu
		$wp_customize->add_setting( 'remove_menu_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_textarea_field',
		] );

		$wp_customize->add_control(
			'remove_menu_control',
			[
				'type'        => 'textarea',
				'section'     => 'other_section',
				'settings'    => 'remove_menu_setting',
				'label'       => __( 'Remove Menu', TEXT_DOMAIN ),
				'description' => __( 'The menu list will be hidden', TEXT_DOMAIN ),
			]
		);
	}

	// --------------------------------------------------

	/**
	 * @param \WP_Customize_Manager $wp_customize
	 *
	 * @return void
	 */
	private function _logo_and_title( \WP_Customize_Manager $wp_customize ): void {

		// -------------------------------------------------------------
		// Alternative Logo
		// -------------------------------------------------------------

		$wp_customize->add_setting( 'alt_logo', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => '_sanitize_image',
		] );

		$wp_customize->add_control(
			new \WP_Customize_Image_Control(
				$wp_customize,
				'alt_logo',
				[
					'label'    => __( 'Alternative Logo', TEXT_DOMAIN ),
					'section'  => 'title_tagline',
					'settings' => 'alt_logo',
					'priority' => 8,
				]
			)
		);

		// -------------------------------------------------------------
		// Logo title
		// -------------------------------------------------------------

		$wp_customize->add_setting( 'logo_title_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		$wp_customize->add_control(
			'logo_title_control',
			[
				'label'    => __( 'Logo title', TEXT_DOMAIN ),
				'section'  => 'title_tagline',
				'settings' => 'logo_title_setting',
				'type'     => 'text',
				'priority' => 9,
			]
		);

		// -------------------------------------------------------------
		// H1 on the homepage
		// -------------------------------------------------------------

		$wp_customize->add_setting( 'home_heading_setting', [
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => 'sanitize_text_field',
		] );

		$wp_customize->add_control(
			'home_heading_control',
			[
				'label'    => __( 'H1 on the homepage', TEXT_DOMAIN ),
				'section'  => 'title_tagline',
				'settings' => 'home_heading_setting',
				'type'     => 'text',
				'priority' => 9,
			]
		);
	}
}
