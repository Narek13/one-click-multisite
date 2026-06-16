<?php

declare(strict_types=1);

namespace MultisiteAutoEnabler\Admin;

use MultisiteAutoEnabler\Conversion\MultisiteConverter;
use MultisiteAutoEnabler\Conversion\PrerequisiteChecker;
use MultisiteAutoEnabler\Module\ExecutableModule;
use MultisiteAutoEnabler\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class AdminModule implements ServiceModule, ExecutableModule
{
    public function services(): array
    {
        return [
            ToolsPage::class => static function (ContainerInterface $c): ToolsPage {
                return new ToolsPage($c->get(PrerequisiteChecker::class));
            },
            ConversionController::class => static function (ContainerInterface $c): ConversionController {
                return new ConversionController($c->get(MultisiteConverter::class));
            },
        ];
    }

    public function run(ContainerInterface $container): bool
    {
        $page       = $container->get(ToolsPage::class);
        $controller = $container->get(ConversionController::class);
        $basename   = $container->get('multisite-auto-enabler.plugin-basename');
        $pluginUrl  = $container->get('multisite-auto-enabler.plugin-url');
        $version    = $container->get('multisite-auto-enabler.plugin-version');

        add_action('admin_menu', [$page, 'register']);

        add_action(
            'admin_enqueue_scripts',
            static function (string $hookSuffix) use ($pluginUrl, $version): void {
                if ($hookSuffix !== 'tools_page_multisite-auto-enabler') {
                    return;
                }
                wp_enqueue_style(
                    'multisite-auto-enabler',
                    $pluginUrl . 'assets/css/admin.css',
                    [],
                    $version
                );
            }
        );

        add_filter(
            'plugin_action_links_' . $basename,
            static function (array $links): array {
                $url   = admin_url('tools.php?page=multisite-auto-enabler');
                $label = __('Convert to Multisite', 'multisite-auto-enabler');
                $link  = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
                return array_merge(['mae-settings' => $link], $links);
            }
        );

        add_action('admin_post_multisite_auto_enabler_convert', [$controller, 'handle']);

        return true;
    }
}
