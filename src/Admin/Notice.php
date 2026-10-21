<?php

declare(strict_types=1);

namespace OneClickMultisite\Admin;

use OneClickMultisite\Conversion\ConversionResult;

/**
 * Carries the conversion outcome across the post/redirect/get round trip.
 *
 * Stored per user, so two admins converting at once never see each other's result.
 */
final class Notice {

	/**
	 * Transient key prefix; the user ID is appended.
	 */
	public const TRANSIENT_PREFIX = 'one_click_multisite_notice_';

	/**
	 * Stores a conversion result for the current user.
	 *
	 * @param ConversionResult $result Conversion result.
	 */
	public function store( ConversionResult $result ): void {
		set_transient(
			$this->key(),
			array(
				'success'  => $result->succeeded(),
				'message'  => $result->message(),
				'warnings' => $result->warnings(),
			),
			5 * MINUTE_IN_SECONDS
		);
	}

	/**
	 * Returns and clears the stored result for the current user.
	 *
	 * @return array{success: bool, message: string, warnings: list<string>}|null
	 */
	public function pull(): ?array {
		$key    = $this->key();
		$notice = get_transient( $key );

		if ( ! is_array( $notice ) ) {
			return null;
		}

		delete_transient( $key );

		$warnings = isset( $notice['warnings'] ) && is_array( $notice['warnings'] ) ? $notice['warnings'] : array();

		return array(
			'success'  => ! empty( $notice['success'] ),
			'message'  => isset( $notice['message'] ) ? (string) $notice['message'] : '',
			'warnings' => array_values( array_map( 'strval', $warnings ) ),
		);
	}

	/**
	 * Returns the transient key for the current user.
	 *
	 * @return string
	 */
	private function key(): string {
		return self::TRANSIENT_PREFIX . get_current_user_id();
	}
}
