<?php

declare(strict_types=1);

namespace OneClickMultisite\Conversion;

/**
 * Outcome of a multisite conversion attempt.
 */
final class ConversionResult {

	/**
	 * Constructor.
	 *
	 * @param bool         $success  Whether the conversion succeeded.
	 * @param string       $message  Error message when the conversion failed.
	 * @param list<string> $warnings Non-fatal follow-up notes (wildcard DNS, manual server rules).
	 */
	public function __construct(
		private readonly bool $success,
		private readonly string $message = '',
		private readonly array $warnings = array(),
	) {
	}

	/**
	 * Creates a failed result.
	 *
	 * @param string $message Error message.
	 * @return self
	 */
	public static function failure( string $message ): self {
		return new self( false, $message );
	}

	/**
	 * Creates a successful result.
	 *
	 * @param list<string> $warnings Non-fatal follow-up notes.
	 * @return self
	 */
	public static function success( array $warnings = array() ): self {
		return new self( true, '', $warnings );
	}

	/**
	 * Returns whether the conversion succeeded.
	 *
	 * @return bool
	 */
	public function succeeded(): bool {
		return $this->success;
	}

	/**
	 * Returns the error message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Returns the non-fatal warnings.
	 *
	 * @return list<string>
	 */
	public function warnings(): array {
		return $this->warnings;
	}
}
