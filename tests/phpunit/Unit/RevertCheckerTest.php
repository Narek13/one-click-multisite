<?php

declare(strict_types=1);

namespace OneClickMultisite\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OneClickMultisite\Conversion\Environment;
use OneClickMultisite\Revert\RevertChecker;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the revert-to-single-site checks.
 *
 * ABSPATH points at a temp directory (see bootstrap); each test writes the
 * wp-config.php it needs there.
 */
final class RevertCheckerTest extends TestCase {

	/**
	 * The multisite block a conversion leaves in wp-config.php.
	 */
	private const NETWORK_CONFIG = "<?php\n/* Multisite. Added by One Click Multisite. */\ndefine( 'WP_ALLOW_MULTISITE', true );\ndefine( 'MULTISITE', true );\ndefine( 'SUBDOMAIN_INSTALL', false );\n\n/* That's all, stop editing! Happy publishing. */\nrequire_once ABSPATH . 'wp-settings.php';\n";

	/**
	 * Sets up Brain Monkey and stubs a revertible one-site Apache network.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$GLOBALS['wp_filesystem'] = new \WP_Filesystem_Base(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the file system global Environment reads.
		\WP_User::$rolesByUser    = array( 7 => array( 'administrator' ) );
		\WP_User_Query::$totals   = array(
			0 => 2,
			1 => 2,
		);

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( '_n' )->alias( static fn( string $single, string $plural, int $count ): string => 1 === $count ? $single : $plural );
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'get_sites' )->justReturn( 1 );
		Functions\when( 'get_networks' )->justReturn( 1 );
		Functions\when( 'get_main_site_id' )->justReturn( 1 );
		Functions\when( 'get_current_network_id' )->justReturn( 1 );
		Functions\when( 'get_site_option' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 7 );
		Functions\when( 'wp_is_writable' )->justReturn( true );
		Functions\when( 'get_filesystem_method' )->justReturn( 'direct' );
		Functions\when( 'WP_Filesystem' )->justReturn( true );
		Functions\when( 'allow_subdomain_install' )->justReturn( true );
		Functions\when( 'got_mod_rewrite' )->justReturn( true );
		Functions\when( 'get_home_path' )->justReturn( ABSPATH );
		Functions\when( 'get_option' )->justReturn( 'https://example.com' );
		Functions\when( 'trailingslashit' )->alias( static fn( string $value ): string => rtrim( $value, '/\\' ) . '/' );
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );

		$this->writeConfig( self::NETWORK_CONFIG );
	}

	/**
	 * Tears down Brain Monkey and the fixture after each test.
	 */
	protected function tearDown(): void {
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			unlink( ABSPATH . 'wp-config.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture.
		}
		unset( $GLOBALS['wp_filesystem'] );
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Writes the wp-config.php fixture.
	 *
	 * @param string $contents File contents.
	 */
	private function writeConfig( string $contents ): void {
		if ( ! is_dir( ABSPATH ) ) {
			mkdir( ABSPATH, 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture.
		}
		file_put_contents( ABSPATH . 'wp-config.php', $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture.
	}

	/**
	 * Builds a checker around a real environment.
	 */
	private function createChecker(): RevertChecker {
		return new RevertChecker( new Environment() );
	}

	/**
	 * Returns the labels of the checks that block the revert.
	 *
	 * @param RevertChecker $checker Checker.
	 * @return list<string>
	 */
	private function blocking( RevertChecker $checker ): array {
		$labels = array();
		foreach ( $checker->check() as $result ) {
			if ( $result->blocks() ) {
				$labels[] = $result->label();
			}
		}

		return $labels;
	}

	/**
	 * GIVEN a network holding only its main site, converted by this plugin
	 * WHEN the revert checks run
	 * THEN every check passes.
	 */
	public function testAllChecksPassForOneSiteNetwork(): void {
		$checker = $this->createChecker();

		foreach ( $checker->check() as $result ) {
			self::assertTrue( $result->passes(), $result->label() . ': ' . $result->message() );
		}
		self::assertTrue( $checker->canRevert() );
	}

	/**
	 * GIVEN a single site
	 * WHEN the revert checks run
	 * THEN the revert is blocked.
	 */
	public function testBlocksOnSingleSite(): void {
		Functions\when( 'is_multisite' )->justReturn( false );

		self::assertFalse( $this->createChecker()->canRevert() );
	}

	/**
	 * GIVEN a network with a second site
	 * WHEN the revert checks run
	 * THEN the revert is blocked, because that site's tables and uploads would be orphaned.
	 */
	public function testBlocksWhenOtherSitesExist(): void {
		Functions\when( 'get_sites' )->justReturn( 2 );

		self::assertSame( array( 'Only the main site exists' ), $this->blocking( $this->createChecker() ) );
	}

	/**
	 * GIVEN an installation with two networks
	 * WHEN the revert checks run
	 * THEN the revert is blocked.
	 */
	public function testBlocksWithMoreThanOneNetwork(): void {
		Functions\when( 'get_networks' )->justReturn( 2 );

		self::assertSame( array( 'Only the main site exists' ), $this->blocking( $this->createChecker() ) );
	}

	/**
	 * GIVEN a main site that is not site 1
	 * WHEN the revert checks run
	 * THEN the revert is blocked, because its tables do not use the single-site prefix.
	 */
	public function testBlocksWhenMainSiteIsNotSiteOne(): void {
		Functions\when( 'get_main_site_id' )->justReturn( 3 );
		\WP_User_Query::$totals[3] = 2;

		self::assertContains( 'Main site uses the single-site layout', $this->blocking( $this->createChecker() ) );
	}

	/**
	 * GIVEN a pre-3.5 network that still serves uploads through ms-files.php
	 * WHEN the revert checks run
	 * THEN the revert is blocked.
	 */
	public function testBlocksLegacyMsFilesNetwork(): void {
		Functions\when( 'get_site_option' )->alias( static fn( string $name ) => 'ms_files_rewriting' === $name ? '1' : false );

		self::assertSame( array( 'Main site uses the single-site layout' ), $this->blocking( $this->createChecker() ) );
	}

	/**
	 * GIVEN a super admin without the Administrator role on the main site
	 * WHEN the revert checks run
	 * THEN the revert is blocked, because they would be locked out.
	 */
	public function testBlocksSuperAdminWithoutSiteRole(): void {
		\WP_User::$rolesByUser = array( 7 => array() );

		self::assertSame( array( 'You stay an administrator' ), $this->blocking( $this->createChecker() ) );
	}

	/**
	 * GIVEN MULTISITE defined outside wp-config.php
	 * WHEN the revert checks run
	 * THEN the revert is blocked, since the plugin cannot switch it off.
	 */
	public function testBlocksWhenMultisiteIsDefinedElsewhere(): void {
		$this->writeConfig( "<?php\n// define( 'MULTISITE', true );\nrequire_once ABSPATH . 'wp-settings.php';\n" );

		self::assertSame( array( 'Multisite settings can be removed from wp-config.php' ), $this->blocking( $this->createChecker() ) );
	}

	/**
	 * GIVEN a multisite constant defined inside a condition
	 * WHEN the revert checks run
	 * THEN the revert is blocked instead of leaving a half-edited wp-config.php.
	 */
	public function testBlocksWhenAConstantCannotBeRemovedSafely(): void {
		$this->writeConfig( str_replace( "define( 'SUBDOMAIN_INSTALL', false );", "if ( true ) { define( 'SUBDOMAIN_INSTALL', false ); }", self::NETWORK_CONFIG ) );

		self::assertSame( array( 'Multisite settings can be removed from wp-config.php' ), $this->blocking( $this->createChecker() ) );
	}

	/**
	 * GIVEN a server that ignores .htaccess
	 * WHEN the revert checks run
	 * THEN it is a warning and the revert is still allowed.
	 */
	public function testWarnsWithoutHtaccessSupport(): void {
		Functions\when( 'got_mod_rewrite' )->justReturn( false );

		$checker = $this->createChecker();

		self::assertCount( 1, array_filter( $checker->check(), static fn( $result ) => $result->isWarning() ) );
		self::assertTrue( $checker->canRevert() );
	}

	/**
	 * GIVEN accounts that only belong to the network
	 * WHEN the revert checks run
	 * THEN it is a warning and the revert is still allowed.
	 */
	public function testWarnsAboutUsersWithoutRoleOnMainSite(): void {
		\WP_User_Query::$totals = array(
			0 => 5,
			1 => 2,
		);

		$checker  = $this->createChecker();
		$warnings = array_values( array_filter( $checker->check(), static fn( $result ) => $result->isWarning() ) );

		self::assertCount( 1, $warnings );
		self::assertStringStartsWith( '3 accounts have no role', $warnings[0]->message() );
		self::assertTrue( $checker->canRevert() );
	}
}
