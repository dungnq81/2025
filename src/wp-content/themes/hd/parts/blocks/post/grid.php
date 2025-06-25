<?php

\defined( 'ABSPATH' ) || die;

$query     = $args['query'] ?? null;
$title_tag = $args['title_tag'] ?? 'p';
$max       = $args['max'] ?? false;

if ( ! $query ) {
	return;
}

$i = 0;
while ( $query?->have_posts() ) : $query->the_post();
	$i ++;
	if ( $max && $i > $max ) {
		break;
	}

	$post_type = get_post_type( $post->ID ) ?: 'post';

	echo "<div class=\"cell\">";
	\HD_Helper::blockTemplate( 'parts/' . $post_type . '/loop', [ 'title_tag' => $title_tag ] );
	echo "</div>";

endwhile;
wp_reset_postdata();
