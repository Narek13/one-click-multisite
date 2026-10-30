<?php

declare(strict_types=1);

namespace OneClickMultisite\Admin;

use OneClickMultisite\Conversion\ConversionResult;
use OneClickMultisite\Revert\SingleSiteReverter;

/**
 * Handles the admin-post form submission that reverts the network.
 */
final class RevertController {

	/**
	 * The admin-post action name and nonce action.
	 */
	public const ACTION = 'one_click_multisite_revert';

	/**
	 * Constructor.
	 *
	 * @param SingleSiteReverter $reverter Network reverter.
	 * @param Notice             $notice   Result notice store.
	 */
	public function __construct(
		private readonly SingleSiteReverter $reverter,
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
	 * Verifies the request, runs the revert and redirects.
	 */
	public function handle(): void {
		if ( ! is_multisite() || ! current_user_can( RevertPage::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'one-click-multisite' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION );

		if ( ! isset( $_POST['confirm_backup'] ) || '1' !== sanitize_key( wp_unslash( $_POST['confirm_backup'] ) ) ) {
			$this->notice->store( ConversionResult::failure( __( 'Confirm that you have a backup first.', 'one-click-multisite' ) ), Notice::REVERT );
			wp_safe_redirect( RevertPage::url() );
			exit;
		}

		// Read the target before reverting: afterwards network_admin_url() no longer applies.
		$singleSiteUrl = ToolsPage::url();

		$result = $this->reverter->revert();
		$this->notice->store( $result, Notice::REVERT );

		// On success the next request already runs as a single site: continue on the
		// Tools screen there (which may ask to log in again).
		wp_safe_redirect( $result->succeeded() ? $singleSiteUrl : RevertPage::url() );
		exit;
	}
}
