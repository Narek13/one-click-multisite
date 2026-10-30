<?php

declare(strict_types=1);

namespace OneClickMultisite\Revert;

use OneClickMultisite\Conversion\Environment;
use OneClickMultisite\Conversion\PrerequisiteResult;
use OneClickMultisite\Conversion\WpConfigEditor;
use WP_User;

/**
 * Checks whether a network can be turned back into a single site without losing anything.
 *
 * Reverting is only safe while the network holds nothing but its main site:
 * every other site has its own tables and upload folder, which a single-site
 * install would silently orphan.
 */
final class RevertChecker {

	/**
	 * Constants a network defines in wp-config.php; all of them are removed on revert.
	 */
	public const CONSTANTS = array(
		'WP_ALLOW_MULTISITE',
		'MULTISITE',
		'SUBDOMAIN_INSTALL',
		'DOMAIN_CURRENT_SITE',
		'PATH_CURRENT_SITE',
		'SITE_ID_CURRENT_SITE',
		'BLOG_ID_CURRENT_SITE',
	);

	/**
	 * Memoized results for the current request.
	 *
	 * @var list<PrerequisiteResult>|null
	 */
	private ?array $results = null;

	/**
	 * Constructor.
	 *
	 * @param Environment    $environment Installation facts.
	 * @param WpConfigEditor $wpConfig    wp-config.php editor.
	 */
	public function __construct(
		private readonly Environment $environment,
		private readonly WpConfigEditor $wpConfig = new WpConfigEditor(),
	) {
	}

	/**
	 * Returns the installation facts this checker evaluates.
	 *
	 * @return Environment
	 */
	public function environment(): Environment {
		return $this->environment;
	}

	/**
	 * Runs all checks.
	 *
	 * @return list<PrerequisiteResult>
	 */
	public function check(): array {
		if ( null !== $this->results ) {
			return $this->results;
		}

		if ( ! is_multisite() ) {
			$this->results = array(
				new PrerequisiteResult(
					__( 'Multisite network', 'one-click-multisite' ),
					false,
					__( 'This site is not a multisite network.', 'one-click-multisite' )
				),
			);

			return $this->results;
		}

		$this->results = array(
			$this->checkOnlyMainSite(),
			$this->checkMainSiteLayout(),
			$this->checkAdministrator(),
			$this->checkFilesystemMethod(),
			$this->checkWpConfig(),
			$this->checkRewriteRules(),
			$this->checkUsers(),
		);

		return $this->results;
	}

	/**
	 * Returns true when no check blocks the revert (warnings are allowed).
	 *
	 * @return bool
	 */
	public function canRevert(): bool {
		foreach ( $this->check() as $result ) {
			if ( $result->blocks() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks that the network holds a single network with a single site.
	 *
	 * @return PrerequisiteResult
	 */
	private function checkOnlyMainSite(): PrerequisiteResult {
		$label    = __( 'Only the main site exists', 'one-click-multisite' );
		$sites    = (int) get_sites( array( 'count' => true ) );
		$networks = (int) get_networks( array( 'count' => true ) );

		if ( $networks > 1 ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'This installation runs more than one network. Reverting is only possible for a single network.', 'one-click-multisite' )
			);
		}

		if ( $sites > 1 ) {
			return new PrerequisiteResult(
				$label,
				false,
				sprintf(
					/* translators: %d: number of sites other than the main site. */
					_n(
						'The network has %d site besides the main site. Every site has its own tables and uploads, which a single site cannot use. Delete it under Sites (export its content first if you need it), then come back.',
						'The network has %d sites besides the main site. Every site has its own tables and uploads, which a single site cannot use. Delete them under Sites (export their content first if you need it), then come back.',
						$sites - 1,
						'one-click-multisite'
					),
					$sites - 1
				)
			);
		}

		return new PrerequisiteResult( $label, true );
	}

	/**
	 * Checks that the main site uses the single-site table prefix and uploads folder.
	 *
	 * @return PrerequisiteResult
	 */
	private function checkMainSiteLayout(): PrerequisiteResult {
		$label = __( 'Main site uses the single-site layout', 'one-click-multisite' );

		if ( 1 !== get_main_site_id() || 1 !== get_current_network_id() ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'The main site is not site 1 of network 1, so its tables do not use the single-site prefix.', 'one-click-multisite' )
			);
		}

