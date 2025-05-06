<?php

namespace HD\Utilities\Widgets;

use HD\Utilities\Widgets\Base\AbstractWidget;

\defined( 'ABSPATH' ) || die;

class RecentPostsWidget extends AbstractWidget {
	public function __construct() {
		$this->widget_description = __( 'Your site&#8217;s most recent Posts.' );
		$this->widget_name        = __( '* Recent Posts', TEXT_DOMAIN );
		$this->settings           = [
			'title'          => [
				'type'  => 'text',
				'std'   => __( 'Recent posts', TEXT_DOMAIN ),
				'label' => __( 'Title', TEXT_DOMAIN ),
			],
			'number'         => [
				'type'  => 'number',
				'min'   => 0,
				'max'   => 99,
				'std'   => 5,
				'class' => 'tiny-text',
				'label' => __( 'Number of posts to show:', TEXT_DOMAIN ),
			],
			'show_cat'       => [
				'type'  => 'checkbox',
				'std'   => '',
				'class' => 'checkbox',
				'label' => __( 'Show categories', TEXT_DOMAIN ),
			],
			'show_thumbnail' => [
				'type'  => 'checkbox',
				'std'   => '',
				'class' => 'checkbox',
				'label' => __( 'Show thumbnail', TEXT_DOMAIN ),
			],
			'show_date'      => [
				'type'  => 'checkbox',
				'std'   => '',
				'class' => 'checkbox',
				'label' => __( 'Show post date', TEXT_DOMAIN ),
			],
			'show_desc'      => [
				'type'  => 'checkbox',
				'std'   => '',
				'class' => 'checkbox',
				'label' => __( 'Show post description', TEXT_DOMAIN ),
			],
			'limit_time'     => [
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'Time limit', TEXT_DOMAIN ),
				'desc'  => sprintf( __( "A date/time string, restrict to only posts within a specific time period. %s", TEXT_DOMAIN ), "\n<a target='_blank' href=\"https://www.php.net/manual/en/function.strtotime.php\">php.net/manual/en/function.strtotime.php</a>" ),
			],
			'css_class'         => [
				'type'  => 'text',
				'std'   => '',
				'label' => __( 'CSS class', TEXT_DOMAIN ),
			],
		];

		parent::__construct();
	}

	/**
	 * Outputs the content for the current Recent Posts widget instance.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ): void {
		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		$title = $this->get_instance_title( $instance );

		$number         = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		$show_cat       = ! empty( $instance['show_cat'] );
		$show_thumbnail = ! empty( $instance['show_thumbnail'] );
		$show_date      = ! empty( $instance['show_date'] );
		$show_desc      = ! empty( $instance['show_desc'] );

		$limit_time = $instance['limit_time'] ? trim( $instance['limit_time'] ) : '';
		$css_class  = ! empty( $instance['css_class'] ) ? \HD_Helper::escAttr( $instance['css_class'] ) : '';
		$uniqid     = esc_attr( uniqid( $this->widget_classname . '-', false ) );

        $r = \HD_Helper::queryByLatestPosts( 'post', $number, $limit_time );
		if ( ! $r ) {
			return;
		}

		ob_start();

		?>
        <div class="<?php echo $css_class; ?>">
			<?php if ( $title ) : ?>
            <span class="sidebar-title"><?php echo $title; ?></span>
			<?php endif;

			// The title may be filtered: Strip out HTML and make sure the aria-label is never empty.
			$aria_label = $title ? \HD_Helper::escAttr( $title ) : __( 'Recent Posts', TEXT_DOMAIN );

			?>
            <nav class="<?= $uniqid ?>" aria-label="<?php echo $aria_label; ?>">
                <ul>
					<?php
					$ratio_class = \HD_Helper::aspectRatioClass();

					foreach ( $r->posts as $recent_post ) :
						$post_title = get_the_title( $recent_post->ID );
						$post_title     = ( ! empty( $post_title ) ) ? $post_title : __( '(no title)', TEXT_DOMAIN );

                        $attr_post_title = \HD_Helper::escAttr( $post_title );
						$post_thumbnail = \HD_Helper::postImageHTML( $recent_post, 'medium', [ 'alt' => $attr_post_title ], true );

						$aria_current = '';
						if ( get_queried_object_id() === $recent_post->ID ) {
							$aria_current = ' aria-current="page"';
						}
                    ?>
                    <li>
                        <?php if ( $show_thumbnail && $post_thumbnail ) : ?>
                        <a class="block cover" href="<?php the_permalink( $recent_post->ID ); ?>" aria-label="<?php echo $attr_post_title; ?>">
                            <span class="res <?= $ratio_class ?>"><?php echo $post_thumbnail; ?></span>
                        </a>
                        <?php endif; ?>
                        <div class="cover-content">
                            <a href="<?php the_permalink( $recent_post->ID ); ?>" title="<?php echo $attr_post_title; ?>"<?php echo $aria_current; ?>><?php echo $post_title; ?></a>
                            <?php if ( $show_date || $show_cat ) : ?>
                            <div class="meta">
                                <?php if ( $show_date ) : ?>
                                <span class="post-date"><?php echo \HD_Helper::humanizeTime( $recent_post ); ?></span>
                                <?php endif;
                                echo $show_cat ? \HD_Helper::getPrimaryTerm( $recent_post ) : '';
                                ?>
                            </div>
                            <?php
                            endif;
                            echo $show_desc ? \HD_Helper::loopExcerpt( $recent_post ) : '';
                            ?>
                        </div>
                    </li>
					<?php endforeach; ?>
                </ul>
            </nav>
        </div>
		<?php

		echo $this->cache_widget( $args, ob_get_clean() );
	}
}
