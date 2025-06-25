<?php

\defined( 'ABSPATH' ) || die;

$query      = $args['query'] ?? null;
$title_tag  = $args['title_tag'] ?? 'p';
$max        = $args['max'] ?? false;
$slide_data = $args['slide_data'] ?? '[]';

if ( ! $query ) {
	return;
}

?>
<div class="swiper-container">
    <div class="w-swiper swiper">
        <div class="swiper-wrapper" data-options='<?= $slide_data ?>'>
			<?php
			$i = 0;

			while ( $query?->have_posts() ) : $query->the_post();
				$post = get_post();

				$i ++;
				if ( $max && $i > $max ) {
					break;
				}

				$post_type = get_post_type( $post->ID ) ?: 'post';

				echo "<div class=\"swiper-slide\">";
				\HD_Helper::blockTemplate( 'parts/' . $post_type . '/loop', [ 'title_tag' => $title_tag ] );
				echo "</div>";

			endwhile;
			wp_reset_postdata();
			?>
        </div>
    </div>
</div>
