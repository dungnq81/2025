<?php

\defined( 'ABSPATH' ) || die;

global $post;

$post_id         = $args['id'] ?? $post->ID;
$suggestion_list = \HD_Helper::getField( 'suggestion', $post_id );
if ( ! $suggestion_list ) {
	return;
}

?>
<ul class="suggestion-list">
	<?php
	foreach ( $suggestion_list as $suggestion_id ) :
		$post_title = get_the_title( $suggestion_id );
		$post_title = ! empty( $post_title ) ? $post_title : __( '(no title)', TEXT_DOMAIN );
    ?>
    <li>
        <a title="<?= \HD_Helper::escAttr( $post_title ) ?>" class="title" href="<?php the_permalink( $suggestion_id ); ?>"><?php echo $post_title; ?></a>
        <span class="date"><?php echo \HD_Helper::humanizeTime( $suggestion_id ); ?></span>
    </li>
	<?php endforeach; ?>
</ul>
