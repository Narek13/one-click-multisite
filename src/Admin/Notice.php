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
	 * Transient key prefix; the context and user ID are appended.
	 */
	public const TRANSIENT_PREFIX = 'one_click_multisite_notice_';

	/**
	 * Result of converting a single site into a network.
	 */
	public const CONVERT = 'convert';

	/**
	 * Result of reverting a network to a single site.
	 */
	public const REVERT = 'revert';

	/**
	 * Stores a result for the current user.
	 *
	 * @param ConversionResult $result  Conversion or revert result.
	 * @param string           $context self::CONVERT or self::REVERT.
	 */
	public function store( ConversionResult $result, string $context = self::CONVERT ): void {
		set_transient(
			$this->key( $context ),
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
	 * Contexts are kept apart so a notice hook on one screen can never consume
	 * the result another screen is about to show.
	 *
	 * @param string $context self::CONVERT or self::REVERT.
	 * @return array{success: bool, message: string, warnings: list<string>}|null
	 */
	public function pull( string $context = self::CONVERT ): ?array {
		$key    = $this->key( $context );
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
	 * Returns the transient key for the given context and the current user.
	 *
	 * @param string $context self::CONVERT or self::REVERT.
	 * @return string
	 */
	private function key( string $context ): string {
		return self::TRANSIENT_PREFIX . $context . '_' . get_current_user_id();
	}
}
