<?php

declare(strict_types=1);

namespace OneClickMultisite\Tests\Unit\Conversion;

use OneClickMultisite\Conversion\PrerequisiteResult;
use OneClickMultisite\Tests\AbstractTestCase;

class PrerequisiteResultTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function passingResultHasNoMessage(): void
    {
        $result = new PrerequisiteResult('Single site', true);

        self::assertTrue($result->passes());
        self::assertSame('Single site', $result->label());
        self::assertSame('', $result->message());
    }

    /**
     * @test
     */
    public function failingResultCarriesMessage(): void
    {
        $result = new PrerequisiteResult('wp-config.php writable', false, 'File is not writable.');

        self::assertFalse($result->passes());
        self::assertSame('wp-config.php writable', $result->label());
        self::assertSame('File is not writable.', $result->message());
    }

    /**
     * @test
     */
    public function failingResultWithoutMessageIsAllowed(): void
    {
        $result = new PrerequisiteResult('Label', false);

        self::assertFalse($result->passes());
        self::assertSame('', $result->message());
    }
}
