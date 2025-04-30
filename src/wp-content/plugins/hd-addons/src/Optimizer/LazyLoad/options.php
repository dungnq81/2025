<?php

\defined( 'ABSPATH' ) || exit;

$lazyload         = $lazyload ?? 0;
$lazyload_mobile  = $lazyload_mobile ?? 0;
$lazyload_exclude = $lazyload_exclude ?? '';

?>
<div class="cell section section-checkbox !sm-12">
	<label class="heading" for="lazyload"><?php _e( 'Lazy-Load Media', ADDONS_TEXTDOMAIN ); ?></label>
	<div class="option">
		<div class="controls">
			<input type="checkbox" class="checkbox" name="lazyload" id="lazyload" <?php checked( $lazyload, 1 ); ?> value="1">
		</div>
		<div class="explain"><?php _e( 'Check to activate', ADDONS_TEXTDOMAIN ); ?></div>
	</div>
    <div class="desc"><?php _e( 'Speed up your web application by deferring the loading of below-the-fold images, animated SVGs, videos, and iframes until they enter the viewport.', ADDONS_TEXTDOMAIN ) ?></div>
</div>
<div class="section section-checkbox !hidden">
	<label class="heading" for="lazyload_mobile"><?php _e( 'Lazy-Load on Mobile', ADDONS_TEXTDOMAIN ); ?></label>
	<div class="option">
		<div class="controls">
			<input type="checkbox" class="checkbox" name="lazyload_mobile" id="lazyload_mobile" <?php checked( $lazyload_mobile, 1 ); ?> value="1" />
		</div>
		<div class="explain"><?php _e( 'Check to activate', ADDONS_TEXTDOMAIN ); ?></div>
	</div>
</div>
<div class="section section-textarea !sm-12">
	<label class="heading" for="lazyload_exclude"><?php _e( 'Excluded images or iframes', ADDONS_TEXTDOMAIN ) ?></label>
	<div class="option">
		<div class="controls">
			<textarea class="textarea" name="lazyload_exclude" id="lazyload_exclude" rows="4"><?php echo $lazyload_exclude; ?></textarea>
		</div>
	</div>
    <div class="desc">'The keywords include <b>filename</b>, <b>CSS classes</b> of images or <b>iframe</b> that will be excluded.</div>
</div>
