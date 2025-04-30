<?php

use MU\DisallowIndexing;
use MU\PluginDisabler\PluginDisabler;

/**
 * MU Class
 *
 * @author Gaudev
 */
final class MU {
	public function __construct() {
		( new DisallowIndexing() );
		( new PluginDisabler() );
	}
}
