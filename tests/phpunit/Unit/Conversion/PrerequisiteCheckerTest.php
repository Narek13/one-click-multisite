<?php
/**
 * Tests for PrerequisiteChecker.
 *
 * @package OneClickMultisite
 */

declare(strict_types=1);

namespace OneClickMultisite\Tests\Unit\Conversion;

use OneClickMultisite\Conversion\PrerequisiteChecker;
use OneClickMultisite\Conversion\PrerequisiteResult;
use OneClickMultisite\Tests\AbstractTestCase;
use Brain\Monkey\Functions;

/**
 * Unit tests for PrerequisiteChecker.
 */
class PrerequisiteCheckerTest extends AbstractTestCase {

	/**
	 * Verifies that check() returns exactly three PrerequisiteResult instances.
	 *
	 * @test
	 */
	public function checkReturnsThreePrerequisiteResults(): void {
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( '__' )->returnArg( 1 );

		$checker = new PrerequisiteChecker();
		$results = $checker->check();

		self::assertCount( 3, $results );
		foreach ( $results as $result ) {
			self::assertInstanceOf( PrerequisiteResult::class, $result );
		}
	}

	/**
	 * Verifies that all_pass() returns false when the site is already multisite.
	 *
	 * @test
	 */
	public function allPassReturnsFalseWhenAlreadyMultisite(): void {
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( '__' )->returnArg( 1 );

		$checker = new PrerequisiteChecker();

		self::assertFalse( $checker->all_pass() );
	}

	/**
	 * Verifies that find_wp_config() returns null when no wp-config.php exists.
	 *
	 * @test
	 */
	public function findWpConfigReturnsNullWhenNoConfigExists(): void {
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( '__' )->returnArg( 1 );

		$checker = new PrerequisiteChecker();

		// ABSPATH is set to a temp directory that has no wp-config.php in tests.
		self::assertNull( $checker->find_wp_config() );
	}
}
