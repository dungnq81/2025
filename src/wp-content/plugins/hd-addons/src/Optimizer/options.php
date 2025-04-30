<?php

\defined( 'ABSPATH' ) || exit;

$optimizer_options = \Addons\Helper::getOption( 'optimizer__options' );

$minify_html = $optimizer_options['minify_html'] ?? 0;

$dns_prefetch = $optimizer_options['dns_prefetch'] ?? [];
$dns_prefetch = implode( PHP_EOL, $dns_prefetch );

$font_preload      = $optimizer_options['font_preload'] ?? [];
$font_preload      = implode( PHP_EOL, $font_preload );

$lazyload         = $optimizer_options['lazyload'] ?? 0;
$lazyload_mobile  = $optimizer_options['lazyload_mobile'] ?? 0;
$lazyload_exclude = $optimizer_options['lazyload_exclude'] ?? [];
$lazyload_exclude = implode( PHP_EOL, $lazyload_exclude );

?>
<div class="container flex flex-x flex-gap sm-up-1 md-up-2">
    <input type="hidden" name="optimizer-hidden" value="1">
    <div class="cell section section-checkbox">
        <label class="heading !block" for="minify_html"><?php _e( 'Minify HTML', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="minify_html" id="minify_html" <?php checked( $minify_html, 1 ); ?> value="1">
            </div>
            <div class="explain"><?php _e( 'Check to activate', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
        <div class="desc"><?php _e( 'Remove unnecessary characters from your HTML output to reduce data size and improve your site\'s loading speed. Not compatible with LadiPage pages..', ADDONS_TEXTDOMAIN ); ?></div>
    </div>
    <div class="cell section section-textarea !sm-12">
        <label class="heading" for="dns_prefetch"><?php _e( 'DNS Pre-fetch', ADDONS_TEXTDOMAIN ) ?></label>
        <div class="option">
            <div class="controls">
                <textarea class="textarea" name="dns_prefetch" id="dns_prefetch" rows="4"><?php echo $dns_prefetch; ?></textarea>
            </div>
        </div>
        <div class="desc"><?php _e( 'Enabling DNS pre-fetch for a domain will resolve it before resources are requested from it, resulting in faster loading of those resources.', ADDONS_TEXTDOMAIN ); ?></div>
    </div>
    <div class="cell section section-textarea !sm-12">
        <label class="heading inline-heading" for="font_preload"><?php _e( 'Fonts Preloading', ADDONS_TEXTDOMAIN ) ?></label>
        <div
            class="desc"><?php _e( 'Preload the fonts you\'re using to improve rendering speed and enhance site performance. <b>Use the full URL to the font.</b>', ADDONS_TEXTDOMAIN ) ?></div>
        <div class="option">
            <div class="controls">
                <textarea class="textarea" name="font_preload" id="font_preload" rows="4"><?php echo $font_preload; ?></textarea>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/LazyLoad/options.php'; ?>

</div>
