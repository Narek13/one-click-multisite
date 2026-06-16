<?php

declare(strict_types=1);

namespace MultisiteAutoEnabler\Module;

interface ServiceModule
{
    /**
     * Returns an associative array of service id => callable factory.
     *
     * @return array<string, callable>
     */
    public function services(): array;
}
