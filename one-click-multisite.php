<?php
/**
 * Plugin Name:       One Click Multisite
 * Description:       Convert a single-site WordPress installation into a multisite network with one click from Tools > Convert to Multisite, and revert it back.
 * Version:           1.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Narek Zakarian
 * Author URI:        https://profile.wordpress.org/nareknovembit
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       one-click-multisite
 * Domain Path:       /languages
 *
 * @package OneClickMultisite
 */

declare(strict_types=1);

// No direct access.
defined( 'ABSPATH' ) || exit;

if ( defined( 'ONE_CLICK_MULTISITE_VERSION' ) ) {
	return;
}

define( 'ONE_CLICK_MULTISITE_VERSION', '1.1.0' );
define( 'ONE_CLICK_MULTISITE_FILE', __FILE__ );
define( 'ONE_CLICK_MULTISITE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ONE_CLICK_MULTISITE_URL', plugin_dir_url( __FILE__ ) );

/*
 * Autoloading.
 *
 * Prefer Composer's autoloader if the plugin has been built (vendor/ present).
 * Otherwise fall back to a tiny PSR-4 autoloader so the plugin runs as soon as
 * it's dropped into wp-content/plugins, no build step required.
 */
if ( is_readable( ONE_CLICK_MULTISITE_DIR . 'vendor/autoload.php' ) ) {
	require ONE_CLICK_MULTISITE_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( string $class_name ): void {
			$prefix = 'OneClickMultisite\\';
			if ( ! str_starts_with( $class_name, $prefix ) ) {
				return;
			}
			$relative = substr( $class_name, strlen( $prefix ) );
			$path     = ONE_CLICK_MULTISITE_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
			if ( is_readable( $path ) ) {
				require $path;
			}
		}
	);
}

use OneClickMultisite\Plugin;

// Boot once WordPress and all plugins are loaded.
add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->boot();
	}
);
