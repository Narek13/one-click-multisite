<?php
/**
 * Conversion controller.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite\Admin;

use OneClickMultisite\Conversion\MultisiteConverter;

/**
 * Handles the admin-post form submission that triggers multisite conversion.
 */
class ConversionController {

	/**
	 * Multisite converter service.
	 *
	 * @var MultisiteConverter
	 */
	private MultisiteConverter $converter;

	/**
	 * Constructor.
	 *
	 * @param MultisiteConverter $converter The multisite converter.
	 */
	public function __construct( MultisiteConverter $converter ) {
		$this->converter = $converter;
	}

	/**
	 * Handles the conversion form submission.
	 *
	 * @return void
	 */
	public function handle(): void {
		check_admin_referer( 'one_click_multisite_convert' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'one-click-multisite' ) );
		}

		$subdomain_install = isset( $_POST['subdomain_install'] ) && sanitize_key( $_POST['subdomain_install'] ) === '1';

		$result = $this->converter->convert( $subdomain_install );

		if ( $result->success() ) {
			set_transient( 'one_click_multisite_notice', 'success', 120 );
			wp_safe_redirect( admin_url( 'tools.php?page=one-click-multisite' ) );
			exit;
		}

		set_transient(
			'one_click_multisite_notice',
			'error:' . $result->message(),
			30
		);

		wp_safe_redirect( admin_url( 'tools.php?page=one-click-multisite' ) );
		exit;
	}
}
