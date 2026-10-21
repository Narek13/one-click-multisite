<?php

declare(strict_types=1);

namespace OneClickMultisite\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use OneClickMultisite\Conversion\Environment;
use OneClickMultisite\Conversion\PrerequisiteChecker;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the conversion prerequisites.
 *
 * ABSPATH points at an empty temp directory (see bootstrap), so wp-config.php
 * is never found unless a test creates one.
 */
final class PrerequisiteCheckerTest extends TestCase {

	/**
	 * Temporary WordPress root used by tests that need a wp-config.php.
	 *
	 * @var string
	 */
	private string $root = ABSPATH;

	/**
	 * Sets up Brain Monkey and stubs a healthy single-site Apache install.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'wp_is_writable' )->justReturn( true );
		Functions\when( 'get_filesystem_method' )->justReturn( 'direct' );
		Functions\when( 'get_clean_basedomain' )->justReturn( 'example.com' );
		Functions\when( 'allow_subdomain_install' )->justReturn( true );
		Functions\when( 'allow_subdirectory_install' )->justReturn( true );
		Functions\when( 'got_mod_rewrite' )->justReturn( true );
		Functions\when( 'get_home_path' )->justReturn( $this->root );
		Functions\when( 'get_option' )->justReturn( 'https://example.com' );
		Functions\when( 'trailingslashit' )->alias( static fn( string $value ): string => rtrim( $value, '/\\' ) . '/' );
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );

		if ( ! is_dir( $this->root ) ) {
			mkdir( $this->root, 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture.
		}
		file_put_contents( $this->root . 'wp-config.php', "<?php\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture.
	}

	/**
	 * Tears down Brain Monkey and the fixture after each test.
	 */
	protected function tearDown(): void {
		if ( file_exists( $this->root . 'wp-config.php' ) ) {
			unlink( $this->root . 'wp-config.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture.
		}
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Builds a checker around a real environment.
	 */
	private function createChecker(): PrerequisiteChecker {
		return new PrerequisiteChecker( new Environment() );
	}

	/**
	 * GIVEN a healthy single-site Apache install
	 * WHEN the prerequisites are checked
	 * THEN every check passes and the conversion is allowed.
	 */
	public function testAllChecksPassOnHealthyInstall(): void {
		$checker = $this->createChecker();

		foreach ( $checker->check() as $result ) {
			self::assertTrue( $result->passes(), $result->label() . ': ' . $result->message() );
		}
		self::assertTrue( $checker->canConvert() );
	}

	/**
	 * GIVEN a site that is already a network
	 * WHEN the prerequisites are checked
	 * THEN the conversion is blocked.
	 */
	public function testBlocksWhenAlreadyMultisite(): void {
		Functions\when( 'is_multisite' )->justReturn( true );

		self::assertFalse( $this->createChecker()->canConvert() );
	}

	/**
	 * GIVEN no wp-config.php in ABSPATH or its parent
	 * WHEN the prerequisites are checked
	 * THEN the conversion is blocked.
	 */
	public function testBlocksWhenWpConfigIsMissing(): void {
		unlink( $this->root . 'wp-config.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture.

		self::assertFalse( $this->createChecker()->canConvert() );
	}

	/**
	 * GIVEN WordPress would need FTP credentials to write files
	 * WHEN the prerequisites are checked
	 * THEN the conversion is blocked.
	 */
	public function testBlocksWithoutDirectFilesystemAccess(): void {
		Functions\when( 'get_filesystem_method' )->justReturn( 'ftpext' );

		self::assertFalse( $this->createChecker()->canConvert() );
	}

	/**
	 * GIVEN a site address with a non-standard port
	 * WHEN the prerequisites are checked
	 * THEN the conversion is blocked, as core's Network Setup refuses it too.
	 */
	public function testBlocksOnNonStandardPort(): void {
		Functions\when( 'get_clean_basedomain' )->justReturn( 'example.com:8080' );

		self::assertFalse( $this->createChecker()->canConvert() );
	}

	/**
	 * GIVEN a standard HTTPS port in the site address
	 * WHEN the prerequisites are checked
	 * THEN it is accepted.
	 */
	public function testAllowsStandardPort(): void {
		Functions\when( 'get_clean_basedomain' )->justReturn( 'example.com:443' );

		self::assertTrue( $this->createChecker()->canConvert() );
	}

	/**
	 * GIVEN neither network type is allowed by WordPress
	 * WHEN the prerequisites are checked
	 * THEN the conversion is blocked.
	 */
	public function testBlocksWhenNoNetworkTypeIsAllowed(): void {
		Functions\when( 'allow_subdomain_install' )->justReturn( false );
		Functions\when( 'allow_subdirectory_install' )->justReturn( false );

		self::assertFalse( $this->createChecker()->canConvert() );
	}

	/**
	 * GIVEN only one network type is allowed
	 * WHEN the prerequisites are checked
	 * THEN it is a warning and the conversion is still allowed.
	 */
	public function testWarnsWhenOnlyOneNetworkTypeIsAllowed(): void {
		Functions\when( 'allow_subdomain_install' )->justReturn( false );

		$checker  = $this->createChecker();
		$warnings = array_filter( $checker->check(), static fn( $result ) => $result->isWarning() );

		self::assertCount( 1, $warnings );
		self::assertTrue( $checker->canConvert() );
	}

	/**
	 * GIVEN a server that ignores .htaccess (nginx)
	 * WHEN the prerequisites are checked
	 * THEN it is a warning and the conversion is still allowed.
	 */
	public function testWarnsWithoutHtaccessSupport(): void {
		Functions\when( 'got_mod_rewrite' )->justReturn( false );

		$checker  = $this->createChecker();
		$warnings = array_filter( $checker->check(), static fn( $result ) => $result->isWarning() );

		self::assertCount( 1, $warnings );
		self::assertTrue( $checker->canConvert() );
	}

	/**
	 * GIVEN an Apache install whose .htaccess cannot be created
	 * WHEN the prerequisites are checked
	 * THEN the conversion is blocked.
	 */
	public function testBlocksWhenHtaccessCannotBeWritten(): void {
		Functions\when( 'wp_is_writable' )->alias( static fn( string $path ): bool => str_ends_with( $path, 'wp-config.php' ) );

		self::assertFalse( $this->createChecker()->canConvert() );
	}
}
