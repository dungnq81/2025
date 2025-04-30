<?php

namespace Addons;

\defined( 'ABSPATH' ) || exit;

/**
 * Creates minified CSS via PHP.
 *
 * @author  Carlos Rios
 *
 * Modified by Tom Usborne for GeneratePress
 * Modified by Gaudev
 */
final class CSS {
	private string $_selector = '';
	private string $_selector_output = '';
	private string $_css = '';
	private string $_output = '';
	private ?string $_media_query = null;
	private string $_media_query_output = '';

	/**
	 * @param string $selector
	 *
	 * @return $this
	 */
	public function set_selector( string $selector = '' ) {
		// Render the CSS in the output string everytime the selector changes.
		if ( $this->_selector !== '' ) {
			$this->add_selector_rules_to_output();
		}

		$this->_selector = $selector;

		return $this;
	}

	/**
	 * @param $property
	 * @param $value
	 * @param mixed $og_default
	 * @param mixed $unit
	 *
	 * @return $this|false
	 */
	public function add_property( $property, $value, mixed $og_default = false, mixed $unit = false ) {
		// Setting font-size to 0 is rarely ever a good thing.
		if ( 'font-size' === $property && 0 === $value ) {
			return false;
		}

		// Add our unit to our value if it exists.
		if ( $unit && '' !== $unit && is_numeric( $value ) ) {
			$value .= $unit;
			if ( '' !== $og_default ) {
				$og_default .= $unit;
			}
		}

		// If we don't have a value or our value is the same as our og default, bail.
		if ( ( empty( $value ) && ! is_numeric( $value ) ) || $og_default === $value ) {
			return false;
		}

		$this->_css .= $property . ':' . $value . ';';

		return $this;
	}

	/**
	 * @param $value
	 *
	 * @return $this
	 */
	public function start_media_query( $value ) {
		// Add the current rules to the output.
		$this->add_selector_rules_to_output();

		// Add any previous media queries to the output.
		if ( ! empty( $this->_media_query ) ) {
			$this->add_media_query_rules_to_output();
		}

		// Set the new media query.
		$this->_media_query = $value;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function stop_media_query() {
		return $this->start_media_query( null );
	}

	/**
	 * @return $this
	 */
	private function add_media_query_rules_to_output() {
		if ( ! empty( $this->_media_query_output ) ) {
			$this->_output .= sprintf( '@media %1$s{%2$s}', $this->_media_query, $this->_media_query_output );

			// Reset the media query output string.
			$this->_media_query_output = '';
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	private function add_selector_rules_to_output() {
		if ( ! empty( $this->_css ) ) {
			$this->_selector_output = $this->_selector;
			$selector_output        = sprintf( '%1$s{%2$s}', $this->_selector_output, $this->_css );

			// Add our CSS to the output.
			if ( ! empty( $this->_media_query ) ) {
				$this->_media_query_output .= $selector_output;
				//$this->_css                = '';
			} else {
				$this->_output .= $selector_output;
			}

			// Reset the CSS.
			$this->_css = '';
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function css_output(): string {
		// Add current selector's rules to output.
		$this->add_selector_rules_to_output();

		// Output minified CSS.
		return $this->_output;
	}
}
