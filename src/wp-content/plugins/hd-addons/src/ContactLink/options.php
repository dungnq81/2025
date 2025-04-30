<?php

\defined( 'ABSPATH' ) || exit;

$contact_link_options = \Addons\Helper::getOption( 'contact_link__options' );
$contact_links        = \Addons\Helper::filterSettingOptions( 'contact_links', [] );

?>
<div class="container flex flex-x flex-gap sm-up-1 md-up-2">
    <input type="hidden" name="contact-link-hidden" value="1">
	<?php
	if ( empty( $contact_links ) ) {
		echo '<h3 class="cell">' . __( 'Not initialized yet', ADDONS_TEXTDOMAIN ) . '</h3>';
		echo '</div>';
		return;
	}

    foreach ( $contact_links as $key => $link ) :
        if ( empty( $link['name'] ) ) {
            continue;
        }

	    $name        = $link['name'];
	    $icon        = $link['icon'];
	    $color       = $contact_link_options[ $key ]['color'] ?? ( $link['color'] ?? '' );
	    $value       = $contact_link_options[ $key ]['value'] ?? $link['value'];
	    $placeholder = $link['placeholder'] ?? '';
	?>
    <div class="cell section section-text">
        <span class="heading"><?php _e( $name, ADDONS_TEXTDOMAIN ); ?></span>
        <div class="option">
            <div class="controls control-img">
                <label for="<?= esc_attr( $key ) ?>">
		            <?php
		            if ( \Addons\Helper::isUrl( $icon ) || str_starts_with( $icon, 'data:' ) ) {
			            echo '<img src="' . $icon . '" alt="' . esc_attr( $name ) . '">';
		            } elseif ( str_starts_with( $icon, '<svg' ) ) {
			            echo $icon;
		            } elseif ( is_string( $icon ) ) {
			            echo '<i class="' . $icon . '"></i>';
		            }
		            ?>
                </label>
                <?php if ( isset( $link['color'] ) ) : ?>
                <input class="addon-color-field" type="text" name="<?= esc_attr( $key ) ?>-color" value="<?php echo esc_attr( $color ); ?>" pattern="^#([A-Fa-f0-9]{6})$" title="Color must be in the format #RRGGBB" />
                <?php endif; ?>
                <input id="<?= esc_attr( $key ) ?>" class="input" type="text" name="<?= esc_attr( $key ) ?>-value" value="<?= esc_attr( $value ) ?>" title="Value" placeholder="<?= $placeholder ?>">
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
