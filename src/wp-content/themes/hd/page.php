<?php
/**
 * The Template for displaying all pages.
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @author Gaudev
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( 'page' );

if ( have_posts() ) {
	the_post();
}

if ( post_password_required() ) {
	echo get_the_password_form();
	get_footer( 'page' );

	return;
}

// breadcrumbs
\HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs', [ 'title' => get_the_title( $post->ID ) ] );

/**
 * HOOK: hd_page_before_action
 */
do_action( 'hd_page_before_action' );

$alternative_title = \HD_Helper::getField( 'alternative_title', $post->ID );

?>
<h1 class="test tw:flex">
    <strong>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</strong>
</h1>
<section class="section section-page singular">
    <div class="container flex flex-x">
        <div class="content">
            <h1 class="heading-title" <?= \HD_Helper::microdata( 'headline' ) ?>><?= $alternative_title ?: get_the_title() ?></h1>
	        <?php echo \HD_Helper::postExcerpt( $post, 'excerpt', false ); ?>
            <article <?= \HD_Helper::microdata( 'article' ) ?>>
	            <?php
                the_content();
	            \HD_Helper::blockTemplate( 'parts/blocks/suggestion-posts' );
                ?>
            </article>
        </div>
	    <?php if ( is_active_sidebar( 'page-sidebar' ) ) : ?>
        <aside class="sidebar" <?= \HD_Helper::microdata( 'sidebar' ) ?>>
            <?php dynamic_sidebar( 'page-sidebar' ); ?>
        </aside>
	    <?php endif;

	    /**
	     * HOOK: hd_singular_sidebar_action
	     */
	    do_action( 'hd_singular_sidebar_action' );
	    ?>
	</div>
</section>
<?php

/**
 * HOOK: hd_page_after_action
 */
do_action( 'hd_page_after_action' );

// footer
get_footer( 'page' );
