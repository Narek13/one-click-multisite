<?php
/**
 * Uninstall — runs only when the plugin is deleted from the WordPress admin.
 *
 * The plugin stores no settings. The only data it leaves behind is the
 * short-lived, per-user result notice, so that is all this removes.
 *
 * The network itself (wp-config.php constants, .htaccess rules and network
 * tables) is deliberately left alone: it is the site's configuration now, not
 * plugin data, and removing it would break every site on the network.
 *
 * @package OneClickMultisite
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$one_click_multisite_like = $wpdb->esc_like( '_transient_one_click_multisite_notice_' ) . '%';
$one_click_multisite_ttl  = $wpdb->esc_like( '_transient_timeout_one_click_multisite_notice_' ) . '%';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one-off cleanup of per-user transients; no API deletes by prefix.
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $one_click_multisite_like, $one_click_multisite_ttl ) );
