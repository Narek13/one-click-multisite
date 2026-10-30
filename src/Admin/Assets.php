<?php

declare(strict_types=1);

namespace OneClickMultisite\Admin;

/**
 * Enqueues the admin stylesheet, only on the plugin's own screens.
 */
final class Assets {

	/**
	 * Hooks asset enqueueing for the plugin screen.
	 */
	public function register(): void {
		add_action(
			'admin_enqueue_scripts',
			static function ( string $hook ): void {
				if ( ToolsPage::HOOK_SUFFIX !== $hook && RevertPage::HOOK_SUFFIX !== $hook ) {
					return;
				}

				wp_enqueue_style(
					'one-click-multisite-admin',
					ONE_CLICK_MULTISITE_URL . 'assets/admin.css',
					array(),
					ONE_CLICK_MULTISITE_VERSION
				);
			}
		);
	}
}
