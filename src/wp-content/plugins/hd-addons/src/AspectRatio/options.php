<?php

\defined( 'ABSPATH' ) || exit;

$aspect_ratio_settings = \Addons\Helper::filterSettingOptions( 'aspect_ratio', [] );
$no_data_message       = __( 'No data available or configuration for this feature has not been set.', ADDONS_TEXTDOMAIN );

?>
<div class="container flex flex-x flex-gap sm-up-1 md-up-2">
    <input type="hidden" name="aspect-ratio-hidden" value="1">
	<?php
    if ( empty( $aspect_ratio_settings['post_type_term'] ) ) {
        echo '<h3 class="cell">' . $no_data_message . '</h3>';
        echo '</div>';
        return;
    }

	foreach ( $aspect_ratio_settings['post_type_term'] as $ar ) :
		if ( ! $ar ) {
			break;
		}

		$aspect_ratio_options = \Addons\Helper::getOption( 'aspect_ratio__options' );
		$width                = $aspect_ratio_options[ 'ar-' . $ar . '-width' ] ?? '';
		$height               = $aspect_ratio_options[ 'ar-' . $ar . '-height' ] ?? '';

		$title = get_post_type_object( $ar )?->labels?->singular_name;
		$title = ! empty( $title ) ? $title : get_taxonomy( $ar )?->labels?->singular_name;
	?>
    <div class="section section-text cell">
        <span class="heading"><?php echo $title . ' ( ' . $ar . ' )' ?></span>
        <div class="option inline-option">
            <div class="controls">
                <div class="inline-group">
                    <label>
                        <span>Width:</span>
                        <input class="input" name="<?= $ar ?>-width" type="number" pattern="\d*" size="3" min="0" value="<?php echo esc_attr( $width ); ?>">
                    </label>
                    <span>x</span>
                    <label>
                        <span>Height:</span>
                        <input class="input" name="<?= $ar ?>-height" type="number" pattern="\d*" size="3" min="0" value="<?php echo esc_attr( $height ); ?>">
                    </label>
                </div>
            </div>
        </div>
        <div class="desc"><?php echo mb_ucfirst( $ar ) ?> images will be viewed at a custom aspect ratio.</div>
    </div>
	<?php endforeach; ?>
</div>
