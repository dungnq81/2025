<?php
/**
 * The Template for displaying all single posts.
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @author Gaudev
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( 'single' );

if ( have_posts() ) {
	the_post();
}

if ( post_password_required() ) {
	echo get_the_password_form();
	get_footer( 'single' );

	return;
}

// breadcrumbs
\HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs', [ 'title' => \HD_Helper::primaryTerm( $post )?->name ] );

/**
 * HOOK: hd_single_before_action
 */
do_action( 'hd_single_before_action' );

$alternative_title = \HD_Helper::getField( 'alternative_title', $post->ID );
$featured_banner   = \HD_Helper::getField( 'featured_banner', $post->ID );

?>
<section class="section section-page section-single singular">
    <div class="container flex flex-x">

        <?php \HD_Helper::blockTemplate( 'parts/blocks/social-share', [], true ); ?>

        <div class="content">
            <h1 class="heading-title" <?= \HD_Helper::microdata( 'headline' ) ?>><?= $alternative_title ?: get_the_title() ?></h1>
            <div class="meta">
	            <?php echo \HD_Helper::getPrimaryTerm( $post ); ?>
                <span class="date" <?= \HD_Helper::microdata( 'date-published' ) ?> data-fa=""><?= \HD_Helper::humanizeTime( $post->ID ) ?></span>
                <?php

                $views = get_post_meta( $post->ID, '_post_views', true );
                $views = $views ? (int) $views : 1;

                ?>
                <span class="views" data-fa=""><?= number_format_i18n( $views ) ?></span>
            </div>

				<?php echo $featured_banner ? \HD_Helper::pictureHTML( 'featured-img', $featured_banner ) : ''; ?>
				<?php echo \HD_Helper::postExcerpt( $post, 'excerpt', 'div', false ); ?>

                <article class="entry-content" <?= \HD_Helper::microdata( 'article' ) ?>>
					<?php

					the_content();
					\HD_Helper::blockTemplate( 'parts/blocks/suggestion-posts' );

					?>
                </article>
				<?php

				\HD_Helper::hashTags();
				\HD_Helper::blockTemplate( 'parts/blocks/author' );

				// If comments are open, or we have at least one comment, load up the comment template.
				comments_template();

				?>
            </div>
			<?php if ( is_active_sidebar( 'news-sidebar' ) ) : ?>
                <aside class="sidebar" <?= \HD_Helper::microdata( 'sidebar' ) ?>>
                    <div class="sidebar-inner">
						<?php dynamic_sidebar( 'news-sidebar' ); ?>
                    </div>
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

\HD_Helper::blockTemplate( 'parts/blocks/post/related-posts', [
	'title'     => __( 'Bài viết liên quan', TEXT_DOMAIN ),
	'title_tag' => 'h2',
	'id'        => $post->ID,
	'max'       => 12,
	'rows'      => 1,
	'taxonomy'  => 'category',
] );

/**
 * HOOK: hd_single_after_action
 */
do_action( 'hd_single_after_action' );

// footer
get_footer( 'single' );
