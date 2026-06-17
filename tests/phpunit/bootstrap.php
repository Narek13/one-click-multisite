<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package OneClickMultisite
 */

declare(strict_types=1);

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- test bootstrap, not shipped.
$plugin_root = dirname( __DIR__, 2 );

// Patchwork must be required before the autoloader loads any file it may need to patch.
require_once $plugin_root . '/vendor/antecedent/patchwork/Patchwork.php';
require_once $plugin_root . '/vendor/autoload.php';

// Minimal WordPress constants required by production code.
if ( ! defined( 'ABSPATH' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Mimicking WP core constant for test environment.
	define( 'ABSPATH', sys_get_temp_dir() . '/wordpress/' );
}
