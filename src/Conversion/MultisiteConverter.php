<?php

declare(strict_types=1);

namespace OneClickMultisite\Conversion;

use WP_Filesystem_Base;

/**
 * Converts a single-site installation into a multisite network.
 *
 * Runs the same steps as core's Network Setup screen (wp-admin/network.php),
 * then applies the wp-config.php and .htaccess changes that screen would
 * otherwise ask the admin to paste by hand.
 */
final class MultisiteConverter {

	/**
	 * Constructor.
	 *
	 * @param PrerequisiteChecker $checker  Prerequisite checker.
	 * @param WpConfigEditor      $wpConfig wp-config.php editor.
	 */
	public function __construct(
		private readonly PrerequisiteChecker $checker,
		private readonly WpConfigEditor $wpConfig = new WpConfigEditor(),
	) {
	}

	/**
	 * Runs the conversion.
	 *
	 * @param bool $requestedSubdomain Whether the admin picked a sub-domain network.
	 * @return ConversionResult
	 */
	public function convert( bool $requestedSubdomain ): ConversionResult {
		if ( ! $this->checker->canConvert() ) {
			return ConversionResult::failure( __( 'One or more prerequisites are not met.', 'one-click-multisite' ) );
		}

		$environment      = $this->checker->environment();
		$subdomainInstall = $environment->resolveSubdomainInstall( $requestedSubdomain );
		$domain           = $environment->domain();
		$base             = $environment->base();
		$warnings         = array();

		$filesystem = $environment->filesystem();
		if ( null === $filesystem ) {
			return ConversionResult::failure( __( 'Could not initialise the WordPress file system.', 'one-click-multisite' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Network table names are registered on wpdb by Environment (domain() above).
		// Creates the network tables and the users.spam/deleted columns.
		install_network();

		// populate_network() replaces the main site's permalink structure with a fixed
		// default (/blog/%year%/%monthnum%/%day%/%postname%/ on sub-directory networks),
		// which would change every existing URL. Remember it and put it back.
		$permalinkStructure = (string) get_option( 'permalink_structure' );

		$populated = populate_network(
			1,
			$domain,
			sanitize_email( (string) get_option( 'admin_email' ) ),
			(string) get_option( 'blogname' ),
			$base,
			$subdomainInstall
		);

		$this->restorePermalinkStructure( $permalinkStructure );

		if ( is_wp_error( $populated ) ) {
			foreach ( $populated->get_error_codes() as $code ) {
				if ( 'siteid_exists' === $code ) {
					// The network rows exist from an earlier attempt; finish the file changes.
					continue;
				}
				if ( 'no_wildcard_dns' === $code ) {
					// The network was created; the random-subdomain probe just failed.
					$warnings[] = trim( wp_strip_all_tags( (string) $populated->get_error_message( $code ) ) );
					continue;
				}

				return ConversionResult::failure( wp_strip_all_tags( (string) $populated->get_error_message( $code ) ) );
			}
		}

		// .htaccess first: the multisite rules still serve a single site, whereas
		// wp-config.php switches multisite on, so it must be the last change made.
		if ( $environment->usesHtaccess() ) {
			$htaccessResult = $this->writeHtaccess( $environment, $subdomainInstall, $base );
			if ( ! $htaccessResult->succeeded() ) {
				return $htaccessResult;
			}
		} else {
			$warnings[] = __( 'This server does not read .htaccess. Add the multisite rewrite rules to your web server configuration (for nginx, see https://developer.wordpress.org/advanced-administration/server/web-server/nginx/).', 'one-click-multisite' );
		}

		$configResult = $this->writeWpConfig( $filesystem, $subdomainInstall, $domain, $base );
		if ( ! $configResult->succeeded() ) {
			return $configResult;
		}

		wp_cache_flush();

		return ConversionResult::success( $warnings );
	}

	/**
	 * Adds the multisite constants to wp-config.php.
	 *
	 * @param WP_Filesystem_Base $filesystem       File system.
	 * @param bool               $subdomainInstall True for a sub-domain network.
	 * @param string             $domain           Network domain.
	 * @param string             $base             Network path.
	 * @return ConversionResult
	 */
	private function writeWpConfig( WP_Filesystem_Base $filesystem, bool $subdomainInstall, string $domain, string $base ): ConversionResult {
		$configFile = $this->checker->environment()->wpConfigPath();
		if ( null === $configFile ) {
			return ConversionResult::failure( __( 'wp-config.php not found.', 'one-click-multisite' ) );
		}

		$contents = $filesystem->get_contents( $configFile );
		if ( ! is_string( $contents ) || '' === $contents ) {
			return ConversionResult::failure( __( 'wp-config.php is empty or unreadable.', 'one-click-multisite' ) );
		}

		$updated = $this->wpConfig->insert(
			$contents,
			array(
				'WP_ALLOW_MULTISITE'   => true,
				'MULTISITE'            => true,
				'SUBDOMAIN_INSTALL'    => $subdomainInstall,
				'DOMAIN_CURRENT_SITE'  => $domain,
				'PATH_CURRENT_SITE'    => $base,
				'SITE_ID_CURRENT_SITE' => 1,
				'BLOG_ID_CURRENT_SITE' => 1,
			)
		);

		if ( null === $updated ) {
			return ConversionResult::failure( __( 'Could not find where to add the multisite settings in wp-config.php (neither the "stop editing" line nor the wp-settings.php include was found).', 'one-click-multisite' ) );
		}

		if ( $updated === $contents ) {
			return ConversionResult::success();
		}

		if ( ! Environment::writeKeepingMode( $filesystem, $configFile, $updated ) ) {
			return ConversionResult::failure( __( 'Could not write to wp-config.php.', 'one-click-multisite' ) );
		}

		return ConversionResult::success();
	}

	/**
	 * Puts the site's own permalink structure back after populate_network() reset it.
	 *
	 * Core prefixes sub-directory networks with /blog to keep main-site posts from
	 * colliding with new site addresses; core itself still refuses to create a site
	 * whose name matches an existing page, so keeping the URLs people already link
	 * to is the safer default for an existing site.
	 *
	 * @param string $structure Structure before populate_network() ran.
	 */
	private function restorePermalinkStructure( string $structure ): void {
		global $wp_rewrite;

		if ( (string) get_option( 'permalink_structure' ) === $structure ) {
			return;
		}

		$wp_rewrite->set_permalink_structure( $structure );
		flush_rewrite_rules( false );
	}

	/**
	 * Replaces the WordPress block in .htaccess with the multisite rewrite rules.
	 *
	 * @param Environment $environment      Installation facts.
	 * @param bool        $subdomainInstall True for a sub-domain network.
	 * @param string      $base             Network path.
	 * @return ConversionResult
	 */
	private function writeHtaccess( Environment $environment, bool $subdomainInstall, string $base ): ConversionResult {
		$lines = HtaccessRules::lines( $subdomainInstall, $base, $environment->rewriteBase() );

		if ( ! insert_with_markers( $environment->htaccessPath(), 'WordPress', $lines ) ) {
			return ConversionResult::failure( __( 'Could not write to .htaccess.', 'one-click-multisite' ) );
		}

		return ConversionResult::success();
	}
}
