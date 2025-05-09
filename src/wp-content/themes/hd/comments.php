<?php
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 */

\defined( 'ABSPATH' ) || die;

if ( post_password_required() ) {
	return;
}

if ( ! post_type_supports( get_post_type(), 'comments' ) ) {
	return;
}

if ( ! have_comments() && ! comments_open() ) {
	return;
}

?>
<section id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>
        <h2 class="title-comments">
			<?php
			$comments_number = get_comments_number();
			if ( '1' === $comments_number ) {
				printf( esc_html_x( 'One Response', 'comments title', TEXT_DOMAIN ) );
			} else {
				printf(
				    /* translators: %s: Number of comments. */
					esc_html(
						_nx(
							'%s Response',
							'%s Responses',
							$comments_number,
							'comments title',
							TEXT_DOMAIN
						)
					),
					esc_html( number_format_i18n( $comments_number ) )
				);
			}
			?>
        </h2>

		<?php the_comments_navigation(); ?>

        <ol class="comment-list">
			<?php
			echo wp_list_comments(
				[
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 42,
					'echo'        => false,
				]
			);
			?>
        </ol>

		<?php the_comments_navigation(); ?>

	<?php endif; ?>

	<?php
	comment_form(
		[
			'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title">',
			'title_reply_after'  => '</h2>',
		]
	);
	?>

</section>
