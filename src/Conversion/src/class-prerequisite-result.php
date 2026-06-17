<?php
/**
 * Prerequisite result value object.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite\Conversion;

/**
 * Holds the outcome of a single prerequisite check.
 */
class PrerequisiteResult {

	/**
	 * Human-readable label for this prerequisite.
	 *
	 * @var string
	 */
	private string $label;

	/**
	 * Whether this prerequisite passes.
	 *
	 * @var bool
	 */
	private bool $passes;

	/**
	 * Optional message explaining why the check failed.
	 *
	 * @var string
	 */
	private string $message;

	/**
	 * Constructor.
	 *
	 * @param string $label   Human-readable label.
	 * @param bool   $passes  Whether the prerequisite passes.
	 * @param string $message Optional failure message.
	 */
	public function __construct( string $label, bool $passes, string $message = '' ) {
		$this->label   = $label;
		$this->passes  = $passes;
		$this->message = $message;
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
	 * Returns the failure message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}
}
