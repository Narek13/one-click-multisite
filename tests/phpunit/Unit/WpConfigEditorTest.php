<?php

declare(strict_types=1);

namespace OneClickMultisite\Tests\Unit;

use OneClickMultisite\Conversion\WpConfigEditor;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the wp-config.php constant inserter.
 */
final class WpConfigEditorTest extends TestCase {

	/**
	 * A stock wp-config.php tail, as wp-config-sample.php ships it.
	 */
	private const SAMPLE = "<?php\ndefine( 'DB_NAME', 'wp' );\n\n/* That's all, stop editing! Happy publishing. */\n\nif ( ! defined( 'ABSPATH' ) ) {\n\tdefine( 'ABSPATH', __DIR__ . '/' );\n}\n\nrequire_once ABSPATH . 'wp-settings.php';\n";

	/**
	 * GIVEN a stock wp-config.php
	 * WHEN constants are inserted
	 * THEN they appear directly above the "stop editing" line.
	 */
	public function testInsertsAboveStopEditingMarker(): void {
		$result = ( new WpConfigEditor() )->insert( self::SAMPLE, array( 'MULTISITE' => true ) );

		self::assertIsString( $result );
		self::assertStringContainsString( "define( 'MULTISITE', true );\n\n/* That's all, stop editing!", $result );
		self::assertLessThan( strpos( $result, 'stop editing' ), strpos( $result, "'MULTISITE'" ) );
	}

	/**
	 * GIVEN a wp-config.php without the "stop editing" line
	 * WHEN constants are inserted
	 * THEN they go above the wp-settings.php include, never after it.
	 */
	public function testFallsBackToAboveWpSettingsInclude(): void {
		$contents = "<?php\ndefine( 'DB_NAME', 'wp' );\nrequire_once( ABSPATH . '/wp-settings.php' );\n";

		$result = ( new WpConfigEditor() )->insert( $contents, array( 'MULTISITE' => true ) );

		self::assertIsString( $result );
		self::assertLessThan( strpos( $result, 'wp-settings.php' ), strpos( $result, "'MULTISITE'" ) );
	}

	/**
	 * GIVEN a wp-config.php that loads wp-settings.php inside a file_exists() guard
	 * WHEN constants are inserted
	 * THEN they go above the guard, not inside it.
	 */
	public function testFallsBackToAboveGuardedWpSettingsInclude(): void {
		$contents = "<?php\nif ( file_exists( ABSPATH . '/wp-settings.php' ) ) {\n\trequire_once ABSPATH . '/wp-settings.php';\n}\n";

		$result = ( new WpConfigEditor() )->insert( $contents, array( 'MULTISITE' => true ) );

		self::assertIsString( $result );
		self::assertStringStartsWith( "<?php\n/* Multisite.", $result );
	}

	/**
	 * GIVEN a wp-config.php with neither the marker nor a wp-settings.php include
	 * WHEN constants are inserted
	 * THEN null is returned instead of guessing a location.
	 */
	public function testReturnsNullWithoutInsertionPoint(): void {
		self::assertNull( ( new WpConfigEditor() )->insert( "<?php\ndefine( 'DB_NAME', 'wp' );\n", array( 'MULTISITE' => true ) ) );
	}

	/**
	 * GIVEN a wp-config.php that already defines some of the constants
	 * WHEN constants are inserted
	 * THEN only the missing ones are added, so define() never runs twice.
	 */
	public function testSkipsConstantsThatAreAlreadyDefined(): void {
		$contents = str_replace( '/* That', "define(\"WP_ALLOW_MULTISITE\", true);\n/* That", self::SAMPLE );

		$result = ( new WpConfigEditor() )->insert(
			$contents,
			array(
				'WP_ALLOW_MULTISITE' => true,
				'MULTISITE'          => true,
			)
		);

		self::assertIsString( $result );
		self::assertSame( 1, substr_count( $result, 'WP_ALLOW_MULTISITE' ) );
		self::assertSame( 1, substr_count( $result, "'MULTISITE'" ) );
	}

	/**
	 * GIVEN every constant is already defined
	 * WHEN constants are inserted
	 * THEN the contents are returned unchanged.
	 */
	public function testLeavesContentsUnchangedWhenNothingIsMissing(): void {
		$contents = str_replace( '/* That', "define( 'MULTISITE', true );\n/* That", self::SAMPLE );

		self::assertSame( $contents, ( new WpConfigEditor() )->insert( $contents, array( 'MULTISITE' => true ) ) );
	}

	/**
	 * GIVEN a value containing a quote and a backslash
	 * WHEN constants are inserted
	 * THEN the value is escaped and the result is still valid PHP.
	 */
	public function testEscapesValuesSafely(): void {
		$result = ( new WpConfigEditor() )->insert( self::SAMPLE, array( 'PATH_CURRENT_SITE' => "/it's\\/" ) );

		self::assertIsString( $result );
		self::assertStringContainsString( "define( 'PATH_CURRENT_SITE', '/it\\'s\\\\/' );", $result );
	}

	/**
	 * GIVEN boolean and integer values
	 * WHEN constants are inserted
	 * THEN they are written as PHP literals, not strings.
	 */
	public function testWritesTypedLiterals(): void {
		$result = ( new WpConfigEditor() )->insert(
			self::SAMPLE,
			array(
				'SUBDOMAIN_INSTALL'    => false,
				'SITE_ID_CURRENT_SITE' => 1,
			)
		);

		self::assertIsString( $result );
		self::assertStringContainsString( "define( 'SUBDOMAIN_INSTALL', false );", $result );
		self::assertStringContainsString( "define( 'SITE_ID_CURRENT_SITE', 1 );", $result );
	}
}
