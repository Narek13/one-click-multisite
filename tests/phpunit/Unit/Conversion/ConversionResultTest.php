<?php
/**
 * Tests for ConversionResult.
 *
 * @package OneClickMultisite
 */

declare(strict_types=1);

namespace OneClickMultisite\Tests\Unit\Conversion;

use OneClickMultisite\Conversion\ConversionResult;
use OneClickMultisite\Tests\AbstractTestCase;

/**
 * Unit tests for the ConversionResult value object.
 */
class ConversionResultTest extends AbstractTestCase {

	/**
	 * Verifies that a successful result has an empty message.
	 *
	 * @test
	 */
	public function successResultHasNoMessage(): void {
		$result = new ConversionResult( true );

		self::assertTrue( $result->success() );
		self::assertSame( '', $result->message() );
	}

	/**
	 * Verifies that a failed result carries the provided message.
	 *
	 * @test
	 */
	public function failureResultCarriesMessage(): void {
		$result = new ConversionResult( false, 'Could not write wp-config.php.' );

		self::assertFalse( $result->success() );
		self::assertSame( 'Could not write wp-config.php.', $result->message() );
	}
}
