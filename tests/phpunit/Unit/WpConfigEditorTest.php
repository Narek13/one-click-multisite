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

	/**
	 * GIVEN a commented-out define, as multisite tutorials often leave behind
	 * WHEN constants are inserted
	 * THEN the constant counts as missing and is added for real.
	 */
	public function testIgnoresCommentedOutDefines(): void {
		$contents = str_replace( '/* That', "// define( 'MULTISITE', true );\n# define( 'SUBDOMAIN_INSTALL', true );\n/* That", self::SAMPLE );

		$result = ( new WpConfigEditor() )->insert(
			$contents,
			array(
				'MULTISITE'         => true,
				'SUBDOMAIN_INSTALL' => false,
			)
		);

		self::assertIsString( $result );
		self::assertStringContainsString( "\ndefine( 'MULTISITE', true );", $result );
		self::assertStringContainsString( "\ndefine( 'SUBDOMAIN_INSTALL', false );", $result );
	}

	/**
	 * GIVEN a wp-config.php converted by this plugin
	 * WHEN the multisite constants are removed
	 * THEN the result is the original file again.
	 */
	public function testRemoveUndoesInsert(): void {
		$editor    = new WpConfigEditor();
		$constants = array(
			'WP_ALLOW_MULTISITE'   => true,
			'MULTISITE'            => true,
			'SUBDOMAIN_INSTALL'    => false,
			'DOMAIN_CURRENT_SITE'  => 'example.com',
			'PATH_CURRENT_SITE'    => '/',
			'SITE_ID_CURRENT_SITE' => 1,
			'BLOG_ID_CURRENT_SITE' => 1,
		);

		$converted = $editor->insert( self::SAMPLE, $constants );

		self::assertIsString( $converted );
		self::assertSame( self::SAMPLE, $editor->remove( $converted, array_keys( $constants ) ) );
	}

	/**
	 * GIVEN constants added by hand, with different quoting, spacing and a trailing comment
	 * WHEN they are removed
	 * THEN every one of them is gone and the rest of the file is untouched.
	 */
	public function testRemoveHandlesHandWrittenDefines(): void {
		$contents = str_replace( '/* That', "define(\"MULTISITE\",true); // network\n  define( 'PATH_CURRENT_SITE' , '/' ) ;\n/* That", self::SAMPLE );

		self::assertSame( self::SAMPLE, ( new WpConfigEditor() )->remove( $contents, array( 'MULTISITE', 'PATH_CURRENT_SITE' ) ) );
	}

	/**
	 * GIVEN a constant defined inside a condition
	 * WHEN the constants are removed
	 * THEN null is returned instead of a half-edited file.
	 */
	public function testRemoveRefusesDefinesItCannotRemoveSafely(): void {
		$contents = str_replace( '/* That', "if ( ! defined( 'MULTISITE' ) ) { define( 'MULTISITE', true ); }\n/* That", self::SAMPLE );

		self::assertNull( ( new WpConfigEditor() )->remove( $contents, array( 'MULTISITE' ) ) );
	}

	/**
	 * GIVEN a commented-out define
	 * WHEN the constants are removed
	 * THEN it is left alone and does not block the removal.
	 */
	public function testRemoveLeavesCommentedDefinesAlone(): void {
		$contents = str_replace( '/* That', "// define( 'MULTISITE', true );\n/* That", self::SAMPLE );

		self::assertSame( $contents, ( new WpConfigEditor() )->remove( $contents, array( 'MULTISITE' ) ) );
	}
}
