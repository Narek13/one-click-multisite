<?php
/**
 * PHPUnit bootstrap. Unit tests use Brain Monkey to stub WordPress functions,
 * so no full WP install is required.
 *
 * The conversion itself (network tables, wp-config.php, .htaccess) is a real
 * file-system and database round trip, so it is exercised in DDEV instead
 * (see SETUP.md), not mocked here.
 *
 * @package OneClickMultisite
 */

declare(strict_types=1);

$one_click_multisite_autoload = dirname( __DIR__, 2 ) . '/vendor/autoload.php';

if ( ! is_readable( $one_click_multisite_autoload ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI bootstrap, no WP_Filesystem available.
	fwrite( STDERR, "Dependencies not installed. Run `composer install` first.\n" );
	exit( 1 );
}

require $one_click_multisite_autoload;
require __DIR__ . '/Stubs/wp-classes.php';

// Production code reads ABSPATH; point it at a directory that holds no WordPress install.
if ( ! defined( 'ABSPATH' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core's own constant.
	define( 'ABSPATH', sys_get_temp_dir() . '/one-click-multisite-tests/wordpress/' );
}
