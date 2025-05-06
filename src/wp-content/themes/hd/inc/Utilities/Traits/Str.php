<?php

namespace HD\Utilities\Traits;

\defined( 'ABSPATH' ) || die;

trait Str {
	// --------------------------------------------------

	/**
	 * https://github.com/cofirazak/phpMissingFunctions
	 *
	 * Replicates PHP's ucfirst() function with multibyte support.
	 *
	 * @param string $str The string being converted.
	 * @param null|string $encoding Optional encoding parameter is the character encoding.
	 *                              If it is omitted, the internal character encoding value will be used.
	 *
	 * @return string The input string with the first character in uppercase.
	 */
	public static function mbUcFirst( string $str, string $encoding = null ): string {
		// Check for an empty string
		if ( $str === '' ) {
			return $str;
		}

		// Use the null coalescing operator to assign encoding
		$encoding = $encoding ?? mb_internal_encoding();

		return mb_strtoupper( mb_substr( $str, 0, 1, $encoding ), $encoding ) . mb_substr( $str, 1, null, $encoding );
	}

	// --------------------------------------------------

	/**
	 * Remove empty <p> tags from content.
	 *
	 * @param string $content The input content with <p> tags.
	 *
	 * @return string|null The content with empty <p> tags removed, or null if the input is not a string.
	 */
	public static function removeEmptyP( string $content ): ?string {
		return preg_replace( '/<p>(\s|&nbsp;)*<\/p>/', '', $content ) ?? $content;
	}

	// --------------------------------------------------

	/**
	 * Convert newlines to <p> tags in HTML content.
	 *
	 * @param string $html The input HTML content.
	 *
	 * @return string The HTML content wrapped in <p> tags, with empty <p> tags removed.
	 */
	public static function nl2P( string $html ): string {
		$html = trim( $html );
		if ( empty( $html ) ) {
			return '';
		}

		// Replace newlines with </p><p> and wrap in <p> tags
		$html = preg_replace( '/(\r?\n)+/', '</p><p>', $html );
		$html = '<p>' . $html . '</p>';

		// Remove any empty <p> tags
		return self::removeEmptyP( $html );
	}

	// --------------------------------------------------

	/**
	 * Convert <br> tags in HTML content to <p> tags.
	 *
	 * @param string $html The input HTML content.
	 *
	 * @return string The HTML content wrapped in <p> tags, with empty <p> tags removed.
	 */
	public static function br2P( string $html ): string {
		$html = trim( $html );
		if ( empty( $html ) ) {
			return '';
		}

		// Replace <br> tags with </p><p> and wrap in <p> tags
		$html = preg_replace( '/(<br\s*\/?>\s*)+/', "</p>\n<p>", $html );
		$html = '<p>' . $html . '</p>';

		return self::removeEmptyP( $html );
	}

	// --------------------------------------------------

	/**
	 * Remove inline JavaScript and CSS from the given HTML content.
	 *
	 * @param string $content The HTML content to clean.
	 *
	 * @return string The cleaned HTML content without inline JavaScript and CSS.
	 */
	public static function removeInlineJsCss( string $content ): string {
		if ( empty( $content ) ) {
			return '';
		}

		return preg_replace(
			       [
				       '/<script\b[^>]*>(.*?)<\/script>/is',
				       '/\s*on\w+="[^"]*"/i',
				       "/\s*on\w+='[^']*'/i",
				       '/<style\b[^>]*>(.*?)<\/style>/is',
				       '/\s*style=["\'][^"\']*["\']/i',
			       ],
			       '',
			       $content
		       ) ?? $content;
	}

	// --------------------------------------------------

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public static function camelCase( string $string ): string {
		$string = ucwords( str_replace( [ '-', '_' ], ' ', trim( $string ) ) );

		return str_replace( ' ', '', $string );
	}

	// --------------------------------------------------

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public static function dashCase( string $string ): string {
		return str_replace( '_', '-', self::snakeCase( $string ) );
	}

	// --------------------------------------------------

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public static function snakeCase( string $string ): string {
		if ( ! ctype_lower( $string ) ) {
			$string = preg_replace( [ '/\s+/u', '/(.)(?=[A-Z])/u' ], [ '', '$1_' ], $string );
			$string = mb_strtolower( $string, 'UTF-8' );
		}

		return str_replace( '-', '_', $string );
	}

