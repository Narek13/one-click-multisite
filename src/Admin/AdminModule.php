<?php

declare(strict_types=1);

namespace OneClickMultisite\Admin;

use OneClickMultisite\Conversion\MultisiteConverter;
use OneClickMultisite\Conversion\PrerequisiteChecker;
use OneClickMultisite\Module\ExecutableModule;
use OneClickMultisite\Module\ServiceModule;
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
        $basename   = $container->get('one-click-multisite.plugin-basename');
        $pluginUrl  = $container->get('one-click-multisite.plugin-url');
        $version    = $container->get('one-click-multisite.plugin-version');

        add_action('admin_menu', [$page, 'register']);

        add_action(
            'admin_enqueue_scripts',
            static function (string $hookSuffix) use ($pluginUrl, $version): void {
                if ($hookSuffix !== 'tools_page_one-click-multisite') {
                    return;
                }
                wp_enqueue_style(
                    'one-click-multisite',
                    $pluginUrl . 'assets/css/admin.css',
                    [],
                    $version
                );
            }
        );

        add_filter(
            'plugin_action_links_' . $basename,
            static function (array $links): array {
                $url   = admin_url('tools.php?page=one-click-multisite');
                $label = __('Convert to Multisite', 'one-click-multisite');
                $link  = '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
                return array_merge(['ocm-settings' => $link], $links);
            }
        );

        add_action('admin_post_one_click_multisite_convert', [$controller, 'handle']);

        return true;
    }
}
