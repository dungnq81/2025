<?php
/**
 * The template for displaying `homepage`
 * Template Name: Home page
 * Template Post Type: page
 *
 * @author Gaudev
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( 'home' );

if ( have_posts() ) {
	the_post();
}

if ( post_password_required() ) {
	echo get_the_password_form();
	get_footer( 'home' );

	return;
}

$ACF                   = \HD_Helper::getFields( get_the_ID() );
$home_flexible_content = ! empty( $ACF['home_flexible_content'] ) ? (array) $ACF['home_flexible_content'] : false;
if ( $home_flexible_content ) {

	foreach ( $home_flexible_content as $section ) {
		$acf_fc_layout = $section['acf_fc_layout'] ?? '';

		if ( $acf_fc_layout ) {
			\HD_Helper::blockTemplate( 'parts/home/' . $acf_fc_layout, $section );
		}
	}
} else {
	\HD_Helper::blockTemplate( 'parts/blocks/static-page' );
}

// footer
get_footer( 'home' );
