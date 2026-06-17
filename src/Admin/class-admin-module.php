<?php
/**
 * Admin module.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite\Admin;

use OneClickMultisite\Conversion\MultisiteConverter;
use OneClickMultisite\Conversion\PrerequisiteChecker;
use OneClickMultisite\Module\ExecutableModule;
use OneClickMultisite\Module\ServiceModule;
use Psr\Container\ContainerInterface;

/**
 * Registers admin services and wires up all admin hooks.
 */
class AdminModule implements ServiceModule, ExecutableModule {

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string, callable>
	 */
	public function services(): array {
		return array(
			ToolsPage::class            => static function ( ContainerInterface $c ): ToolsPage {
				return new ToolsPage( $c->get( PrerequisiteChecker::class ) );
			},
			ConversionController::class => static function ( ContainerInterface $c ): ConversionController {
				return new ConversionController( $c->get( MultisiteConverter::class ) );
			},
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param ContainerInterface $container The service container.
	 * @return bool True on success.
	 */
	public function run( ContainerInterface $container ): bool {
		$page       = $container->get( ToolsPage::class );
		$controller = $container->get( ConversionController::class );
		$basename   = $container->get( 'one-click-multisite.plugin-basename' );
		$plugin_url = $container->get( 'one-click-multisite.plugin-url' );
		$version    = $container->get( 'one-click-multisite.plugin-version' );

		add_action( 'admin_menu', array( $page, 'register' ) );

		add_action(
			'admin_enqueue_scripts',
			static function ( string $hook_suffix ) use ( $plugin_url, $version ): void {
				if ( 'tools_page_one-click-multisite' !== $hook_suffix ) {
					return;
				}
				wp_enqueue_style(
					'one-click-multisite',
					$plugin_url . 'assets/css/admin.css',
					array(),
					$version
				);
			}
		);

		add_filter(
			'plugin_action_links_' . $basename,
			static function ( array $links ): array {
				$url   = admin_url( 'tools.php?page=one-click-multisite' );
				$label = __( 'Convert to Multisite', 'one-click-multisite' );
				$link  = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
				return array_merge( array( 'one-click-multisite-settings' => $link ), $links );
			}
		);

		add_action( 'admin_post_one_click_multisite_convert', array( $controller, 'handle' ) );

		return true;
	}
}
