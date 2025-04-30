<?php

\defined( 'ABSPATH' ) || exit;

$security_options  = \Addons\Helper::getOption( 'security__options' );
$comments_off      = $security_options['comments_off'] ?? false;
$xmlrpc_off        = $security_options['xmlrpc_off'] ?? false;
$hide_wp_version   = $security_options['hide_wp_version'] ?? false;
$wp_links_opml_off = $security_options['wp_links_opml_off'] ?? '';
$rss_feed_off      = $security_options['rss_feed_off'] ?? '';
$remove_readme     = $security_options['remove_readme'] ?? false;

?>
<div class="container flex flex-x flex-gap sm-up-1 md-up-2">
    <input type="hidden" name="security-hidden" value="1">
    <div class="cell section section-checkbox">
        <label class="heading" for="comments_off"><?php _e( 'Disable Comments', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="comments_off" id="comments_off" <?php checked( $comments_off, 1 ); ?>value="1">
            </div>
            <div class="explain"><?php _e( 'Disable Comments', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
        <div class="desc"><?php _e( 'WordPress comments foster user engagement by enabling feedback on posts, with administrators easily managing approvals or disabling them as needed.', ADDONS_TEXTDOMAIN ) ?></div>
    </div>
    <div class="cell section section-checkbox">
        <label class="heading" for="xmlrpc_off"><?php _e( 'Disable XMLRPC', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="xmlrpc_off" id="xmlrpc_off" <?php checked( $xmlrpc_off, 1 ); ?> value="1">
            </div>
            <div class="explain"><?php _e( 'Disable xmlrpc.php', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
        <div class="desc"><?php _e( 'XMLRPC was designed as a protocol enabling WordPress to communicate with third-party systems but recently it has been used in a number of exploits. Unless you specifically need to use it, we recommend that XMLRPC is always disabled.', ADDONS_TEXTDOMAIN ) ?></div>
    </div>
    <div class="cell section section-checkbox">
        <label class="heading" for="hide_wp_version"><?php _e( 'Hide WordPress Version', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="hide_wp_version" id="hide_wp_version" <?php checked( $hide_wp_version, 1 ); ?> value="1">
            </div>
            <div class="explain"><?php _e( 'Hide WP Version', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
        <div class="desc"><?php _e( 'Many attackers scan sites for vulnerable WordPress versions. By hiding the version from your site HTML, you avoid being marked by hackers for mass attacks.', ADDONS_TEXTDOMAIN ) ?></div>
    </div>
    <div class="cell section section-checkbox">
        <label class="heading" for="wp_links_opml_off"><?php _e( 'Disable wp-links-opml', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="wp_links_opml_off" id="wp_links_opml_off" <?php checked( $wp_links_opml_off, 1 ); ?> value="1">
            </div>
            <div class="explain"><?php _e( 'Disable wp-links-opml.php', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
        <div class="desc"><?php _e( 'The wp-links-opml.php file allows users to export their links to an OPML file. This file is rarely used and can be disabled to enhance the security of your WordPress site. Disabling it helps prevent unauthorized access and potential information leakage.', ADDONS_TEXTDOMAIN ) ?></div>
    </div>
    <div class="cell section section-checkbox">
        <label class="heading" for="rss_feed_off"><?php _e( 'Disable RSS and ATOM Feeds', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="rss_feed_off" id="rss_feed_off" <?php checked( $rss_feed_off, 1 ); ?> value="1">
            </div>
            <div class="explain"><?php _e( 'Disable RSS', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
        <div class="desc"><?php _e( 'RSS and ATOM feeds are often used to scrape your content and to perform a number of attacks against your site. Only use feeds if you have readers using your site via RSS readers.', ADDONS_TEXTDOMAIN ); ?></div>
    </div>
    <div class="cell section section-checkbox">
        <label class="heading" for="remove_readme"><?php _e( 'Delete the default readme.html', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="remove_readme" id="remove_readme" <?php checked( $remove_readme, 1 ); ?> value="1">
            </div>
            <div class="explain"><?php _e( 'Remove readme.html', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
        <div class="desc"><?php _e( 'WordPress comes with a readme.html file containing information about your website. The readme.html is often used by hackers to compile lists of potentially vulnerable sites which can be hacked or attacked.', ADDONS_TEXTDOMAIN ); ?></div>
    </div>
</div>
