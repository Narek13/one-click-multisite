<?php

declare(strict_types=1);

namespace OneClickMultisite\Conversion;

/**
 * Facts about the current installation that the network setup depends on.
 *
 * Every value is derived the way WordPress core derives it on the manual
 * Network Setup screen (wp-admin/network.php and wp-admin/includes/network.php),
 * so a one-click conversion ends up identical to a hand-made one.
 */
final class Environment {

	/**
	 * Whether the includes and network table names have been set up.
	 *
	 * @var bool
	 */
	private bool $prepared = false;

	/**
	 * Loads the admin include files that hold the network helpers and registers
	 * the network table names, on first use only, so admin requests that never
	 * open the Tools page don't pay for them.
	 *
	 * The wpdb object only knows the network table names once multisite is on. Core's
	 * Network Setup screen registers them for the request before anything else
	 * (wp-admin/network.php), because helpers such as network_domain_check() and
	 * populate_network() query them. This does the same.
	 */
	private function loadIncludes(): void {
		if ( $this->prepared ) {
			return;
		}
		$this->prepared = true;

		if ( ! function_exists( 'allow_subdomain_install' ) ) {
			require_once ABSPATH . 'wp-admin/includes/network.php';
		}
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'got_mod_rewrite' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		global $wpdb;
		if ( $wpdb instanceof \wpdb && empty( $wpdb->site ) ) {
			foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixedTable ) {
				$wpdb->$table = $prefixedTable;
			}
		}
	}

	/**
	 * Locates wp-config.php the same way wp-load.php does: in ABSPATH, or one level
	 * up as long as that directory is not itself another WordPress install.
	 *
	 * @return string|null Absolute path, or null when not found.
	 */
	public function wpConfigPath(): ?string {
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			return ABSPATH . 'wp-config.php';
		}

		$parent = dirname( ABSPATH );
		if ( file_exists( $parent . '/wp-config.php' ) && ! file_exists( $parent . '/wp-settings.php' ) ) {
			return $parent . '/wp-config.php';
		}

		return null;
	}

	/**
	 * Returns whether WordPress can write files directly in the wp-config.php
	 * directory, i.e. without asking for FTP or SSH credentials.
	 *
	 * @return bool
	 */
	public function hasDirectFilesystem(): bool {
		$this->loadIncludes();

		$config = $this->wpConfigPath();

		return 'direct' === get_filesystem_method( array(), null === $config ? ABSPATH : dirname( $config ) );
	}

	/**
	 * Initialises and returns the direct WordPress file system.
	 *
	 * @return \WP_Filesystem_Base|null Null when it cannot be initialised.
	 */
	public function filesystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;

		$this->loadIncludes();

		if ( ! WP_Filesystem() || ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			return null;
		}

		return $wp_filesystem;
	}

	/**
	 * Writes a file and keeps its existing permissions: wp-config.php is often
	 * deliberately 0600 or 0640, and WP_Filesystem would otherwise reset it to 0644.
	 *
	 * @param \WP_Filesystem_Base $filesystem File system.
	 * @param string              $path       Absolute file path.
	 * @param string              $contents   New contents.
	 * @return bool
	 */
	public static function writeKeepingMode( \WP_Filesystem_Base $filesystem, string $path, string $contents ): bool {
		$mode = (int) octdec( (string) $filesystem->getchmod( $path ) );

		return (bool) $filesystem->put_contents( $path, $contents, $mode > 0 ? $mode : FS_CHMOD_FILE );
	}

	/**
	 * Returns the network domain, e.g. "example.com" (port included if the site URL has one).
	 *
	 * @return string
	 */
	public function domain(): string {
		$this->loadIncludes();

		return (string) get_clean_basedomain();
	}

	/**
	 * Returns whether the site address uses a port other than 80 or 443,
	 * which WordPress refuses to build a network on.
	 *
	 * @return bool
	 */
	public function hasNonStandardPort(): bool {
		$hostname = (string) preg_replace( '/(?::80|:443)$/', '', $this->domain() );

		return str_contains( $hostname, ':' );
	}

	/**
	 * Returns the network path from the home URL, with leading and trailing slash.
	 *
	 * @return string
	 */
	public function base(): string {
		$path = wp_parse_url( trailingslashit( (string) get_option( 'home' ) ), PHP_URL_PATH );

		return is_string( $path ) && '' !== $path ? $path : '/';
	}

	/**
	 * Returns the absolute path of the directory that holds .htaccess.
	 *
	 * @return string
	 */
	public function homePath(): string {
		$documentRoot = isset( $_SERVER['DOCUMENT_ROOT'] ) ? (string) realpath( sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) ) : '';
		$documentRoot = str_replace( '\\', '/', $documentRoot );
		$abspath      = str_replace( '\\', '/', ABSPATH );

		if ( '' !== $documentRoot && str_starts_with( $abspath, $documentRoot ) ) {
			return $documentRoot . $this->base();
		}

		$this->loadIncludes();

		return get_home_path();
	}

	/**
	 * Returns the path to .htaccess.
	 *
	 * @return string
	 */
	public function htaccessPath(): string {
		return $this->homePath() . '.htaccess';
	}

	/**
	 * Returns WordPress core's directory relative to the home path (e.g. "wp/")
	 * when core is installed in its own directory, else "".
	 *
	 * @return string
	 */
	public function rewriteBase(): string {
		$abspath = str_replace( '\\', '/', ABSPATH );
		$subdir  = (string) preg_replace( '#^' . preg_quote( $this->homePath(), '#' ) . '#', '', $abspath );

		return '' !== $subdir ? ltrim( trailingslashit( $subdir ), '/' ) : '';
	}

	/**
	 * Returns whether the server is Apache with mod_rewrite, i.e. whether .htaccess is honoured.
	 *
	 * @return bool
	 */
	public function usesHtaccess(): bool {
		$this->loadIncludes();

		return got_mod_rewrite();
	}

	/**
	 * Returns whether a sub-domain network is possible (not localhost, an IP, or a site in a sub-folder).
	 *
	 * @return bool
	 */
	public function subdomainAllowed(): bool {
		$this->loadIncludes();

		return allow_subdomain_install();
	}

	/**
	 * Returns whether a sub-directory network is possible. WordPress disables it once
	 * the site has posts older than a month, because "/blog/" permalinks would collide.
	 *
	 * @return bool
	 */
	public function subdirectoryAllowed(): bool {
		$this->loadIncludes();

		return allow_subdirectory_install();
	}

	/**
	 * Resolves the requested network type against what the installation allows,
	 * using the same rules as core's Network Setup screen.
	 *
	 * @param bool $requestedSubdomain Whether the admin picked sub-domains.
	 * @return bool True for a sub-domain network.
	 */
	public function resolveSubdomainInstall( bool $requestedSubdomain ): bool {
		if ( ! $this->subdomainAllowed() ) {
			return false;
		}

		return $this->subdirectoryAllowed() ? $requestedSubdomain : true;
	}
}
