<?php
// otp-login.php

\defined( 'ABSPATH' ) || exit;

if ( ! empty( $args['error'] ) ) : ?>
    <div id="login_error" class="notice notice-error"><p><strong>Error:</strong> <?= esc_html( $args['error'] ) ?></p></div>
<?php endif ?>

<form name="otp_validate_form" id="loginform" action="<?= esc_url( $args['action'] ) ?>" method="post">
	<?php if ( $args['interim_login'] ) : ?>
        <input type="hidden" name="interim-login" value="1" />
    <?php endif; ?>
	<?php if ( ! empty( $args['redirect_to'] ) ) : ?>
        <input type="hidden" name="redirect_to" value="<?= esc_url( $args['redirect_to'] ) ?>" />
	<?php endif; ?>
    <input type="hidden" name="uid" value="<?= $args['uid'] ?>">
    <?= \Addons\Helper::CSRFToken( 'otp_csrf_token' ) ?>
    <input type="hidden" name="rememberme" id="rememberme" value="0" />
    <p class="otp-prompt"><?php esc_html_e( 'Enter a recovery code.', ADDONS_TEXTDOMAIN ); ?></p>
    <p>
        <label for="authcode">Recovery Code:</label>
        <input required autofocus type="text" inputmode="numeric" name="authcode" id="authcode" class="input authcode" value="" size="20" pattern="[0-9]*" placeholder="xxxxxx" data-digits="6">
    </p>
    <?php submit_button( __( 'Submit', ADDONS_TEXTDOMAIN ) ); ?>
</form>
