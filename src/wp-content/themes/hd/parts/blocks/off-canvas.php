<?php
/**
 * Displays navigation mobile
 *
 * @author Gaudev
 */

\defined( 'ABSPATH' ) || die;

$txt_logo = \HD_Helper::getOption( 'blogname' );
$img_logo = \HD_Helper::getThemeMod( 'custom_logo' );

if ( ! $img_logo ) :
	$html = sprintf(
		'<a href="%1$s" class="mobile-logo-link" rel="home" aria-label="%2$s">%3$s</a>',
		\HD_Helper::home(),
		\HD_Helper::escAttr( $txt_logo ),
		$txt_logo
	);
else :
	$image = \HD_Helper::iconImageHTML( $img_logo, 'medium' );
	$html  = sprintf(
		'<a href="%1$s" class="mobile-logo-link" rel="home">%2$s</a>',
		\HD_Helper::home(),
		$image
	);
endif;

$position = \HD_Helper::getThemeMod( 'offcanvas_menu_setting' );
if ( ! in_array( $position, [ 'left', 'right', 'top', 'bottom' ], false ) ) {
	$position = 'left';
}

?>
<div class="off-canvas position-<?= $position ?>" id="offCanvasMenu" data-off-canvas data-content-scroll="false">
    <div class="menu-heading-outer">
        <button class="menu-lines" aria-label="Close" type="button" data-close>
            <span class="line line-1"></span>
            <span class="line line-2"></span>
        </button>
        <div class="title-bar-title"><?php echo $html; ?></div>
    </div>
    <div class="menu-outer">
		<?php

		echo \HD_Helper::doShortcode( 'inline_search' );
		echo \HD_Helper::doShortcode( 'vertical_menu' );
		?>
    </div>
</div>
