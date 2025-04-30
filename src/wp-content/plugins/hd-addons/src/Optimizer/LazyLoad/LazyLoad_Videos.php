<?php

namespace Addons\Optimizer\LazyLoad;

\defined( 'ABSPATH' ) || exit;

class LazyLoad_Videos extends Abstract_LazyLoad {

	// -------------------------------------------------------------

	// Regex parts for checking content.
	public string $regexp = '/(?:<video[^>]*)(?:(?:\/>)|(?:>.*?<\/video>))/is';

	// Regex for already replaced items.
	public string $regex_replaced = "/class=['\"][\w\s]*(lazy)+[\w\s]*['\"]/is";

	// Search patterns.
	public array $patterns = [
		'/(<video[^>]+)(src)=["|\']((?!data).*?)["|\']/i',
	];

	// Replace patterns.
	public array $replacements = [
		'$1data-$2="$3"',
	];

	// -------------------------------------------------------------

	/**
	 * @param $element
	 *
	 * @return string
	 */
	public function add_lazyload_class( $element ): string {
		return preg_replace( '/<video/i', '<video class="lazy"', $element );
	}
}
