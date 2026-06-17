<?php

declare(strict_types=1);

namespace OneClickMultisite\Conversion;

class PrerequisiteChecker
{
    /**
     * @return PrerequisiteResult[]
     */
    public function check(): array
    {
        return [
            $this->checkSingleSite(),
            $this->checkWpConfigWritable(),
            $this->checkHtaccessWritable(),
        ];
    }

    public function allPass(): bool
    {
        foreach ($this->check() as $result) {
            if (!$result->passes()) {
                return false;
            }
        }
        return true;
    }

    private function checkSingleSite(): PrerequisiteResult
    {
        $label = __('Single site installation', 'one-click-multisite');

        if (is_multisite()) {
            return new PrerequisiteResult(
                $label,
                false,
                __('This site is already running as a multisite network.', 'one-click-multisite')
            );
        }

        if (defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE) {
            return new PrerequisiteResult(
                $label,
                false,
                __('WP_ALLOW_MULTISITE is already defined. A previous conversion may be incomplete.', 'one-click-multisite')
            );
        }

        return new PrerequisiteResult($label, true);
    }

    private function checkWpConfigWritable(): PrerequisiteResult
    {
        $label  = __('wp-config.php is writable', 'one-click-multisite');
        $config = $this->findWpConfig();

        if ($config === null) {
            return new PrerequisiteResult(
                $label,
                false,
                __('wp-config.php could not be located.', 'one-click-multisite')
            );
        }

        if (!is_writable($config)) {
            return new PrerequisiteResult(
                $label,
                false,
                __('wp-config.php is not writable. Please check file permissions.', 'one-click-multisite')
            );
        }

        return new PrerequisiteResult($label, true);
    }

    private function checkHtaccessWritable(): PrerequisiteResult
    {
        $label    = __('.htaccess is writable', 'one-click-multisite');
        $htaccess = ABSPATH . '.htaccess';

        if (file_exists($htaccess) && !is_writable($htaccess)) {
            return new PrerequisiteResult(
                $label,
                false,
                __('.htaccess exists but is not writable. Please check file permissions.', 'one-click-multisite')
            );
        }

        if (!file_exists($htaccess) && !is_writable(ABSPATH)) {
            return new PrerequisiteResult(
                $label,
                false,
                __('The WordPress root directory is not writable. Cannot create .htaccess.', 'one-click-multisite')
            );
        }

        return new PrerequisiteResult($label, true);
    }

    public function findWpConfig(): ?string
    {
        $locations = [
            ABSPATH . 'wp-config.php',
            dirname(ABSPATH) . '/wp-config.php',
        ];

        foreach ($locations as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
