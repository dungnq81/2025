<?php
/**
 * The template for displaying the header.
 * This is the template that displays all the <head> section, opens the <body> tag and adds the site's header.
 *
 * @author Gaudev
 */

\defined( 'ABSPATH' ) || die;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <?php
    /**
     * HOOK: wp_head
     *
     * @see Hook::wp_head_action - 1
     * @see Hook::other_head_action - 10
     * @see Hook::external_fonts_action - 11
     */
    wp_head();
    ?>
</head>
<body <?php body_class(); ?> <?= \HD_Helper::microdata( 'body' ) ?>>
    <?php
    /**
     * HOOK: wp_body_open
     *
     * @see CustomScript::body_scripts_top__hook - 99
     */
    do_action( 'wp_body_open' );

    /**
     * HOOK: hd_header_before_action
     *
     * @see Hook::skip_to_content_link_action - 2
     * @see Hook::off_canvas_menu_action - 11
     */
    do_action( 'hd_header_before_action' );
    ?>
    <header id="header" class="<?= apply_filters( 'hd_header_class_filter', 'site-header' ) ?>" <?= \HD_Helper::microdata( 'header' ) ?>>
		<?php
		/**
         * HOOK: hd_header_action
         *
		 * @see Hook::construct_header_action - 10
		 */
		do_action( 'hd_header_action' );
		?>
    </header><!-- #header -->
    <?php
    /**
     * HOOK: hd_header_after_action
     */
    do_action( 'hd_header_after_action' );
    ?>
    <div class="main site-content" id="site-content">
        <?php
        /**
         * HOOK: hd_site_content_before_action
         */
        do_action( 'hd_site_content_before_action' );
