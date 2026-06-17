<?php

declare(strict_types=1);

namespace OneClickMultisite\Module;

use Psr\Container\ContainerInterface;

interface ExecutableModule
{
    public function run(ContainerInterface $container): bool;
}
