<?php
/**
 * PSR-11 service container.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Minimal PSR-11 service container with lazy singleton resolution.
 */
class Container implements ContainerInterface {

	/**
	 * Registered factory callables keyed by service ID.
	 *
	 * @var array<string, callable>
	 */
	private array $definitions = array();

	/**
	 * Already-resolved service instances.
	 *
	 * @var array<string, mixed>
	 */
	private array $resolved = array();

	/**
	 * Registers a service factory.
	 *
	 * @param string   $id      Service identifier.
	 * @param callable $factory Factory that receives this container and returns the service.
	 * @return void
	 */
	public function bind( string $id, callable $factory ): void {
		$this->definitions[ $id ] = $factory;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $id Service identifier.
	 * @return mixed The service instance.
	 * @throws \RuntimeException When no service is found.
	 */
	public function get( string $id ) {
		if ( array_key_exists( $id, $this->resolved ) ) {
			return $this->resolved[ $id ];
		}

		if ( ! $this->has( $id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $id is a class name, not user input.
			throw new class( "Service not found: {$id}" ) extends RuntimeException implements \Psr\Container\NotFoundExceptionInterface {};
		}

		$this->resolved[ $id ] = ( $this->definitions[ $id ] )( $this );

		return $this->resolved[ $id ];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->definitions[ $id ] );
	}
}
