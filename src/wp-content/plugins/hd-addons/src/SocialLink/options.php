<?php

\defined( 'ABSPATH' ) || exit;

$social_options       = \Addons\Helper::getOption( 'social_link__options' );
$social_follows_links = \Addons\Helper::filterSettingOptions( 'social_follows_links', [] );

?>
<div class="container flex flex-x flex-gap sm-up-1 lg-up-2">
    <input type="hidden" name="social-link-hidden" value="1">
	<?php
	if ( empty( $social_follows_links ) ) {
		echo '<h3 class="cell">' . __( 'Not initialized yet', ADDONS_TEXTDOMAIN ) . '</h3>';
		echo '</div>';
		return;
	}

    foreach ( $social_follows_links as $key => $social ) :
        if ( empty( $social['name'] ) || empty( $social['icon'] ) ) {
            continue;
        }

        $name  = $social['name'];
        $icon  = $social['icon'];
        $url   = $social_options[ $key ]['url'] ?? $social['url'];
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
                <input class="input" type="url" id="<?= esc_attr( $key ) ?>" name="<?= esc_attr( $key ) ?>-url" value="<?= esc_attr( $url ) ?>" title="URL">
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
