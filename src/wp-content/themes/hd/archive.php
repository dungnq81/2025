<?php
/**
 * The template for displaying archive.
 *
 * @author Gaudev
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( 'archive' );

$object = get_queried_object();

// breadcrumbs
\HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs', [ 'title' => get_the_archive_title() ] );

/**
 * HOOK: hd_archive_before_action
 */
do_action( 'hd_archive_before_action' );

?>
    <section class="section section-page archive">
        <div class="container flex flex-x">
            <div class="content">
                <h1 class="heading-title" <?= \HD_Helper::microdata( 'headline' ) ?>><?= get_the_archive_title() ?></h1>
				<?= \HD_Helper::termExcerpt( $object?->term_id, 'excerpt', 'div' ) ?>
				<?php if ( have_posts() ) : ?>
                    <div class="posts-list archive-list items-list flex flex-x gap sm-up-1 md-up-2 lg-up-4">
						<?php

						// Start the Loop.
						while ( have_posts() ) : the_post();

							echo "<div class=\"cell\">";
							\HD_Helper::blockTemplate( 'parts/post/loop', [ 'title_tag' => 'h2' ] );
							echo "</div>";

							// End the loop.
						endwhile;
						?>
                    </div>
					<?php
					// Previous/next page navigation.
					\HD_Helper::paginateLinks();
				else :
					\HD_Helper::blockTemplate( 'parts/blocks/no-results', [], true );
				endif;
				?>
            </div>
			<?php if ( is_active_sidebar( 'archive-sidebar' ) ) : ?>
                <aside class="sidebar" <?= \HD_Helper::microdata( 'sidebar' ) ?>>
                    <div class="sidebar-inner">
						<?php dynamic_sidebar( 'archive-sidebar' ); ?>
                    </div>
                </aside>
			<?php endif;

			/**
			 * HOOK: hd_archive_sidebar_action
			 */
			do_action( 'hd_archive_sidebar_action' );
			?>
        </div>
    </section>
<?php

/**
 * HOOK: hd_archive_after_action
 */
do_action( 'hd_archive_after_action' );

// footer
get_footer( 'archive' );
