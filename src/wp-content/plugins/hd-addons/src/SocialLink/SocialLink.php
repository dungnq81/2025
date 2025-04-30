<?php

namespace Addons\SocialLink;

use Addons\Helper;

\defined( 'ABSPATH' ) || exit;

final class SocialLink {
	// ------------------------------------------------------

	public function __construct() {
		/**
		 * @var array $shortcodes
		 */
		$shortcodes = [
			'social_menu' => [ $this, 'social_menu' ],
		];

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}
	}

	// ------------------------------------------------------

	/**
	 * @param array $atts
	 *
	 * @return string
	 */
	public function social_menu( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'class' => 'social-menu',
			],
			$atts,
			'social_menu'
		);

		$class = $atts['class'] ? ' ' . Helper::escAttr( $atts['class'] ) : ' social-menu';

		ob_start();

		$social_options       = Helper::getOption( 'social_link__options' );
		$social_follows_links = Helper::filterSettingOptions( 'social_follows_links', [] );

		if ( $social_options ) {
			foreach ( $social_options as $key => $social_option ) {
				$data = [
					'name' => $social_follows_links[ $key ]['name'] ?? '',
					'icon' => $social_follows_links[ $key ]['icon'] ?? '',
					'url'  => $social_option['url'] ?? '',
				];

				$thumb = '';
				if ( Helper::isUrl( $data['icon'] ) || str_starts_with( $data['icon'], 'data:' ) ) :
					$thumb = '<img width="24" height="24" src="' . $data['icon'] . '" alt="' . Helper::escAttr( $data['name'] ) . '-alt">';
                elseif ( str_starts_with( $data['icon'], '<svg' ) ) :
					$thumb = $data['icon'];
                elseif ( is_string( $data['icon'] ) ) :
					$thumb = '<i class="' . $data['icon'] . '"></i>';
				endif;

				if ( ! empty( $data['url'] ) ) :
                ?>
                <li>
                    <a class="<?= $key ?>" href="<?= $data['url'] ?>" title="<?= Helper::escAttr( $data['name'] ) ?>" target="_blank">
                        <?= $thumb ?>
                        <span class="sr-only"><?= $data['name'] ?></span>
                    </a>
                </li>
				<?php
                endif;
			}
		}

		return '<ul class="menu' . $class . '">' . ob_get_clean() . '</ul>';
	}
}