		if ( get_site_option( 'ms_files_rewriting' ) ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'This network was created before WordPress 3.5 and serves uploads through ms-files.php. Those uploads are stored per site and would be lost on a single site.', 'one-click-multisite' )
			);
		}

		return new PrerequisiteResult( $label, true );
	}

	/**
	 * Checks that the current user stays an administrator after reverting.
	 *
	 * Super admin rights live in the network tables, which are removed. Only a
	 * real Administrator role on the main site carries over.
	 *
	 * @return PrerequisiteResult
	 */
	private function checkAdministrator(): PrerequisiteResult {
		$label = __( 'You stay an administrator', 'one-click-multisite' );
		$user  = new WP_User( get_current_user_id(), '', get_main_site_id() );

		if ( ! in_array( 'administrator', $user->roles, true ) ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'Your account has no Administrator role on the main site. Super admin rights disappear with the network, so you would be locked out. Give yourself the Administrator role on the main site first.', 'one-click-multisite' )
			);
		}

		return new PrerequisiteResult( $label, true );
	}

	/**
	 * Checks that WordPress can write files directly.
	 *
	 * @return PrerequisiteResult
	 */
	private function checkFilesystemMethod(): PrerequisiteResult {
		$label = __( 'Direct file system access', 'one-click-multisite' );

		if ( ! $this->environment->hasDirectFilesystem() ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'WordPress cannot write files directly on this server (it would need FTP or SSH credentials).', 'one-click-multisite' )
			);
		}

		return new PrerequisiteResult( $label, true );
	}

	/**
	 * Checks that wp-config.php is writable and that every multisite constant can be removed from it.
	 *
	 * @return PrerequisiteResult
	 */
	private function checkWpConfig(): PrerequisiteResult {
		$label  = __( 'Multisite settings can be removed from wp-config.php', 'one-click-multisite' );
		$config = $this->environment->wpConfigPath();

		if ( null === $config || ! wp_is_writable( $config ) ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'wp-config.php could not be located or is not writable.', 'one-click-multisite' )
			);
		}

		$filesystem = $this->environment->hasDirectFilesystem() ? $this->environment->filesystem() : null;
		$contents   = null !== $filesystem ? $filesystem->get_contents( $config ) : false;

		if ( ! is_string( $contents ) || '' === $contents ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'wp-config.php could not be read.', 'one-click-multisite' )
			);
		}

		if ( ! $this->wpConfig->defines( $contents, 'MULTISITE' ) ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'MULTISITE is not defined in wp-config.php itself (it may come from another included file or the hosting environment). Remove it there by hand.', 'one-click-multisite' )
			);
		}

		if ( null === $this->wpConfig->remove( $contents, self::CONSTANTS ) ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'Some multisite constants in wp-config.php are not plain one-line define() statements, so they cannot be removed safely. Remove them by hand.', 'one-click-multisite' )
			);
		}

		return new PrerequisiteResult( $label, true );
	}

	/**
	 * Checks that the single-site rewrite rules can be restored.
	 *
	 * @return PrerequisiteResult
	 */
	private function checkRewriteRules(): PrerequisiteResult {
		$label = __( 'Rewrite rules can be restored', 'one-click-multisite' );

		if ( ! $this->environment->usesHtaccess() ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'This server does not use .htaccess (for example nginx). After reverting, replace the multisite rewrite rules in your server configuration with the standard WordPress ones.', 'one-click-multisite' ),
				false
			);
		}

		$htaccess = $this->environment->htaccessPath();

		if ( file_exists( $htaccess ) ? ! wp_is_writable( $htaccess ) : ! wp_is_writable( dirname( $htaccess ) ) ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( '.htaccess is not writable. Please check the file permissions.', 'one-click-multisite' )
			);
		}

		return new PrerequisiteResult( $label, true );
	}

	/**
	 * Warns about accounts that only belong to the network, not to the main site.
	 *
	 * @return PrerequisiteResult
	 */
	private function checkUsers(): PrerequisiteResult {
		$label   = __( 'All users belong to the main site', 'one-click-multisite' );
		$outside = max( 0, $this->countUsers( 0 ) - $this->countUsers( get_main_site_id() ) );

		if ( $outside > 0 ) {
			return new PrerequisiteResult(
				$label,
				false,
				sprintf(
					/* translators: %d: number of user accounts. */
					_n(
						'%d account has no role on the main site. It is kept, but has no role after reverting.',
						'%d accounts have no role on the main site. They are kept, but have no role after reverting.',
						$outside,
						'one-click-multisite'
					),
					$outside
				),
				false
			);
		}

		return new PrerequisiteResult( $label, true );
	}

	/**
	 * Counts user accounts; get_user_count() is a cached network total and can be stale.
	 *
	 * @param int $siteId Site ID, or 0 for every account in the installation.
	 * @return int
	 */
	private function countUsers( int $siteId ): int {
		$query = new \WP_User_Query(
			array(
				'blog_id'     => $siteId,
				'fields'      => 'ID',
				'number'      => 1,
				'count_total' => true,
			)
		);

		return (int) $query->get_total();
	}
}
