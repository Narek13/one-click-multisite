<?php

declare(strict_types=1);

$pluginRoot = dirname(__DIR__, 2);

// Patchwork must be required before the autoloader loads any file it may need to patch.
require_once $pluginRoot . '/vendor/antecedent/patchwork/Patchwork.php';
require_once $pluginRoot . '/vendor/autoload.php';

// Minimal WordPress constants required by production code.
if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/wordpress/');
}
