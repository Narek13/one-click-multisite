<?php
/**
 * Abstract base test case.
 *
 * @package OneClickMultisite
 */

declare(strict_types=1);

namespace OneClickMultisite\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Base class for all plugin unit tests.
 */
abstract class AbstractTestCase extends TestCase {

	use MockeryPHPUnitIntegration;

	/**
	 * Sets up Brain\Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tears down Brain\Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
