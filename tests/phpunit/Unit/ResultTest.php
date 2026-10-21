<?php

declare(strict_types=1);

namespace OneClickMultisite\Tests\Unit;

use OneClickMultisite\Conversion\ConversionResult;
use OneClickMultisite\Conversion\PrerequisiteResult;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the prerequisite and conversion result value objects.
 */
final class ResultTest extends TestCase {

	/**
	 * GIVEN a passing prerequisite
	 * WHEN it is inspected
	 * THEN it neither blocks nor warns.
	 */
	public function testPassingPrerequisiteNeitherBlocksNorWarns(): void {
		$result = new PrerequisiteResult( 'Single site', true );

		self::assertTrue( $result->passes() );
		self::assertFalse( $result->blocks() );
		self::assertFalse( $result->isWarning() );
		self::assertSame( 'Single site', $result->label() );
		self::assertSame( '', $result->message() );
	}

	/**
	 * GIVEN a failing prerequisite
	 * WHEN it is inspected
	 * THEN it blocks by default and carries its message.
	 */
	public function testFailingPrerequisiteBlocksByDefault(): void {
		$result = new PrerequisiteResult( 'wp-config.php is writable', false, 'Not writable.' );

		self::assertTrue( $result->blocks() );
		self::assertFalse( $result->isWarning() );
		self::assertSame( 'Not writable.', $result->message() );
	}

	/**
	 * GIVEN a failing, non-blocking prerequisite
	 * WHEN it is inspected
	 * THEN it is a warning that does not block.
	 */
	public function testNonBlockingFailureIsAWarning(): void {
		$result = new PrerequisiteResult( 'Rewrite rules', false, 'nginx', false );

		self::assertFalse( $result->blocks() );
		self::assertTrue( $result->isWarning() );
	}

	/**
	 * GIVEN a successful conversion with follow-up notes
	 * WHEN it is inspected
	 * THEN it succeeded, has no error and keeps the notes.
	 */
	public function testSuccessKeepsWarnings(): void {
		$result = ConversionResult::success( array( 'Wildcard DNS may not be configured.' ) );

		self::assertTrue( $result->succeeded() );
		self::assertSame( '', $result->message() );
		self::assertSame( array( 'Wildcard DNS may not be configured.' ), $result->warnings() );
	}

	/**
	 * GIVEN a failed conversion
	 * WHEN it is inspected
	 * THEN it carries the error message and no warnings.
	 */
	public function testFailureCarriesMessage(): void {
		$result = ConversionResult::failure( 'Could not write to wp-config.php.' );

		self::assertFalse( $result->succeeded() );
		self::assertSame( 'Could not write to wp-config.php.', $result->message() );
		self::assertSame( array(), $result->warnings() );
	}
}
