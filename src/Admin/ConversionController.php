<?php

declare(strict_types=1);

namespace OneClickMultisite\Admin;

use OneClickMultisite\Conversion\MultisiteConverter;

/**
 * Handles the admin-post form submission that triggers the conversion.
 */
final class ConversionController {

	/**
	 * The admin-post action name and nonce action.
	 */
	public const ACTION = 'one_click_multisite_convert';

	/**
	 * Constructor.
	 *
	 * @param MultisiteConverter $converter Multisite converter.
	 * @param Notice             $notice    Result notice store.
	 */
	public function __construct(
		private readonly MultisiteConverter $converter,
		private readonly Notice $notice,
	) {
	}

	/**
	 * Hooks the form handler.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Verifies the request, runs the conversion and redirects back to the Tools page.
	 */
	public function handle(): void {
		if ( ! current_user_can( ToolsPage::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'one-click-multisite' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION );

		$subdomainInstall = isset( $_POST['subdomain_install'] ) && '1' === sanitize_key( wp_unslash( $_POST['subdomain_install'] ) );

		$result = $this->converter->convert( $subdomainInstall );
		$this->notice->store( $result );

		// On success the next request already runs as a network, where the Tools page
		// no longer exists: continue in the Network Admin (which may ask to log in again).
		wp_safe_redirect( $result->succeeded() ? admin_url( 'network/' ) : ToolsPage::url() );
		exit;
	}
}
