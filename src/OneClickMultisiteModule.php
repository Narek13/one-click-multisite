<?php

declare(strict_types=1);

namespace OneClickMultisite;

use OneClickMultisite\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class OneClickMultisiteModule implements ServiceModule
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
            'one-click-multisite.plugin-basename' => static function () use ($pluginFile): string {
                return plugin_basename($pluginFile);
            },
            'one-click-multisite.plugin-url' => static function () use ($pluginFile): string {
                return plugin_dir_url($pluginFile);
            },
            'one-click-multisite.plugin-version' => static function () use ($pluginFile): string {
                $data = get_file_data($pluginFile, ['Version' => 'Version']);
                return $data['Version'] ?? '1.0.0';
            },
        ];
    }
}
