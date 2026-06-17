<?php
/**
 * Multisite converter.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite\Conversion;

/**
 * Converts a single-site WordPress installation to a multisite network.
 */
class MultisiteConverter {

	/**
	 * Prerequisite checker.
	 *
	 * @var PrerequisiteChecker
	 */
	private PrerequisiteChecker $checker;

	/**
	 * Constructor.
	 *
	 * @param PrerequisiteChecker $checker Prerequisite checker.
	 */
	public function __construct( PrerequisiteChecker $checker ) {
		$this->checker = $checker;
	}

	/**
	 * Runs the full conversion: creates multisite tables, writes wp-config.php, and writes .htaccess.
	 *
	 * @param bool $subdomain_install True for subdomain, false for subdirectory network.
	 * @return ConversionResult
	 */
	public function convert( bool $subdomain_install ): ConversionResult {
		if ( ! $this->checker->all_pass() ) {
			return new ConversionResult(
				false,
				__( 'One or more prerequisites are not met.', 'one-click-multisite' )
			);
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// wpdb only registers multisite table properties when is_multisite() or
		// WP_ALLOW_MULTISITE is defined. Neither is true here (the prerequisite check
		// enforces that), so we register them manually before doing anything else.
		// Without this, every query in populate_network() runs against an empty table
		// name, fails silently, and returns null — indistinguishable from success.
		global $wpdb;
		if ( empty( $wpdb->site ) ) {
			$bp                     = $wpdb->base_prefix;
			$wpdb->blogs            = $bp . 'blogs';
			$wpdb->blogmeta         = $bp . 'blogmeta';
			$wpdb->signups          = $bp . 'signups';
			$wpdb->site             = $bp . 'site';
			$wpdb->sitemeta         = $bp . 'sitemeta';
			$wpdb->sitecategories   = $bp . 'sitecategories';
			$wpdb->registration_log = $bp . 'registration_log';
		}

		// populate_network() only inserts rows — the tables themselves must be
		// created first. make_db_current_silent('ms_global') runs CREATE TABLE IF
		// NOT EXISTS for wp_blogs, wp_site, wp_sitemeta, wp_signups, etc.
		make_db_current_silent( 'ms_global' );

		$domain      = (string) wp_parse_url( get_option( 'siteurl' ), PHP_URL_HOST );
		$path        = (string) wp_parse_url( get_option( 'siteurl' ), PHP_URL_PATH );
		$path        = trailingslashit( $path ? $path : '/' );
		$site_title  = (string) get_option( 'blogname' );
		$admin_email = (string) get_option( 'admin_email' );

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$network_error = populate_network( 1, $domain, $admin_email, $site_title, $path, $subdomain_install );

		if ( is_wp_error( $network_error ) ) {
			// 'siteid_exists' means the DB was already populated by a previous
			// conversion attempt. The tables and rows are intact — we can still
			// (re-)write wp-config.php and .htaccess.
			if ( ! in_array( 'siteid_exists', $network_error->get_error_codes(), true ) ) {
				return new ConversionResult( false, $network_error->get_error_message() );
			}
		}

		$config_result = $this->write_wp_config( $subdomain_install, $domain, $path );
		if ( ! $config_result->success() ) {
			return $config_result;
		}

		$htaccess_result = $this->write_htaccess( $subdomain_install );
		if ( ! $htaccess_result->success() ) {
			return $htaccess_result;
		}

		wp_cache_flush();

		return new ConversionResult( true );
	}

	/**
	 * Writes multisite constants to wp-config.php.
	 *
	 * @param bool   $subdomain_install True for subdomain network.
	 * @param string $domain            Network domain.
	 * @param string $path              Network path.
	 * @return ConversionResult
	 */
	private function write_wp_config( bool $subdomain_install, string $domain, string $path ): ConversionResult {
		$config_file = $this->checker->find_wp_config();
		if ( null === $config_file ) {
			return new ConversionResult( false, __( 'wp-config.php not found.', 'one-click-multisite' ) );
		}

		$contents = (string) file_get_contents( $config_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( '' === $contents ) {
			return new ConversionResult( false, __( 'wp-config.php is empty or unreadable.', 'one-click-multisite' ) );
		}

		$subdomain_value = $subdomain_install ? 'true' : 'false';

		$constants = "
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', {$subdomain_value} );
define( 'DOMAIN_CURRENT_SITE', '{$domain}' );
define( 'PATH_CURRENT_SITE', '{$path}' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );

";

		$marker = "/* That's all, stop editing!";

		if ( false !== strpos( $contents, $marker ) ) {
			$contents = str_replace( $marker, $constants . $marker, $contents );
		} else {
			$contents .= $constants;
		}

		if ( false === file_put_contents( $config_file, $contents ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return new ConversionResult( false, __( 'Could not write to wp-config.php.', 'one-click-multisite' ) );
		}

		return new ConversionResult( true );
	}

	/**
	 * Writes multisite rewrite rules to .htaccess.
	 *
	 * @param bool $subdomain_install True for subdomain network.
	 * @return ConversionResult
	 */
	private function write_htaccess( bool $subdomain_install ): ConversionResult {
		$htaccess_file = ABSPATH . '.htaccess';

		if ( $subdomain_install ) {
			// Sub-domain: each site runs on its own domain, no path prefix needed.
			$lines = array(
				'RewriteEngine On',
				'RewriteBase /',
				'RewriteRule ^index\.php$ - [L]',
				'',
				'RewriteCond %{REQUEST_FILENAME} -f [OR]',
				'RewriteCond %{REQUEST_FILENAME} -d',
				'RewriteRule ^ - [L]',
				'RewriteRule ^(wp-(content|admin|includes).*) $1 [L]',
				'RewriteRule ^(.*\.php)$ $1 [L]',
				'RewriteRule . index.php [L]',
			);
		} else {
			// Sub-directory: sites live under path prefixes, e.g. /site1/.
			$lines = array(
				'RewriteEngine On',
				'RewriteBase /',
				'RewriteRule ^index\.php$ - [L]',
				'',
				'# add a trailing slash to /wp-admin',
				'RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]',
				'',
				'RewriteCond %{REQUEST_FILENAME} -f [OR]',
				'RewriteCond %{REQUEST_FILENAME} -d',
				'RewriteRule ^ - [L]',
				'RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]',
				'RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]',
				'RewriteRule . index.php [L]',
			);
		}

		$inserted = insert_with_markers( $htaccess_file, 'WordPress', $lines );

		if ( ! $inserted ) {
			return new ConversionResult( false, __( 'Could not write to .htaccess.', 'one-click-multisite' ) );
		}

		return new ConversionResult( true );
	}
}
