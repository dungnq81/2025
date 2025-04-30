<?php

namespace Addons\CustomScript;

use Addons\Helper;

\defined( 'ABSPATH' ) || exit;

final class CustomScript {
	// ------------------------------------------------------

	public function __construct() {
		add_action( 'wp_head', [ $this, 'header_scripts__hook' ], 99 ); // header scripts
		add_action( 'wp_body_open', [ $this, 'body_scripts_top__hook' ], 99 ); // body scripts - TOP

		add_action( 'wp_footer', [ $this, 'footer_scripts__hook' ], 1 ); // footer scripts
		add_action( 'wp_footer', [ $this, 'body_scripts_bottom__hook' ], 998 ); // body scripts - BOTTOM
	}

	// ------------------------------------------------------

	/**
	 * Header scripts
	 */
	public function header_scripts__hook(): void {
		$html_header = Helper::getCustomPostContent( 'html_header', true );
		if ( $html_header && ! Helper::lightHouse() ) {
			echo Helper::JSMinify( $html_header, true );
		}
	}

	// ------------------------------------------------------

	/**
	 * Body scripts - TOP
	 */
	public function body_scripts_top__hook(): void {
		$html_body_top = Helper::getCustomPostContent( 'html_body_top', true );
		if ( $html_body_top && ! Helper::lightHouse() ) {
			echo Helper::JSMinify( $html_body_top, true );
		}
	}

	// ------------------------------------------------------

	/**
	 * Footer scripts
	 */
	public function footer_scripts__hook(): void {
		$html_footer = Helper::getCustomPostContent( 'html_footer', true );
		if ( $html_footer && ! Helper::lightHouse() ) {
			echo Helper::JSMinify( $html_footer, true );
		}
	}

	// ------------------------------------------------------

	/**
	 * Body scripts - BOTTOM
	 */
	public function body_scripts_bottom__hook(): void {
		$html_body_bottom = Helper::getCustomPostContent( 'html_body_bottom', true );
		if ( $html_body_bottom && ! Helper::lightHouse() ) {
			echo Helper::JSMinify( $html_body_bottom, true );
		}
	}

	// ------------------------------------------------------
}
