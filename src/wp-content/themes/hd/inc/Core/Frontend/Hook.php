<?php

declare( strict_types=1 );

namespace HD\Core\Frontend;

use HD\Utilities\Traits\Singleton;

\defined( 'ABSPATH' ) || die;

/**
 * Hook Class
 *
 * @author Gaudev
 */
final class Hook {
	use Singleton;

	private function init(): void {

		// -----------------------------------------------
		// wp_enqueue_scripts
		// -----------------------------------------------

		add_action( 'wp_enqueue_scripts', 'custom_css_action', 99 );

		// -----------------------------------------------
		// wp_head
		// -----------------------------------------------

		add_action( 'wp_head', [ $this, 'wp_head_action' ], 1 );
		add_action( 'wp_head', [ $this, 'other_head_action' ], 10 );
		add_action( 'wp_head', [ $this, 'external_fonts_action' ], 11 );

		// -----------------------------------------------
		// hd_header_before_action
		// -----------------------------------------------

		add_action( 'hd_header_before_action', [ $this, 'skip_to_content_link_action' ], 2 );
		add_action( 'hd_header_before_action', [ $this, 'off_canvas_menu_action' ], 11 );

		// -----------------------------------------------
		// hd_header_action
		// -----------------------------------------------

		add_action( 'hd_header_action', [ $this, 'construct_header_action' ], 10 );
		add_action( 'masthead', [ $this, '_masthead_home_seo_header' ], 10 );
		add_action( 'masthead', [ $this, '_masthead_top_header' ], 12 );
		add_action( 'masthead', [ $this, '_masthead_header' ], 13 );
		add_action( 'masthead', [ $this, '_masthead_bottom_header' ], 14 );
		add_action( 'masthead', [ $this, '_masthead_custom' ], 98 );

		// -----------------------------------------------
		// hd_header_after_action
		// -----------------------------------------------

		// -----------------------------------------------
		// hd_site_content_before_action
		// -----------------------------------------------

		// -----------------------------------------------
		// wp_footer
		// -----------------------------------------------

		add_action( 'wp_footer', [ $this, 'wp_footer_action' ], 32 );
		add_action( 'wp_footer', [ $this, 'wp_footer_custom_js_action' ], 99 );

		// -----------------------------------------------
		// hd_footer_after_action
		// -----------------------------------------------

		// -----------------------------------------------
		// hd_footer_action
		// -----------------------------------------------

		add_action( 'hd_footer_action', [ $this, 'construct_footer_action' ], 10 );
		add_action( 'construct_footer', [ $this, '_construct_footer_columns' ], 11 );
		add_action( 'construct_footer', [ $this, '_construct_footer_credit' ], 12 );
		add_action( 'construct_footer', [ $this, '_construct_footer_custom' ], 98 );

		// -----------------------------------------------
		// hd_footer_before_action
		// -----------------------------------------------

		// -----------------------------------------------
		// hd_site_content_after_action
		// -----------------------------------------------
	}

	// -----------------------------------------------

	public function wp_head_action(): void {
		$meta_viewport = '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
		echo apply_filters( 'hd_meta_viewport_filter', $meta_viewport );

		if ( is_singular() && pings_open() ) {
			printf( '<link rel="pingback" href="%s" />', esc_url( get_bloginfo( 'pingback_url' ) ) );
		}
	}

	// -----------------------------------------------

	public function other_head_action(): void {
		// manifest.json
		if ( is_file( ABSPATH . 'manifest.json' ) ) {
			printf( '<link rel="manifest" href="%s" />', esc_url( home_url( 'manifest.json' ) ) );
		}

		// Theme color
		$theme_color = \HD_Helper::getThemeMod( 'theme_color_setting' );
		if ( $theme_color ) {
			printf( '<meta name="theme-color" content="%s" />', \HD_Helper::escAttr( $theme_color ) );
		}
	}

	// -----------------------------------------------

	public function external_fonts_action(): void {
		//...
	}

	// -----------------------------------------------

	public function skip_to_content_link_action(): void {
		printf(
			'<a class="screen-reader-text skip-link" href="#site-content" title="%1$s">%2$s</a>',
			esc_attr__( 'Skip to content', TEXT_DOMAIN ),
			esc_html__( 'Skip to content', TEXT_DOMAIN )
		);
	}

	// -----------------------------------------------

	public function off_canvas_menu_action(): void {
		//...
	}

	// -----------------------------------------------

	public function construct_header_action(): void {
		/**
		 * @see self::_masthead_home_seo_header - 10
		 * @see self::_masthead_top_header - 12
		 * @see self::_masthead_header - 13
		 * @see self::_masthead_bottom_header - 14
		 * @see self::_masthead_custom - 98
		 */
		do_action( 'masthead' );
	}

	// -----------------------------------------------

	public function _masthead_home_seo_header(): void {
		$home_heading = \HD_Helper::getThemeMod( 'home_heading_setting' );
		$home_heading = ! empty( $home_heading ) ? esc_html( $home_heading ) : get_bloginfo( 'name' );

		echo apply_filters( 'home_seo_header_filter', '<h1 class="sr-only">' . $home_heading . '</h1>' );
	}

	// -----------------------------------------------

	public function _masthead_top_header(): void {
		//...
	}

	// -----------------------------------------------

	public function _masthead_header(): void {
		//...
	}

	// -----------------------------------------------

	public function _masthead_bottom_header(): void {
		//...
	}

	// -----------------------------------------------

