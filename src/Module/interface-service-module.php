<?php
/**
 * Service module interface.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite\Module;

/**
 * Interface for modules that register services into the container.
 */
interface ServiceModule {

	/**
	 * Returns service definitions as a map of ID to factory callable.
	 *
	 * @return array<string, callable> Map of service IDs to factory callables.
	 */
	public function services(): array;
}
