<?php
/**
 * Plugin Name:       One Click Multisite
 * Plugin URI:        https://github.com/narekzakarian/one-click-multisite
 * Description:       Convert a single-site WordPress installation to a multisite network with one click from Tools &gt; Convert to Multisite.
 * Version:           1.0.0
 * Author:            Narek Zakarian
 * Author URI:        https://github.com/narekzakarian
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       one-click-multisite
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Network:           false
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays an admin notice for bootstrap errors.
 *
 * @param string $message The error message (may contain safe HTML).
 * @return void
 */
function one_click_multisite_handle_error( string $message ): void {
	add_action(
		'all_admin_notices',
		static function () use ( $message ): void {
			printf(
				'<div class="notice notice-error"><p><strong>One Click Multisite:</strong> %s</p></div>',
				wp_kses_post( $message )
			);
		}
	);
}

/**
 * Initializes the plugin.
 *
 * @return void
 */
function one_click_multisite_initialize(): void {
	$autoload = __DIR__ . '/vendor/autoload.php';
	if ( ! is_readable( $autoload ) ) {
		one_click_multisite_handle_error(
			sprintf(
				/* translators: %s: path to the plugin directory */
				__( 'Autoloader not found. Please run <code>composer install</code> in %s.', 'one-click-multisite' ),
				esc_html( __DIR__ )
			)
		);
		return;
	}

	require_once $autoload;

	try {
		Plugin::new( PluginProperties::new( __FILE__ ) )
			->add_module( new OneClickMultisiteModule( __FILE__ ) )
			->add_module( new Conversion\ConversionModule() )
			->add_module( new Admin\AdminModule() )
			->boot();
	} catch ( \Throwable $e ) {
		one_click_multisite_handle_error(
			sprintf(
				'<strong>%s</strong><br><pre>%s</pre>',
				esc_html( $e->getMessage() ),
				esc_html( $e->getTraceAsString() )
			)
		);
	}
}

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain(
			'one-click-multisite',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
);

add_action( 'plugins_loaded', __NAMESPACE__ . '\\one_click_multisite_initialize' );
