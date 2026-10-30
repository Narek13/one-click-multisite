<?php

declare(strict_types=1);

namespace OneClickMultisite\Revert;

use OneClickMultisite\Conversion\ConversionResult;
use OneClickMultisite\Conversion\Environment;
use OneClickMultisite\Conversion\HtaccessRules;
use OneClickMultisite\Conversion\WpConfigEditor;

/**
 * Turns a network that only holds its main site back into a single site.
 *
 * The steps run in the order that keeps the site working if one of them fails:
 *
 * 1. Network-activated plugins are added to the main site's active plugins
 *    (harmless while still a network, and nothing is deactivated by the revert).
 * 2. wp-config.php loses the multisite constants. From the next request on the
 *    site is a single site; if this write fails, nothing else has changed. A
 *    /blog permalink prefix left by a hand-made network is removed.
 * 3. .htaccess gets the single-site rules back (Apache only).
 * 4. The network tables are dropped. A failure here leaves unused tables behind,
 *    which a single site ignores.
 */
final class SingleSiteReverter {

	/**
	 * Network tables that predate wpdb::tables( 'ms_global' ) but may still exist.
	 */
	private const LEGACY_TABLES = array( 'blog_versions' );

	/**
	 * Constructor.
	 *
	 * @param RevertChecker  $checker  Revert checks.
	 * @param WpConfigEditor $wpConfig wp-config.php editor.
	 */
	public function __construct(
		private readonly RevertChecker $checker,
		private readonly WpConfigEditor $wpConfig = new WpConfigEditor(),
	) {
	}

	/**
	 * Runs the revert.
	 *
	 * @return ConversionResult
	 */
	public function revert(): ConversionResult {
		if ( ! $this->checker->canRevert() ) {
			return ConversionResult::failure( __( 'One or more checks did not pass.', 'one-click-multisite' ) );
		}

		$environment = $this->checker->environment();
		$warnings    = array();

		$filesystem = $environment->filesystem();
		$configFile = $environment->wpConfigPath();
		if ( null === $filesystem || null === $configFile ) {
			return ConversionResult::failure( __( 'Could not initialise the WordPress file system.', 'one-click-multisite' ) );
		}

		$contents = $filesystem->get_contents( $configFile );
		$updated  = is_string( $contents ) ? $this->wpConfig->remove( $contents, RevertChecker::CONSTANTS ) : null;
		if ( null === $updated ) {
			return ConversionResult::failure( __( 'The multisite settings could not be removed from wp-config.php.', 'one-click-multisite' ) );
		}

		$this->keepNetworkPluginsActive();

		$subdomainInstall = is_subdomain_install();

		if ( ! Environment::writeKeepingMode( $filesystem, $configFile, $updated ) ) {
			return ConversionResult::failure( __( 'Could not write to wp-config.php. Nothing was changed.', 'one-click-multisite' ) );
		}

		$this->dropBlogPrefix( $subdomainInstall );

		if ( $environment->usesHtaccess() ) {
			$lines = HtaccessRules::singleSiteLines( $environment->base(), '' !== (string) get_option( 'permalink_structure' ) );
			if ( ! insert_with_markers( $environment->htaccessPath(), 'WordPress', $lines ) ) {
				$warnings[] = __( 'The .htaccess rules could not be restored. Go to Settings > Permalinks and click Save Changes.', 'one-click-multisite' );
			}
		} else {
			$warnings[] = __( 'This server does not read .htaccess. Replace the multisite rewrite rules in your web server configuration with the standard WordPress ones.', 'one-click-multisite' );
		}

		if ( ! $this->dropNetworkTables() ) {
			$warnings[] = __( 'Some network tables could not be removed. A single site ignores them, so this is safe to leave.', 'one-click-multisite' );
		}

		wp_cache_flush();

		return ConversionResult::success( $warnings );
	}

	/**
	 * Removes the /blog prefix a sub-directory network puts in front of the main
	 * site's permalinks (core adds it when a network is set up by hand). It only
	 * exists to avoid collisions with other sites, which a single site has none of.
	 *
	 * @param bool $subdomainInstall Whether the network used sub-domains.
	 */
	private function dropBlogPrefix( bool $subdomainInstall ): void {
		global $wp_rewrite;

		$structure = (string) get_option( 'permalink_structure' );
		if ( $subdomainInstall || ! str_starts_with( $structure, '/blog/' ) ) {
			return;
		}

		$wp_rewrite->set_permalink_structure( substr( $structure, strlen( '/blog' ) ) );
		delete_option( 'rewrite_rules' );
	}

	/**
	 * Adds every network-activated plugin to the main site's active plugins,
	 * so nothing silently switches off when the network disappears.
	 */
	private function keepNetworkPluginsActive(): void {
		$networkPlugins = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) );
		if ( array() === $networkPlugins ) {
			return;
		}

		$active = array_values( array_filter( (array) get_option( 'active_plugins', array() ), 'is_string' ) );
		$merged = array_values( array_unique( array_merge( $active, array_map( 'strval', $networkPlugins ) ) ) );
		sort( $merged );

		if ( $merged !== $active ) {
			update_option( 'active_plugins', $merged );
		}
	}

	/**
	 * Drops the network tables.
	 *
	 * @return bool False if a table could not be dropped.
	 */
	private function dropNetworkTables(): bool {
		global $wpdb;

		$tables = array_values( $wpdb->tables( 'ms_global' ) );
		foreach ( self::LEGACY_TABLES as $table ) {
			$tables[] = $wpdb->base_prefix . $table;
		}

		$ok = true;
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- removing the network schema is the point of a revert.
			$ok = false !== $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) ) && $ok;
		}

		return $ok;
	}
}
