<?php

declare(strict_types=1);

namespace MultisiteAutoEnabler;

use MultisiteAutoEnabler\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class MultisiteAutoEnablerModule implements ServiceModule
{
    private string $pluginFile;

    public function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;
    }

    public function services(): array
    {
        $pluginFile = $this->pluginFile;

        return [
            'multisite-auto-enabler.plugin-basename' => static function () use ($pluginFile): string {
                return plugin_basename($pluginFile);
            },
            'multisite-auto-enabler.plugin-url' => static function () use ($pluginFile): string {
                return plugin_dir_url($pluginFile);
            },
            'multisite-auto-enabler.plugin-version' => static function () use ($pluginFile): string {
                $data = get_file_data($pluginFile, ['Version' => 'Version']);
                return $data['Version'] ?? '1.0.0';
            },
        ];
    }
}
