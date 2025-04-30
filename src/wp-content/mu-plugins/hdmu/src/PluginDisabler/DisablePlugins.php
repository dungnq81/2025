<?php

namespace MU\PluginDisabler;

/**
 * Class DisablePlugins
 *
 * Disables plugins by filename
 */
class DisablePlugins {
	public static $instance;
	private array $disabled = [];

	// ------------------------------------------------------

	/**
	 * Sets up the option filter and optionally handles an array of plugins to disable
	 *
	 * @param array|null $disables Optional array of plugin filenames to disable
	 */
	public function __construct( ?array $disables = null ) {
		/**
		 * Handle what was passed in
		 */
		if ( is_array( $disables ) ) {
			foreach ( $disables as $disable ) {
				$this->disable( $disable );
			}
		}

		/**
		 * Add the filters
		 */
		add_filter( 'option_active_plugins', [ $this, 'doDisabling' ] );
		add_filter( 'site_option_active_sitewide_plugins', [ $this, 'doNetworkDisabling' ] );

		/**
		 * Allow other plugins to access this instance
		 */
		self::$instance = $this;
	}

	// ------------------------------------------------------

	/**
	 * Adds a filename to the list of plugins to disable
	 */
	public function disable( $file ): void {
		$this->disabled[] = $file;
	}

	// ------------------------------------------------------

	/**
	 * Hooks in to the option_active_plugins filter and does the disabling
	 *
	 * @param array|null $plugins WP-provided list of plugin filenames
	 *
	 * @return array The filtered array of plugin filenames
	 */
	public function doDisabling( ?array $plugins ): array {
		if ( count( $this->disabled ) ) {
			foreach ( $this->disabled as $plugin ) {
				$key = array_search( $plugin, $plugins, false );
				if ( false !== $key ) {
					unset( $plugins[ $key ] );
				}
			}
		}

		return $plugins;
	}

	// ------------------------------------------------------

	/**
	 * Hooks in to the site_option_active_sitewide_plugins filter and does the disabling
	 *
	 * @param array|null $plugins
	 *
	 * @return array
	 */
	public function doNetworkDisabling( ?array $plugins ): array {
		if ( count( $this->disabled ) ) {
			foreach ( $this->disabled as $plugin ) {
				if ( isset( $plugins[ $plugin ] ) ) {
					unset( $plugins[ $plugin ] );
				}
			}
		}

		return $plugins;
	}
}
