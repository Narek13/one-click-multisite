<?php
/**
 * Plugin bootstrapper.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite;

use OneClickMultisite\Module\ExecutableModule;
use OneClickMultisite\Module\ServiceModule;

/**
 * Bootstraps the plugin by loading modules and wiring up the service container.
 */
class Plugin {

	/**
	 * Plugin properties.
	 *
	 * @var PluginProperties
	 */
	private PluginProperties $properties;

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Registered modules.
	 *
	 * @var array<ServiceModule|ExecutableModule>
	 */
	private array $modules = array();

	/**
	 * Constructor.
	 *
	 * @param PluginProperties $properties Plugin properties.
	 */
	private function __construct( PluginProperties $properties ) {
		$this->properties = $properties;
		$this->container  = new Container();
	}

	/**
	 * Creates a new Plugin instance.
	 *
	 * @param PluginProperties $properties Plugin properties.
	 * @return self
	 */
	public static function new( PluginProperties $properties ): self {
		return new self( $properties );
	}

	/**
	 * Adds one or more modules to the plugin.
	 *
	 * @param ServiceModule|ExecutableModule ...$modules Modules to add.
	 * @return self
	 */
	public function add_module( ...$modules ): self {
		foreach ( $modules as $module ) {
			$this->modules[] = $module;
		}
		return $this;
	}

	/**
	 * Boots the plugin by registering all services and running all executable modules.
	 *
	 * @return bool True on success.
	 */
	public function boot(): bool {
		$this->container->bind(
			PluginProperties::class,
			function (): PluginProperties {
				return $this->properties;
			}
		);

		foreach ( $this->modules as $module ) {
			if ( $module instanceof ServiceModule ) {
				foreach ( $module->services() as $id => $factory ) {
					$this->container->bind( $id, $factory );
				}
			}
		}

		foreach ( $this->modules as $module ) {
			if ( $module instanceof ExecutableModule ) {
				$module->run( $this->container );
			}
		}

		return true;
	}
}
