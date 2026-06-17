<?php

declare(strict_types=1);

namespace OneClickMultisite\Tests\Unit\Conversion;

use OneClickMultisite\Conversion\ConversionResult;
use OneClickMultisite\Tests\AbstractTestCase;

class ConversionResultTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function successResultHasNoMessage(): void
    {
        $result = new ConversionResult(true);

        self::assertTrue($result->success());
        self::assertSame('', $result->message());
    }

    /**
     * @test
     */
    public function failureResultCarriesMessage(): void
    {
        $result = new ConversionResult(false, 'Could not write wp-config.php.');

        self::assertFalse($result->success());
        self::assertSame('Could not write wp-config.php.', $result->message());
    }
}
