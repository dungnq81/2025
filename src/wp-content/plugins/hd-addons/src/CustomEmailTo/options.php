<?php

\defined( 'ABSPATH' ) || exit;

$emails_options       = \Addons\Helper::getOption( 'custom_email_to__options' );
$filter_custom_emails = \Addons\Helper::filterSettingOptions( 'custom_emails', [] );

?>
<div class="container flex flex-x flex-gap sm-up-1 lg-up-2">
    <input type="hidden" name="custom-email-to-hidden" value="1"
    <?php
    if ( empty( $filter_custom_emails ) ) {
	    echo '<h3 class="cell">' . __( 'Not initialized yet', ADDONS_TEXTDOMAIN ) . '</h3>';
	    echo '</div>';
	    return;
    }

    foreach ( $filter_custom_emails as $key => $ar ) :
	    $emails_list = $emails_options[ $key ] ?? '';

	    if ( ! $ar ) {
		    break;
	    }
    ?>
    <div class="section section-text">
        <label class="heading" for="<?= esc_attr( $key ) ?>"><?php _e( $ar, ADDONS_TEXTDOMAIN ); ?></label>
        <div class="desc">The email addresses are separated by commas "comma".</div>
        <div class="option">
            <div class="controls">
                <select aria-multiselectable="true" multiple class="select select2-emails !w[100%]" name="<?= esc_attr( $key ) ?>_email" id="<?= esc_attr( $key ) ?>">
				    <?php
				    if ( $emails_list ) {
					    foreach ( (array) $emails_list as $email ) {
                        ?>
                        <option selected value="<?= esc_attr( $email ) ?>"><?= $email ?></option>
					    <?php }
				    } ?>
                </select>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
