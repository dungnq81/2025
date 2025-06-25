<?php
/**
 * The template for displaying 404 pages (Not Found).
 * http://codex.wordpress.org/Template_Hierarchy
 *
 * @author Gaudev
 */

\defined( 'ABSPATH' ) || die;

// header
get_header( '404' );

// breadcrumbs
\HD_Helper::blockTemplate( 'parts/blocks/breadcrumbs' );

?>
    <section class="section section-page section-404 singular">
        <div class="container">
            <h1 class="title"><?= __( 'Lỗi 404 - Trang không tồn tại', TEXT_DOMAIN ) ?></h1>
            <p class="excerpt"><?= __( 'Xin lỗi, trang bạn tìm kiếm không tồn tại hoặc đã bị gỡ bỏ.', TEXT_DOMAIN ) ?></p>
            <div class="search-box">
				<?php echo get_search_form( [ 'echo' => false ] ); ?>
            </div>

            <!-- Featured News -->
            <!-- //... -->
        </div>
    </section>
<?php

// footer
get_footer( '404' );
