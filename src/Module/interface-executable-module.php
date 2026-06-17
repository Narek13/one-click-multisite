<?php
/**
 * Executable module interface.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite\Module;

use Psr\Container\ContainerInterface;

/**
 * Interface for modules that register hooks and execute side effects.
 */
interface ExecutableModule {

	/**
	 * Execute the module.
	 *
	 * @param ContainerInterface $container The service container.
	 * @return bool True on success.
	 */
	public function run( ContainerInterface $container ): bool;
}
