<?php

declare(strict_types=1);

namespace OneClickMultisite;

class PluginProperties
{
    private string $pluginFile;

    private function __construct(string $pluginFile)
    {
        $this->pluginFile = $pluginFile;
    }

    public static function new(string $pluginFile): self
    {
        return new self($pluginFile);
    }

    public function pluginFile(): string
    {
        return $this->pluginFile;
    }

    public function basename(): string
    {
        return plugin_basename($this->pluginFile);
    }

    public function url(): string
    {
        return plugin_dir_url($this->pluginFile);
    }

    public function dir(): string
    {
        return plugin_dir_path($this->pluginFile);
    }

    public function version(): string
    {
        $data = get_file_data($this->pluginFile, ['Version' => 'Version']);
        return $data['Version'] ?? '1.0.0';
    }
}
