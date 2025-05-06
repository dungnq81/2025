<?php

namespace HD\Utilities\Traits;

\defined( 'ABSPATH' ) || die;

/**
 * Singleton base class for having singleton implementation
 * This allows you to have only one instance of the necessary object
 * You can get the instance with $class = My_Class::get_instance();
 *
 * /!\ The get_instance method has to be implemented in the child class!
 */
trait Singleton {
	protected static $instance;

	final public static function get_instance(): static {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	private function __construct() {
		$this->init();
	}

	protected function init(): void {}

	final public function __clone() {}

	final public function __wakeup() {}
}
