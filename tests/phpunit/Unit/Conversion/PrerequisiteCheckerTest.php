<?php

declare(strict_types=1);

namespace MultisiteAutoEnabler\Tests\Unit\Conversion;

use MultisiteAutoEnabler\Conversion\PrerequisiteChecker;
use MultisiteAutoEnabler\Conversion\PrerequisiteResult;
use MultisiteAutoEnabler\Tests\AbstractTestCase;
use Brain\Monkey\Functions;

class PrerequisiteCheckerTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function checkReturnsThreePrerequisiteResults(): void
    {
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('__')->returnArg(1);

        $checker = new PrerequisiteChecker();
        $results = $checker->check();

        self::assertCount(3, $results);
        foreach ($results as $result) {
            self::assertInstanceOf(PrerequisiteResult::class, $result);
        }
    }

    /**
     * @test
     */
    public function allPassReturnsFalseWhenAlreadyMultisite(): void
    {
        Functions\when('is_multisite')->justReturn(true);
        Functions\when('__')->returnArg(1);

        $checker = new PrerequisiteChecker();

        self::assertFalse($checker->allPass());
    }

    /**
     * @test
     */
    public function findWpConfigReturnsNullWhenNoConfigExists(): void
    {
        Functions\when('is_multisite')->justReturn(false);
        Functions\when('__')->returnArg(1);

        $checker = new PrerequisiteChecker();

        // ABSPATH is set to a temp directory that has no wp-config.php in tests.
        self::assertNull($checker->findWpConfig());
    }
}
