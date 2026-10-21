<?php

declare(strict_types=1);

namespace OneClickMultisite\Conversion;

/**
 * Outcome of a single prerequisite check.
 *
 * A failing check is either blocking (conversion is refused) or a warning
 * (conversion proceeds, but the admin has a manual follow-up step).
 */
final class PrerequisiteResult {

	/**
	 * Constructor.
	 *
	 * @param string $label    Human-readable label.
	 * @param bool   $passes   Whether the prerequisite passes.
	 * @param string $message  Explanation shown when the check does not pass.
	 * @param bool   $blocking Whether a failure blocks the conversion.
	 */
	public function __construct(
		private readonly string $label,
		private readonly bool $passes,
		private readonly string $message = '',
		private readonly bool $blocking = true,
	) {
	}

	/**
	 * Returns the prerequisite label.
	 *
	 * @return string
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * Returns whether the prerequisite passes.
	 *
	 * @return bool
	 */
	public function passes(): bool {
		return $this->passes;
	}

	/**
	 * Returns whether this result blocks the conversion.
	 *
	 * @return bool
	 */
	public function blocks(): bool {
		return ! $this->passes && $this->blocking;
	}

	/**
	 * Returns whether this result is a non-blocking warning.
	 *
	 * @return bool
	 */
	public function isWarning(): bool {
		return ! $this->passes && ! $this->blocking;
	}

	/**
	 * Returns the explanation message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}
}
