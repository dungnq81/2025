<?php

namespace Addons\GlobalSetting;

use Addons\BaseSlug\BaseSlug;
use Addons\CustomSorting\CustomSorting;
use Addons\Helper;
use Addons\Security\Readme;

\defined( 'ABSPATH' ) || exit;

final class GlobalSetting {

	// --------------------------------------------------

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_filter( 'menu_order', [ $this, 'options_reorder_submenu' ] );
		add_filter( 'custom_menu_order', '__return_true' );

		add_action( 'admin_init', [ $this, 'add_addon_capability_to_roles' ] );
		add_action( 'wp_ajax_submit_settings', [ $this, 'ajax_submit_settings' ] );
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function admin_menu(): void {
		// Addons menu
		add_menu_page(
			__( 'Addons Settings', ADDONS_TEXTDOMAIN ),
			__( 'Addons', ADDONS_TEXTDOMAIN ),
			'addon_manage_options',
			'addon-settings',
			[ $this, '_addon_menu_callback' ],
			'dashicons-admin-settings',
			80
		);

		// Advanced submenu
		add_submenu_page(
			'addon-settings',
			__( 'Advanced', ADDONS_TEXTDOMAIN ),
			__( 'Advanced', ADDONS_TEXTDOMAIN ),
			'addon_manage_options',
			'customize.php'
		);

		// Server Info submenu
		if ( Helper::version() ) {
			add_submenu_page(
				'addon-settings',
				__( 'Server Info', ADDONS_TEXTDOMAIN ),
				__( 'Server Info', ADDONS_TEXTDOMAIN ),
				'addon_manage_options',
				'server-info',
				[ $this, '_addon_server_info_callback' ]
			);
		}
	}

	// --------------------------------------------------

