<?php

/**
 * Plugin Name:       Multisite Auto-Enabler
 * Plugin URI:        https://github.com/narekzakarian/multisite-auto-enabler
 * Description:       Convert a single-site WordPress installation to a multisite network with one click from Tools &gt; Convert to Multisite.
 * Version:           1.0.0
 * Author:            Narek Zakarian
 * Author URI:        https://github.com/narekzakarian
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       multisite-auto-enabler
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Network:           false
 */

declare(strict_types=1);

namespace MultisiteAutoEnabler;

if (!defined('ABSPATH')) {
    exit;
}

function handleError(string $message): void
{
    add_action(
        'all_admin_notices',
        static function () use ($message): void {
            printf(
                '<div class="notice notice-error"><p><strong>Multisite Auto-Enabler:</strong> %s</p></div>',
                wp_kses_post($message)
            );
        }
    );
}

function initialize(): void
{
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_readable($autoload)) {
        handleError(
            sprintf(
                /* translators: %s: path to the plugin directory */
                __('Autoloader not found. Please run <code>composer install</code> in %s.', 'multisite-auto-enabler'),
                esc_html(__DIR__)
            )
        );
        return;
    }

    require_once $autoload;

    try {
        Plugin::new(PluginProperties::new(__FILE__))
            ->addModule(new MultisiteAutoEnablerModule(__FILE__))
            ->addModule(new Conversion\ConversionModule())
            ->addModule(new Admin\AdminModule())
            ->boot();
    } catch (\Throwable $e) {
        handleError(
            sprintf(
                '<strong>%s</strong><br><pre>%s</pre>',
                esc_html($e->getMessage()),
                esc_html($e->getTraceAsString())
            )
        );
    }
}

add_action('init', static function (): void {
    load_plugin_textdomain(
        'multisite-auto-enabler',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

add_action('plugins_loaded', __NAMESPACE__ . '\\initialize');
