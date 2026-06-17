<?php
/**
 * Conversion result value object.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite\Conversion;

/**
 * Holds the outcome of a multisite conversion attempt.
 */
class ConversionResult {

	/**
	 * Whether the conversion succeeded.
	 *
	 * @var bool
	 */
	private bool $success;

	/**
	 * Optional error or status message.
	 *
	 * @var string
	 */
	private string $message;

	/**
	 * Constructor.
	 *
	 * @param bool   $success Whether the conversion succeeded.
	 * @param string $message Optional message.
	 */
	public function __construct( bool $success, string $message = '' ) {
		$this->success = $success;
		$this->message = $message;
	}

	/**
	 * Returns whether the conversion succeeded.
	 *
	 * @return bool
	 */
	public function success(): bool {
		return $this->success;
	}

	/**
	 * Returns the result message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}
}
