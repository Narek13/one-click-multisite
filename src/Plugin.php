<?php

declare(strict_types=1);

namespace MultisiteAutoEnabler;

use MultisiteAutoEnabler\Module\ExecutableModule;
use MultisiteAutoEnabler\Module\ServiceModule;

class Plugin
{
    private PluginProperties $properties;
    private Container $container;

    /** @var array<ServiceModule|ExecutableModule> */
    private array $modules = [];

    private function __construct(PluginProperties $properties)
    {
        $this->properties = $properties;
        $this->container  = new Container();
    }

    public static function new(PluginProperties $properties): self
    {
        return new self($properties);
    }

    /**
     * @param ServiceModule|ExecutableModule ...$modules
     */
    public function addModule(...$modules): self
    {
        foreach ($modules as $module) {
            $this->modules[] = $module;
        }
        return $this;
    }

    public function boot(): bool
    {
        $this->container->bind(PluginProperties::class, function (): PluginProperties {
            return $this->properties;
        });

        foreach ($this->modules as $module) {
            if ($module instanceof ServiceModule) {
                foreach ($module->services() as $id => $factory) {
                    $this->container->bind($id, $factory);
                }
            }
        }

        foreach ($this->modules as $module) {
            if ($module instanceof ExecutableModule) {
                $module->run($this->container);
            }
        }

        return true;
    }
}
