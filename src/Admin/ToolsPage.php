<?php

declare(strict_types=1);

namespace OneClickMultisite\Admin;

use OneClickMultisite\Conversion\PrerequisiteChecker;

/**
 * The Tools > Convert to Multisite screen.
 */
final class ToolsPage {

	/**
	 * Menu slug.
	 */
	public const SLUG = 'one-click-multisite';

	/**
	 * Screen hook suffix WordPress assigns to the page.
	 */
	public const HOOK_SUFFIX = 'tools_page_one-click-multisite';

	/**
	 * Capability required to see the page and run the conversion. Core's own
	 * Network Setup screen uses it; on a single site it maps to manage_options.
	 */
	public const CAPABILITY = 'setup_network';

	/**
	 * Constructor.
	 *
	 * @param PrerequisiteChecker $checker Prerequisite checker.
	 * @param Notice              $notice  Result notice store.
	 */
	public function __construct(
		private readonly PrerequisiteChecker $checker,
		private readonly Notice $notice,
	) {
	}

	/**
	 * Returns the page URL.
	 *
	 * @return string
	 */
	public static function url(): string {
		return admin_url( 'tools.php?page=' . self::SLUG );
	}

	/**
	 * Hooks the menu entry and the Plugins screen action link.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenu' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ONE_CLICK_MULTISITE_FILE ), array( $this, 'addActionLink' ) );
		add_action( 'network_admin_notices', array( $this, 'renderNetworkNotice' ) );
	}

	/**
	 * Adds the Tools submenu page. Hidden once the site is a network: there is nothing left to convert.
	 */
	public function addMenu(): void {
		if ( is_multisite() ) {
			return;
		}

		add_management_page(
			__( 'Convert to Multisite', 'one-click-multisite' ),
			__( 'Convert to Multisite', 'one-click-multisite' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Prepends a "Convert to Multisite" link to the plugin's row on the Plugins screen.
	 *
	 * @param array<string, string> $links Existing action links.
	 * @return array<string, string>
	 */
	public function addActionLink( array $links ): array {
		if ( is_multisite() || ! current_user_can( self::CAPABILITY ) ) {
			return $links;
		}

		$link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( self::url() ),
			esc_html__( 'Convert to Multisite', 'one-click-multisite' )
		);

		return array_merge( array( 'one-click-multisite' => $link ), $links );
	}

	/**
	 * Shows the "conversion complete" notice on the first Network Admin screen after converting.
	 */
	public function renderNetworkNotice(): void {
		if ( ! current_user_can( 'manage_network' ) ) {
			return;
		}

		$notice = $this->notice->pull( Notice::CONVERT );
		if ( null === $notice || ! $notice['success'] ) {
			return;
		}

		require ONE_CLICK_MULTISITE_DIR . 'templates/network-notice.php';
	}

	/**
	 * Renders the page.
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'one-click-multisite' ) );
		}

		$environment         = $this->checker->environment();
		$prerequisites       = $this->checker->check();
		$canConvert          = $this->checker->canConvert();
		$subdomainAllowed    = $environment->subdomainAllowed();
		$subdirectoryAllowed = $environment->subdirectoryAllowed();
		$notice              = $this->notice->pull( Notice::CONVERT );
		$revertNotice        = $this->notice->pull( Notice::REVERT );
		$action              = ConversionController::ACTION;

		require ONE_CLICK_MULTISITE_DIR . 'templates/tools-page.php';
	}
}