	/**
	 * @param array $menu_order
	 *
	 * @return array
	 */
	public function options_reorder_submenu( array $menu_order ): array {
		global $submenu;

		if ( empty( $submenu['addon-settings'] ) ) {
			return $menu_order;
		}

		// Change menu title
		$submenu['addon-settings'][0][0] = __( 'Settings', ADDONS_TEXTDOMAIN );

		return $menu_order;
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function add_addon_capability_to_roles(): void {
		$capability_to_roles = apply_filters( 'addon_manage_roles', [ 'administrator', 'editor' ] );
		if ( $capability_to_roles ) {

			foreach ( $capability_to_roles as $role_name ) {
				$role = get_role( $role_name );
				$role?->add_cap( 'addon_manage_options' );
			}
		}
	}

	// --------------------------------------------------

	/**
	 * @return false|void
	 */
	public function ajax_submit_settings() {
		if ( ! wp_doing_ajax() ) {
			return false;
		}

		check_ajax_referer( '_wpnonce_settings_form_' . get_current_user_id() );
		$data = $_POST['_data'] ?? [];

		/** ---------------------------------------- */

		/** Global Setting */
		$menu_options           = Helper::loadYaml( ADDONS_PATH . 'config.yaml' );
		$global_setting_options = [];
		foreach ( $menu_options as $slug => $value ) {
			if ( ! empty( $data[ $slug ] ) ) {
				$global_setting_options[ $slug ] = 1;
			}
		}

		if ( $global_setting_options ) {
			Helper::updateOption( 'global_setting__options', $global_setting_options );
		} else {
			Helper::removeOption( 'global_setting__options' );
		}

		/** ---------------------------------------- */

		/** Aspect Ratio */
        if ( isset( $data['aspect-ratio-hidden'] ) ) {
	        $aspect_ratio_options  = [];
	        $aspect_ratio_settings = Helper::filterSettingOptions( 'aspect_ratio', [] );

	        foreach ( $aspect_ratio_settings['post_type_term'] ?? [] as $ar ) {
		        if ( isset( $data[ $ar . '-width' ], $data[ $ar . '-height' ] ) ) {
			        $aspect_ratio_options[ 'ar-' . $ar . '-width' ]  = sanitize_text_field( $data[ $ar . '-width' ] );
			        $aspect_ratio_options[ 'ar-' . $ar . '-height' ] = sanitize_text_field( $data[ $ar . '-height' ] );
		        }
	        }

	        if ( $aspect_ratio_options ) {
		        Helper::updateOption( 'aspect_ratio__options', $aspect_ratio_options );
	        } else {
		        Helper::removeOption( 'aspect_ratio__options' );
	        }
        }

		/** ---------------------------------------- */

		/** Editor */
		if ( isset( $data['editor-hidden'] ) ) {
			$editor_options = [];
			$arrs           = [
				'use_widgets_block_editor_off',
				'gutenberg_use_widgets_block_editor_off',
				'use_block_editor_for_post_type_off',
				'block_style_off'
			];

			foreach ( $arrs as $value ) {
				if ( isset( $data[ $value ] ) ) {
					$editor_options[ $value ] = sanitize_text_field( $data[ $value ] );
				}
			}

			if ( $editor_options ) {
				Helper::updateOption( 'editor__options', $editor_options );
			} else {
				Helper::removeOption( 'editor__options' );
			}
		}

		/** ---------------------------------------- */

        /** Optimizer */
		if ( isset( $data['optimizer-hidden'] ) ) {

			$optimizer_options = [];
			$dns_prefetch      = ! empty( $data['dns_prefetch'] ) ? \Addons\Helper::explodeMulti( [ ',', ' ', PHP_EOL, ], $data['dns_prefetch'] ) : [];
			$font_preload      = ! empty( $data['font_preload'] ) ? \Addons\Helper::explodeMulti( [ ',', ' ', PHP_EOL, ], $data['font_preload'] ) : [];
			$lazyload_exclude  = ! empty( $data['lazyload_exclude'] ) ? \Addons\Helper::explodeMulti( [ ',', ' ', PHP_EOL, ], $data['lazyload_exclude'] ) : [];

			$dns_prefetch     = array_map( 'sanitize_url', $dns_prefetch );
			$font_preload     = array_map( 'sanitize_url', $font_preload );
			$lazyload_exclude = array_map( 'esc_textarea', $lazyload_exclude );

			$arrs = [
				'minify_html'       => ! empty( $data['minify_html'] ) ? sanitize_text_field( $data['minify_html'] ) : '',
				'dns_prefetch'      => $dns_prefetch,
				//'font_optimize'     => ! empty( $data['font_optimize'] ) ? sanitize_text_field( $data['font_optimize'] ) : 0,
				//'font_combined_css' => ! empty( $data['font_combined_css'] ) ? sanitize_text_field( $data['font_combined_css'] ) : 0,
				'font_preload'      => $font_preload,
				'lazyload'          => ! empty( $data['lazyload'] ) ? sanitize_text_field( $data['lazyload'] ) : 0,
				//'lazyload_mobile'   => ! empty( $data['lazyload_mobile'] ) ? sanitize_text_field( $data['lazyload_mobile'] ) : 0,
				'lazyload_mobile'   => ! empty( $data['lazyload'] ) ? sanitize_text_field( $data['lazyload'] ) : 0,
				'lazyload_exclude'  => $lazyload_exclude,
			];

			foreach ( $arrs as $key => $value ) {
				if ( isset( $data[ $key ] ) ) {
					$optimizer_options[ $key ] = $value;
				}
			}

			if ( $optimizer_options ) {
				Helper::updateOption( 'optimizer__options', $optimizer_options );
			} else {
				Helper::removeOption( 'optimizer__options' );
			}
		}

		/** ---------------------------------------- */

        /** Security */
		if ( isset( $data['security-hidden'] ) ) {
			$security_options = [];
			$arrs             = [
				'comments_off',
				'xmlrpc_off',
				'hide_wp_version',
				'wp_links_opml_off',
				'rss_feed_off',
				'remove_readme',
			];

			foreach ( $arrs as $value ) {
				if ( isset( $data[ $value ] ) ) {
					$security_options[ $value ] = sanitize_text_field( $data[ $value ] );
				}
			}

			if ( $security_options ) {
				Helper::updateOption( 'security__options', $security_options );
			} else {
				Helper::removeOption( 'security__options' );
			}

			// Remove readme.html
			isset( $security_options['remove_readme'] ) && ( new Readme() )->delete_readme();
		}

		/** ---------------------------------------- */

		/** Login Security */
		if ( isset( $data['login-security-hidden'] ) ) {
			$login_security_options = [];
			$arrs                   = [
				'custom_login_uri',
				'login_ips_access',
				'disable_ips_access',
				'limit_login_attempts',
				'illegal_users'
			];

			foreach ( $arrs as $value ) {
				if ( ! empty( $data[ $value ] ) ) {
					$login_security_options[ $value ] = sanitize_text_field( $data[ $value ] );
				}
			}

			if ( $login_security_options ) {
				Helper::updateOption( 'login_security__options', $login_security_options );
			} else {
				Helper::removeOption( 'login_security__options' );
			}
		}

		/** ---------------------------------------- */

		/** Social Link */
		if ( isset( $data['social-link-hidden'] ) ) {
			$social_link_options  = [];
			$social_follows_links = Helper::filterSettingOptions( 'social_follows_links', [] );

			foreach ( $social_follows_links as $i => $item ) {
				$url = ! empty( $data[ $i . '-url' ] ) ? sanitize_url( $data[ $i . '-url' ] ) : '';
				if ( $url ) {
					$social_link_options[ $i ] = [
						'url' => $url
					];
				}
			}

			if ( $social_link_options ) {
				Helper::updateOption( 'social_link__options', $social_link_options );
			} else {
				Helper::removeOption( 'social_link__options' );
			}
		}

		/** ---------------------------------------- */

        /** Contact Link */
		if ( isset( $data['contact-link-hidden'] ) ) {
			$contact_link_options = [];
			$contact_links        = Helper::filterSettingOptions( 'contact_links', [] );

			foreach ( $contact_links as $i => $link ) {
                $color = $i . '-color';
				$value = $i . '-value';

                if ( isset( $data[ $color ] ) ) {
	                $contact_link_options[ $i ]['color'] = sanitize_text_field( $data[ $color ] );
                }

				if ( isset( $data[ $value ] ) ) {
					$contact_link_options[ $i ]['value'] = sanitize_text_field( $data[ $value ] );
				}
			}

			if ( $contact_link_options ) {
				Helper::updateOption( 'contact_link__options', $contact_link_options );
			} else {
				Helper::removeOption( 'contact_link__options' );
			}
		}

		/** ---------------------------------------- */

		/** File */
		if ( isset( $data['file-hidden'] ) ) {
			$file_options = [];
			$arrs         = [
				'upload_size_limit',
				'svgs',
			];

			foreach ( $arrs as $value ) {
				if ( ! empty( $data[ $value ] ) ) {
					$file_options[ $value ] = sanitize_text_field( $data[ $value ] );
				}
			}

			if ( $file_options ) {
				Helper::updateOption( 'file__options', $file_options );
			} else {
				Helper::removeOption( 'file__options' );
			}
		}

		/** ---------------------------------------- */

		/** Remove Base Slug */
		if ( isset( $data['base-slug-hidden'] ) ) {
			$custom_base_slug_options = [];
			$base_slug_reset          = ! empty( $data['base_slug_reset'] ) ? sanitize_text_field( $data['base_slug_reset'] ) : '';

			if ( empty( $base_slug_reset ) ) {
				$arrs = [
					'base_slug_post_type',
					'base_slug_taxonomy',
				];

				foreach ( $arrs as $value ) {
					if ( ! empty( $data[ $value ] ) ) {
						$custom_base_slug_options[ $value ] = array_map( 'sanitize_text_field', $data[ $value ] );
					}
				}
			}

			if ( $custom_base_slug_options ) {
				Helper::updateOption( 'base_slug__options', $custom_base_slug_options );
				( new BaseSlug() )->flush_rules();
			} else {
				Helper::removeOption( 'base_slug__options' );
				( new BaseSlug() )->reset_all();
			}
		}

		/** ---------------------------------------- */

		/** Custom Email To */
		if ( isset( $data['custom-email-to-hidden'] ) ) {
			$email_options = [];
			$custom_emails = Helper::filterSettingOptions( 'custom_emails', [] );

			if ( $custom_emails ) {
				foreach ( $custom_emails as $i => $ar ) {
					$email = ! empty( $data[ $i . '_email' ] ) ? $data[ $i . '_email' ] : '';
					$email = is_array( $email ) ? array_map( 'sanitize_text_field', $email ) : sanitize_text_field( $email );

					$email_options[ $i ] = $email;
				}
			}

			if ( $email_options ) {
				Helper::updateOption( 'custom_email_to__options', $email_options );
			} else {
				Helper::removeOption( 'custom_email_to__options' );
			}
		}

		/** ---------------------------------------- */

		/** Custom Sorting */
		if ( isset( $data['custom-sorting-hidden'] ) ) {
			$custom_order_options = [];
			$order_reset          = ! empty( $data['order_reset'] ) ? sanitize_text_field( $data['order_reset'] ) : '';

			if ( empty( $order_reset ) ) {
				$arrs = [
					'order_post_type',
					'order_taxonomy',
				];

				foreach ( $arrs as $value ) {
					if ( ! empty( $data[ $value ] ) ) {
						$custom_order_options[ $value ] = array_map( 'sanitize_text_field', $data[ $value ] );
					}
				}
			}

			if ( $custom_order_options ) {
				Helper::updateOption( 'custom_sorting__options', $custom_order_options );
				( new CustomSorting() )->update_options();
			} else {
				Helper::removeOption( 'custom_sorting__options' );
				( new CustomSorting() )->reset_all();
			}
		}

		/** ---------------------------------------- */

		/** ReCaptcha */
		if ( isset( $data['recaptcha-hidden'] ) ) {
			$recaptcha_options = [];
			$arrs              = [
				'recaptcha_v2_site_key',
				'recaptcha_v2_secret_key',
				'recaptcha_v3_site_key',
				'recaptcha_v3_secret_key',
				'recaptcha_v3_score',
				'recaptcha_global',
				'recaptcha_allowlist_ips'
			];

			foreach ( $arrs as $value ) {
				if ( ! empty( $data[ $value ] ) ) {
					$recaptcha_options[ $value ] = sanitize_text_field( $data[ $value ] );
				}
			}

			if ( $recaptcha_options ) {
				Helper::updateOption( 'recaptcha__options', $recaptcha_options );
			} else {
				Helper::removeOption( 'recaptcha__options' );
			}
		}

		/** ---------------------------------------- */

		/** WooCommerce */
		if ( isset( $data['woocommerce-hidden'] ) && Helper::checkPluginActive( 'woocommerce/woocommerce.php' ) ) {
			$arrs = [
				'woocommerce_jsonld',
				'woocommerce_default_css'
			];

			$woocommerce_options = [];
			foreach ( $arrs as $value ) {
				if ( isset( $data[ $value ] ) ) {
					$woocommerce_options[ $value ] = sanitize_text_field( $data[ $value ] );
				}
			}

			if ( $woocommerce_options ) {
				Helper::updateOption( 'woocommerce__options', $woocommerce_options );
			} else {
				Helper::removeOption( 'woocommerce__options' );
			}
		}

		/** ---------------------------------------- */

		/** Custom JS */
		if ( isset( $data['custom-js-hidden'] ) ) {

			$html_header      = $data['html_header'] ?? '';
			$html_footer      = $data['html_footer'] ?? '';
			$html_body_top    = $data['html_body_top'] ?? '';
			$html_body_bottom = $data['html_body_bottom'] ?? '';

			Helper::updateCustomPostOption( $html_header, 'html_header', 'text/html', true );
			Helper::updateCustomPostOption( $html_footer, 'html_footer', 'text/html', true );
			Helper::updateCustomPostOption( $html_body_top, 'html_body_top', 'text/html', true );
			Helper::updateCustomPostOption( $html_body_bottom, 'html_body_bottom', 'text/html', true );
		}

		/** ---------------------------------------- */

		/** Custom CSS */
        if ( isset( $data['html_custom_css'] ) ) {
            Helper::updateCustomPostOption( $data['html_custom_css'], 'addon_css', 'text/css', false );
        }

		/** ---------------------------------------- */

		Helper::clearAllCache();
		Helper::messageSuccess( __( 'Your settings have been saved.', ADDONS_TEXTDOMAIN ), true );

		exit();
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function _addon_menu_callback(): void {
		?>
        <div class="wrap" id="_container">
            <form role="form" id="_settings_form" method="post" accept-charset="UTF-8" enctype="multipart/form-data">

				<?php wp_nonce_field( '_wpnonce_settings_form_' . get_current_user_id() ); ?>

                <div id="main" class="filter-tabs clearfix">

					<?php include __DIR__ . '/options_menu.php'; ?>
					<?php include __DIR__ . '/options_content.php'; ?>

                </div>
            </form>
        </div>
		<?php
	}

	// --------------------------------------------------

	/**
	 * @return void
	 */
	public function _addon_server_info_callback(): void {
		global $wpdb;

		?>
        <div class="wrap">
            <div id="main">
                <h2 class="hide-text"></h2>
                <div class="server-info-body">
                    <h2><?php echo __( 'Server info', ADDONS_TEXTDOMAIN ) ?></h2>
                    <p class="desc"><?php echo __( 'System configuration information', ADDONS_TEXTDOMAIN ) ?></p>
                    <div class="server-info-inner code">
                        <ul>
                            <li><?php echo sprintf( '<span>Platform:</span> %s', php_uname() ); ?></li>
                            <li><?php echo sprintf( '<span>Server:</span> %s', $_SERVER['SERVER_SOFTWARE'] ); ?></li>
                            <li><?php echo sprintf( '<span>Server IP:</span> %s', Helper::serverIpAddress() ); ?></li>
							<?php

							$cpuInfo = ( is_readable( '/proc/cpuinfo' ) ) ? file_get_contents( '/proc/cpuinfo' ) : 'N/A';
							preg_match( '/^model name\s*:\s*(.+)$/m', $cpuInfo, $matches );
							$cpu_model = isset( $matches[1] ) ? trim( $matches[1] ) : 'N/A';

							?>
                            <li><?php echo sprintf( '<span>CPU Info:</span> %s', $cpu_model ); ?></li>

                            <li><?php echo sprintf( '<span>Memory Limit:</span> %s', ini_get( 'memory_limit' ) ); ?></li>
                            <li><?php echo sprintf( '<span>PHP version:</span> %s', PHP_VERSION ); ?></li>
                            <li><?php echo sprintf( '<span>PHP Max Upload Size:</span> %s', ini_get( 'upload_max_filesize' ) ); ?></li>
                            <li><?php echo sprintf( '<span>MySql version:</span> %s', $wpdb->db_version() ); ?></li>

                            <li><?php echo sprintf( '<span>WordPress version:</span> %s', get_bloginfo( 'version' ) ); ?></li>
                            <li><?php echo sprintf( '<span>WordPress multisite:</span> %s', ( is_multisite() ? 'Yes' : 'No' ) ); ?></li>
							<?php

							$openssl_status = __( 'Available', ADDONS_TEXTDOMAIN );
							$openssl_text   = '';
							if ( ! defined( 'OPENSSL_ALGO_SHA1' ) && ! extension_loaded( 'openssl' ) ) {
								$openssl_status = __( 'Not available', ADDONS_TEXTDOMAIN );
								$openssl_text   = __( ' (openssl extension is required in order to use any kind of encryption like TLS or SSL)', ADDONS_TEXTDOMAIN );
							}
							?>
                            <li><?php echo sprintf( '<span>openssl:</span> %s%s', $openssl_status, $openssl_text ); ?></li>
                            <li><?php echo sprintf( '<span>allow_url_fopen:</span> %s', ( ini_get( 'allow_url_fopen' ) ? __( 'Enabled', ADDONS_TEXTDOMAIN ) : __( 'Disabled', ADDONS_TEXTDOMAIN ) ) ); ?></li>
							<?php

							$stream_socket_client_status = __( 'Not Available', ADDONS_TEXTDOMAIN );
							$fsockopen_status            = __( 'Not Available', ADDONS_TEXTDOMAIN );
							$socket_enabled              = false;

							if ( function_exists( 'stream_socket_client' ) ) {
								$stream_socket_client_status = __( 'Available', ADDONS_TEXTDOMAIN );
								$socket_enabled              = true;
							}
							if ( function_exists( 'fsockopen' ) ) {
								$fsockopen_status = __( 'Available', ADDONS_TEXTDOMAIN );
								$socket_enabled   = true;
							}

							$socket_text = '';
							if ( ! $socket_enabled ) {
								$socket_text = __( ' (In order to make a SMTP connection your server needs to have either stream_socket_client or fsockopen)', ADDONS_TEXTDOMAIN );
							}

							?>
                            <li><?php echo sprintf( '<span>stream_socket_client:</span> %s', $stream_socket_client_status ); ?></li>
                            <li><?php echo sprintf( '<span>fsockopen:</span> %s%s', $fsockopen_status, $socket_text ); ?></li>
							<?php

							if ( $agent = $_SERVER['HTTP_USER_AGENT'] ?? null ) : ?>
                                <li><?php echo sprintf( '<span>User agent:</span> %s', $agent ); ?></li>
							<?php endif;

							?>
                            <li><?php echo sprintf( '<span>IP:</span> %s', Helper::ipAddress() ); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	// --------------------------------------------------
}
