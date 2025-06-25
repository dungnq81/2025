<?php

\defined( 'ABSPATH' ) || die;

$current_user = wp_get_current_user();

$ACF = \HD_Helper::getFields( 'user_' . $current_user?->ID );

$display_name = ! empty( $ACF['author_alt_name'] ) ? $ACF['author_alt_name'] : $current_user?->display_name;
$bio_info     = ! empty( $ACF['author_alt_biographical_info'] ) ? $ACF['author_alt_biographical_info'] : get_user_meta( $current_user?->ID, 'description', true );
$avatar_url   = ! empty( $ACF['author_alt_profile_picture'] ) ? \HD_Helper::attachmentImageSrc( $ACF['author_alt_profile_picture'], 'thumbnail' ) : get_avatar_url( $current_user->ID, [ 'size' => 300 ] );

?>
<div class="author-profile">
    <div class="author-avatar">
        <span class="res ar-1-1 after-overlay">
            <img src="<?= $avatar_url ?>" alt="<?= 'alt: ' . esc_attr( $display_name ) ?>">
        </span>
    </div>
    <div class="author-info">
        <h2 class="author-name" <?= \HD_Helper::microdata( 'name' ) ?>><?= esc_html( $display_name ) ?></h2>
        <div class="author-bio"><?= $bio_info ?></div>
        <a class="author-link" href="<?php echo \HD_Helper::getUserLink( $current_user?->ID );?>" title="<?= esc_attr( $display_name ) ?>" <?= \HD_Helper::microdata( 'url' ) ?>>
            <?= sprintf( __( 'View all posts by %s', TEXT_DOMAIN ), esc_html( $display_name ) ) ?>
        </a>
    </div>
</div>