	public function _masthead_custom(): void {
		//...
	}

	// -----------------------------------------------

	public function wp_footer_action(): void {
		if ( apply_filters( 'back_to_top_filter', true ) ) {
			echo apply_filters(
				'back_to_top_output_filter',
				sprintf(
					'<a title="%1$s" aria-label="%1$s" rel="nofollow" href="#" class="back-to-top toTop" data-scroll-speed="%2$s" data-scroll-start="%3$s">%4$s</a>',
					esc_attr__( 'Scroll back to top', TEXT_DOMAIN ),
					absint( apply_filters( 'back_to_top_scroll_speed_filter', 400 ) ),
					absint( apply_filters( 'back_to_top_scroll_start_filter', 300 ) ),
					'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24"><g fill="none"><path d="M8.47 4.22a.75.75 0 0 0 0 1.06L15.19 12l-6.72 6.72a.75.75 0 1 0 1.06 1.06l7.25-7.25a.75.75 0 0 0 0-1.06L9.53 4.22a.75.75 0 0 0-1.06 0z" fill="currentColor"></path></g></svg>'
				)
			);
		}
	}

	// -----------------------------------------------

	public function construct_footer_action(): void {
		/**
		 * @see self::_construct_footer_columns - 11
		 * @see self::_construct_footer_credit - 12
		 * @see self::_construct_footer_custom - 32
		 */
		do_action( 'construct_footer' );
	}

	// -----------------------------------------------

	public function _construct_footer_columns(): void {
		?>
        <div id="footer-columns" class="footer-columns">
            <div class="container"></div>
        </div>
		<?php
	}

	// -----------------------------------------------

	public function _construct_footer_credit(): void {
		?>
        <div id="footer-credit" class="footer-credit">
            <div class="container">
				<?php
				$footer_credit = \HD_Helper::getThemeMod( 'footer_credit_setting' );
				$footer_credit = ! empty( $footer_credit ) ? esc_html( $footer_credit ) : '&copy; ' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) . '. ' . esc_html__( 'All rights reserved.', TEXT_DOMAIN );

				echo '<p class="copyright">' . apply_filters( 'footer_credit_filter', $footer_credit ) . '</p>';
				?>
            </div>
        </div>
		<?php
	}

	// -----------------------------------------------

	public function _construct_footer_custom(): void {
		//...
	}

	// -----------------------------------------------

	public function wp_footer_custom_js_action(): void {
		ob_start();

		//-------------------------------------------------
		// Single page
		//-------------------------------------------------

		if ( is_single() && $ID = get_the_ID() ) :
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', async () => {
                let postID = <?= $ID ?>;
                const dateEl = document.querySelector('section.singular .meta > .date');
                const viewsEl = document.querySelector('section.singular .meta > .views');

                if (typeof window.hdConfig !== 'undefined') {
                    const endpointURL = window.hdConfig._restApiUrl + 'single/track_views';
                    try {
                        const resp = await fetch(endpointURL, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': window.hdConfig._restToken,
                            },
                            body: JSON.stringify({id: postID})
                        });
                        const json = await resp.json();
                        if (json.success) {
                            if (dateEl) dateEl.textContent = json.date;
                            if (viewsEl) viewsEl.textContent = json.views;
                        }
                    } catch (err) {}
                }
            });
        </script>
		<?php endif;

		$content = ob_get_clean();
		if ( $content ) {
			echo \HD_Helper::JSMinify( $content, true );
		}
	}

	// -----------------------------------------------

    public function custom_css_action(): void {
	    $css = new \HD_CSS();

	    //-------------------------------------------------
	    // Breadcrumb
	    //-------------------------------------------------

	    $object = get_queried_object();

	    $breadcrumb_max     = \HD_Helper::getThemeMod( 'breadcrumb_max_height_setting', 0 );
	    $breadcrumb_min     = \HD_Helper::getThemeMod( 'breadcrumb_min_height_setting', 0 );
	    $breadcrumb_bgcolor = \HD_Helper::getThemeMod( 'breadcrumb_bgcolor_setting' );

	    if ( $breadcrumb_max > 0 || $breadcrumb_min > 0 || $breadcrumb_bgcolor ) {
		    $css->set_selector( '.section.section-breadcrumb' );
	    }

	    $breadcrumb_min && $css->add_property( 'min-height', $breadcrumb_min . 'px !important' );
	    $breadcrumb_max && $css->add_property( 'max-height', $breadcrumb_max . 'px !important' );
	    $breadcrumb_bgcolor && $css->add_property( 'background-color', $breadcrumb_bgcolor . ' !important' );

	    $breadcrumb_title_color = \HD_Helper::getField( 'breadcrumb_title_color', $object ) ?: \HD_Helper::getThemeMod( 'breadcrumb_color_setting' );

	    if ( $breadcrumb_title_color ) {
		    $css->set_selector( '.section.section-breadcrumb .breadcrumb-title' )
		        ->add_property( 'color', $breadcrumb_title_color . ' !important' );
	    }

	    $css_output = $css->css_output();
	    if ( $css_output ) {
		    \HD_Asset::inlineStyle( 'index-css', $css_output );
	    }

	    //ob_start();
	    //...
	    //$inline_css = ob_get_clean();
	    //if ( $inline_css ) {
	    //$inline_css = \HD_Helper::CSSMinify( $inline_css, true );
	    //\HD_Asset::inlineStyle( 'index-css', $inline_css );
	    //}
    }

	// -----------------------------------------------
}
