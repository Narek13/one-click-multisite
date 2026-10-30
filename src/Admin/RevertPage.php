<?php

declare(strict_types=1);

namespace OneClickMultisite\Admin;

use OneClickMultisite\Revert\RevertChecker;

/**
 * The Network Admin > Settings > Revert to Single Site screen.
 */
final class RevertPage {

	/**
	 * Menu slug.
	 */
	public const SLUG = 'one-click-multisite-revert';

	/**
	 * Hook suffix admin_enqueue_scripts receives for the page (the screen ID gets "-network", the hook suffix does not).
	 */
	public const HOOK_SUFFIX = 'settings_page_one-click-multisite-revert';

	/**
	 * Capability required to see the page and run the revert (super admins).
	 */
	public const CAPABILITY = 'manage_network_options';

	/**
	 * Constructor.
	 *
	 * @param RevertChecker $checker Revert checks.
	 * @param Notice        $notice  Result notice store.
	 */
	public function __construct(
		private readonly RevertChecker $checker,
		private readonly Notice $notice,
	) {
	}

	/**
	 * Returns the page URL.
	 *
	 * @return string
	 */
	public static function url(): string {
		return network_admin_url( 'settings.php?page=' . self::SLUG );
	}

	/**
	 * Hooks the Network Admin menu entry and the Network Plugins action link.
	 */
	public function register(): void {
		add_action( 'network_admin_menu', array( $this, 'addMenu' ) );
		add_filter( 'network_admin_plugin_action_links_' . plugin_basename( ONE_CLICK_MULTISITE_FILE ), array( $this, 'addActionLink' ) );
	}

	/**
	 * Adds the Settings submenu page in the Network Admin.
	 */
	public function addMenu(): void {
		add_submenu_page(
			'settings.php',
			__( 'Revert to Single Site', 'one-click-multisite' ),
			__( 'Revert to Single Site', 'one-click-multisite' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Prepends a "Revert to Single Site" link to the plugin's row on the Network Plugins screen.
	 *
	 * @param array<string, string> $links Existing action links.
	 * @return array<string, string>
	 */
	public function addActionLink( array $links ): array {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return $links;
		}

		$link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( self::url() ),
			esc_html__( 'Revert to Single Site', 'one-click-multisite' )
		);

		return array_merge( array( 'one-click-multisite-revert' => $link ), $links );
	}

	/**
	 * Renders the page.
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'one-click-multisite' ) );
		}

		$prerequisites = $this->checker->check();
		$canRevert     = $this->checker->canRevert();
		$notice        = $this->notice->pull( Notice::REVERT );
		$action        = RevertController::ACTION;

		require ONE_CLICK_MULTISITE_DIR . 'templates/revert-page.php';
	}
}