	// --------------------------------------------------

	/**
	 * @param int $length
	 *
	 * @return string
	 */
	public static function random( int $length = 8 ): string {
		$text = base64_encode( wp_generate_password( $length, false ) );

		return substr( str_replace( [ '/', '+', '=' ], '', $text ), 0, $length );
	}

	// --------------------------------------------------

	/**
	 * @param string $string
	 * @param string $prefix
	 * @param $trim
	 *
	 * @return string
	 */
	public static function prefix( string $string, string $prefix, $trim = null ): string {
		if ( '' === $string ) {
			return $string;
		}
		if ( null === $trim ) {
			$trim = $prefix;
		}

		return $prefix . trim( self::removePrefix( $string, $trim ) );
	}

	// --------------------------------------------------

	/**
	 * @param string $prefix
	 * @param string $string
	 *
	 * @return string
	 */
	public static function removePrefix( string $string, string $prefix ): string {
		return self::startsWith( $prefix, $string )
			? mb_substr( $string, mb_strlen( $prefix ) )
			: $string;
	}

	// --------------------------------------------------

	/**
	 * @param $needles
	 * @param $haystack
	 *
	 * @return bool
	 */
	public static function startsWith( $needles, $haystack ): bool {
		$needles = (array) $needles;
		foreach ( $needles as $needle ) {
			if ( str_starts_with( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param $needles
	 * @param $haystack
	 *
	 * @return bool
	 */
	public static function endsWith( $needles, $haystack ): bool {
		$needles = (array) $needles;
		foreach ( $needles as $needle ) {
			if ( str_ends_with( $haystack, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param $string
	 * @param $suffix
	 *
	 * @return string
	 */
	public static function suffix( $string, $suffix ): string {
		if ( ! self::endsWith( $suffix, $string ) ) {
			return $string . $suffix;
		}

		return $string;
	}

	// --------------------------------------------------

	/**
	 * @param $search
	 * @param $replace
	 * @param $subject
	 *
	 * @return string
	 */
	public static function replaceFirst( $search, $replace, $subject ): string {
		if ( $search === '' ) {
			return $subject;
		}
		$position = mb_strpos( $subject, $search );
		if ( $position !== false ) {
			return mb_substr( $subject, 0, $position ) . $replace . mb_substr( $subject, $position + mb_strlen( $search ) );
		}

		return $subject;
	}

	// --------------------------------------------------

	/**
	 * @param string $search
	 * @param string $replace
	 * @param string $subject
	 *
	 * @return string
	 */
	public static function replaceLast( string $search, string $replace, string $subject ): string {
		$position = mb_strrpos( $subject, $search );
		if ( '' !== $search && false !== $position ) {
			return mb_substr( $subject, 0, $position ) . $replace . mb_substr( $subject, $position + mb_strlen( $search ) );
		}

		return $subject;
	}

	// --------------------------------------------------

	/**
	 * Strpos over an array.
	 *
	 * @param     $haystack
	 * @param     $needles
	 * @param int $offset
	 *
	 * @return bool
	 */
	public static function strposOffset( $haystack, $needles, int $offset = 0 ): bool {
		if ( ! is_array( $needles ) ) {
			$needles = [ $needles ];
		}
		foreach ( $needles as $query ) {
			if ( mb_strrpos( $haystack, $query, $offset ) !== false ) {

				// stop on the first true result.
				return true;
			}
		}

		return false;
	}

	// --------------------------------------------------

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public static function titleCase( string $string ): string {
		$value = str_replace( [ '-', '_' ], ' ', $string );

		return mb_convert_case( $value, MB_CASE_TITLE, 'UTF-8' );
	}

	// --------------------------------------------------

	/**
	 * Keywords
	 *
	 * Takes multiple words separated by spaces and changes them to keywords
	 * Makes sure the keywords are separated by a comma followed by a space.
	 *
	 * @param string $str The keywords as a string, separated by whitespace.
	 *
	 * @return string The list of keywords in a comma separated string form.
	 */
	public static function keyWords( string $str ): string {
		$str = preg_replace( '/[\v\s]+/u', ' ', $str );

		return preg_replace( '/\s+/', ', ', trim( $str ) );
	}

	// --------------------------------------------------

	/**
	 * @param $value
	 * @param $length
	 * @param string $end
	 *
	 * @return string
	 */
	public static function truncate( $value, $length, string $end = '' ): string {
		return mb_strwidth( $value, 'UTF-8' ) > $length
			? mb_substr( $value, 0, $length, 'UTF-8' ) . $end
			: $value;
	}

	// --------------------------------------------------

	/**
	 * @param string|null $string
	 *
	 * @return string|null
	 */
	public static function escAttr( ?string $string ): ?string {
		return esc_attr( self::stripAllTags( $string ) );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $string
	 * @param bool $remove_js
	 * @param bool $flatten
	 * @param $allowed_tags
	 *
	 * @return string
	 */
	public static function stripAllTags( ?string $string, bool $remove_js = true, bool $flatten = true, $allowed_tags = null ): string {
		if ( ! is_scalar( $string ) ) {
			return '';
		}

		if ( $remove_js ) {
			$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', ' ', $string ) ?? '';
		}

		$string = strip_tags( $string, $allowed_tags );

		if ( $flatten ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string ) ?? '';
		}

		return trim( $string );
	}

	// --------------------------------------------------

	/**
	 * @param string|null $string
	 * @param bool $strip_tags
	 * @param string $replace
	 *
	 * @return string
	 */
	public static function stripSpace( ?string $string, bool $strip_tags = true, string $replace = '' ): string {
		if ( empty( $string ) ) {
			return '';
		}

		if ( $strip_tags ) {
			$string = strip_tags( $string );
		}

		return preg_replace( '/[\v\s\x{00a0}]+/u', $replace, $string ) ?? $string;
	}

	// --------------------------------------------------

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	public static function normalize( string $text ): string {
		$allowedHtml         = wp_kses_allowed_html();
		$allowedHtml['mark'] = []; // allow using the <mark> tag to highlight text

		// just in case...
		$text = convert_smilies( excerpt_remove_blocks( strip_shortcodes( wp_kses( $text, $allowedHtml ) ) ) );
		$text = str_replace( ']]>', ']]&gt;', $text );

		return preg_replace( '/(\v){2,}/u', '$1', $text );
	}

	// --------------------------------------------------

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	public static function text( string $text ): string {
		$text = wptexturize( nl2br( self::normalize( $text ) ) );

		// replace all multiple-space and carriage return characters with a space
		return preg_replace( '/[\v\s]+/u', ' ', $text );
	}

	// --------------------------------------------------

	/**
	 * @param string $text
	 * @param int $limit
	 *
	 * @return int
	 */
	public static function excerptIntlSplit( string $text, int $limit ): int {
		if ( ! extension_loaded( 'intl' ) ) {
			return mb_strlen( $text );
		}

		$words = \IntlRuleBasedBreakIterator::createWordInstance( '' );
		$words->setText( $text );
		$count = 0;
		foreach ( $words as $offset ) {
			if ( \IntlBreakIterator::WORD_NONE === $words->getRuleStatus() ) {
				continue;
			}
			++ $count;
			if ( $count !== $limit ) {
				continue;
			}

			return $offset;
		}

		return mb_strlen( $text );
	}

	// --------------------------------------------------

	/**
	 * @param string $text
	 * @param int $limit
	 *
	 * @return int
	 */
	protected static function excerptSplit( string $text, int $limit ): int {
		if ( str_word_count( $text ) > $limit ) {
			$words = array_keys( str_word_count( $text, 2 ) );

			return $words[ $limit ];
		}

		return mb_strlen( $text );
	}

	// --------------------------------------------------

	/**
	 * @param string $text
	 * @param int $limit
	 * @param bool $splitWords
	 * @param string $showMore
	 *
	 * @return string
	 */
	public static function excerpt( string $text, int $limit = 55, bool $splitWords = true, string $showMore = '...' ): string {
		$text        = strip_tags( $text );
		$text        = self::normalize( $text );
		$splitLength = $limit;

		if ( $splitWords ) {
			$splitLength = extension_loaded( 'intl' )
				? self::excerptIntlSplit( $text, $limit )
				: self::excerptSplit( $text, $limit );
		}

		$hiddenText = mb_substr( $text, $splitLength );
		if ( ! empty( $hiddenText ) ) {
			$text = ltrim( mb_substr( $text, 0, $splitLength ) ) . $showMore;
		}

		$text = wptexturize( nl2br( $text ) );

		return preg_replace( '/[\v\s]+/u', ' ', $text ) ?? $text;
	}
}
