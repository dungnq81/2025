<?php

namespace Addons\Optimizer\LazyLoad;

\defined( 'ABSPATH' ) || exit;

class LazyLoad_Images extends Abstract_LazyLoad {

	// -------------------------------------------------------------

	// Regex parts for checking content.
	public string $regexp = '/<img[\s\r\n]+[^>]*?>|<picture\b[^>]*?>.*?<source[\s\r\n]+[^>]*?>.*?<\/picture>/is';

	// Regex for already replaced items.
	public string $regex_replaced = "/(src=['\"]data:image|srcset=['\"]data-srcset)/is";

	// Replace patterns.
	public array $patterns = [
		'/(?<!noscript\>)(<img\b[^>]*?)(src)=["|\']((?!data).*?)["|\']/i',
		'/(?<!noscript\>)(<img\b[^>]*?)(srcset)=["|\'](.*?)["|\']/i',
		'/(?<!noscript\>)(<source\b[^>]*?)(srcset)=["|\'](.*?)["|\']/i',
	];

	// Replacements.
	public array $replacements = [
		'$1src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-$2="$3"',
		'$1data-$2="$3"',
		'$1srcset="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-$2="$3"',
	];

	// -------------------------------------------------------------

	/**
	 * @param $element
	 *
	 * @return string
	 */
	public function add_lazyload_class( $element ): string {
		return preg_replace( '/<img/i', '<img class="lazy"', $element );
	}
}
