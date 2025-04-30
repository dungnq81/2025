<?php

namespace Addons\Editor;

\defined( 'ABSPATH' ) || exit;

/**
 * TinyMCE Plugin
 *
 * @author Gaudev
 */
class TinyMCE {
	// --------------------------------------------------

	public function __construct() {
		add_filter( 'mce_buttons', [ $this, 'mce_buttons' ] );
		add_filter( 'mce_external_plugins', [ $this, 'mce_external_plugins' ] );
	}

	// --------------------------------------------------

	/**
	 * @param $buttons
	 *
	 * @return mixed
	 */
	public function mce_buttons( $buttons ): mixed {
		$extra_buttons = [
			'table',
			'charmap',
			'backcolor',
			'superscript',
			'subscript',
			'codesample',
			'toc'
		];

		$insertions = [
			'italic'     => 'underline',
			'alignright' => 'alignjustify',
			'link'       => 'unlink',
		];

		foreach ( $extra_buttons as $btn ) {
			array_push( $buttons, 'separator', $btn );
		}

		foreach ( $insertions as $after => $button ) {
			$pos = array_search( $after, $buttons, true );
			if ( $pos !== false ) {
				array_splice( $buttons, $pos + 1, 0, [ 'separator', $button ] );
			} else {
				array_push( $buttons, 'separator', $button );
			}
		}

		return $buttons;
	}

	// --------------------------------------------------

	/**
	 * @param $plugins
	 *
	 * @return mixed
	 */
	public function mce_external_plugins( $plugins ): mixed {
		$plugin_files = [
			'table'      => 'table/plugin.min.js',
			'codesample' => 'codesample/plugin.min.js',
			'toc'        => 'toc/plugin.min.js',
			'wordcount'  => 'wordcount/plugin.min.js',
			'charcount'  => 'charcount/plugin.min.js',
		];

		foreach ( $plugin_files as $key => $file ) {
			$plugins[ $key ] = ADDONS_URL . "src/Editor/tinymce/{$file}";
		}

		return $plugins;
	}
}
