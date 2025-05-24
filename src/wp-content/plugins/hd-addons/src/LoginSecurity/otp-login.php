<?php
// otp-login.php

\defined( 'ABSPATH' ) || exit;

dump($args);

if ( ! empty( $args['error'] ) ) : ?>
    <div id="login_error"><strong><?php echo esc_html( $args['error'] ); ?></strong><br/></div>
<?php endif ?>

<form name="otp_validate_form" id="loginform" action="<?php echo esc_url( $args['action'] ); ?>" method="post">
	<?php if ( $args['interim_login'] ) : ?>
        <input type="hidden" name="interim-login" value="1" />
    <?php endif; ?>
	<?php if ( ! empty( $args['redirect_to'] ) ) : ?>
        <input type="hidden" name="redirect_to" value="<?php echo esc_url( $args['redirect_to'] ); ?>" />
	<?php endif; ?>
</form>
