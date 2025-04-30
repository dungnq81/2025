<?php

defined( 'ABSPATH' ) || exit;

$upload_max_filesize    = ( ini_get( 'upload_max_filesize' ) !== false ) ? ini_get( 'upload_max_filesize' ) : '2M';
$upload_max_filesize_MB = \Addons\Helper::convertToMB( $upload_max_filesize );

$file_options      = \Addons\Helper::getOption( 'file__options' );
$upload_size_limit = $file_options['upload_size_limit'] ?? '';
$svgs              = $file_options['svgs'] ?? 'disable';

$svg_options = [
	'disable'      => esc_html__( 'Disable SVG images', ADDONS_TEXTDOMAIN ),
	'sanitized'    => esc_html__( 'Sanitized SVG images', ADDONS_TEXTDOMAIN ),
	'unrestricted' => esc_html__( 'Unrestricted SVG images', ADDONS_TEXTDOMAIN ),
];

?>
<div class="container flex flex-x flex-gap sm-up-1">
    <input type="hidden" name="file-hidden" value="1">
    <div class="cell section section-text">
        <label class="heading" for="upload_size_limit"><?php _e( 'Maximum upload file size', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="desc">
            The "Maximum Upload File Size" setting allows administrators to set a limit on the size of uploaded files, measured in MB.
            <cite><strong>Maximum <?= $upload_max_filesize_MB . ' MB' ?></strong></cite>
        </div>
        <div class="option">
            <div class="controls">
                <input value="<?= esc_attr( $upload_size_limit ) ?>" class="input !w-250" type="number" min="1" step="1" max="<?= $upload_max_filesize_MB ?>" id="upload_size_limit" name="upload_size_limit">
            </div>
        </div>
    </div>
    <div class="cell section section-radio">
        <span class="heading !block"><?php _e( 'SVG Images', ADDONS_TEXTDOMAIN ); ?></span>
        <div class="option inline-option">
            <div class="controls">
                <div class="inline-group">
					<?php foreach ( $svg_options as $key => $opt ) : ?>
                    <label>
                        <input type="radio" name="svgs" class="radio" id="svgs-<?= $key ?>" value="<?= $key ?>" <?php checked( $svgs, $key ); ?> />
                        <span><?= $opt ?></span>
                    </label>
					<?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="desc"><b>Security notice:</b> Every SVG image is an XML file that can contain <b>malicious code</b>, potentially leading to <b>XSS</b> or <b>injection attacks</b>.</div>
    </div>
</div>
