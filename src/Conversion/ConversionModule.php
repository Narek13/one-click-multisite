<?php

declare(strict_types=1);

namespace OneClickMultisite\Conversion;

use OneClickMultisite\Module\ServiceModule;

class ConversionModule implements ServiceModule
{
    public function services(): array
    {
        return [
            PrerequisiteChecker::class => static function (): PrerequisiteChecker {
                return new PrerequisiteChecker();
            },
            MultisiteConverter::class => static function (\Psr\Container\ContainerInterface $c): MultisiteConverter {
                return new MultisiteConverter($c->get(PrerequisiteChecker::class));
            },
        ];
    }
}
