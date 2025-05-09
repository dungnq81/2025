<?php
/**
 * The template for displaying `homepage`
 * Template Name: Home
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

$ACF = \HD_Helper::getFields( get_the_ID() );
$home_flexible_content = ! empty( $ACF['home_flexible_content'] ) ? (array) $ACF['home_flexible_content'] : false;
if ( $home_flexible_content ) {

	foreach ( $home_flexible_content as $section ) {
		$acf_fc_layout = $section['acf_fc_layout'] ?? '';

		if ( $acf_fc_layout ) {
			\HD_Helper::blockTemplate( 'template-parts/home/' . $acf_fc_layout, $section );
		}
	}
} else {
	\HD_Helper::blockTemplate( 'template-blocks/static-page' );
}

?>
    <div class="container">
        <h1>Lorem ipsum dolor sit amet, consectetur adipiscing elit</h1>
        <h2>Lorem ipsum dolor sit amet, consectetur adipiscing elit</h2>
        <h3>Lorem ipsum dolor sit amet, consectetur adipiscing elit</h3>
        <h4>Lorem ipsum dolor sit amet, consectetur adipiscing elit</h4>
        <h5>Lorem ipsum dolor sit amet, consectetur adipiscing elit</h5>
        <h6>Lorem ipsum dolor sit amet, consectetur adipiscing elit</h6>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
    </div>
<?php

// footer
get_footer( 'home' );
