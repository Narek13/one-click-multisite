<?php
/**
 * Conversion services module.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite\Conversion;

use OneClickMultisite\Module\ServiceModule;
use Psr\Container\ContainerInterface;

/**
 * Registers the prerequisite checker and multisite converter services.
 */
class ConversionModule implements ServiceModule {

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string, callable>
	 */
	public function services(): array {
		return array(
			PrerequisiteChecker::class => static function (): PrerequisiteChecker {
				return new PrerequisiteChecker();
			},
			MultisiteConverter::class  => static function ( ContainerInterface $c ): MultisiteConverter {
				return new MultisiteConverter( $c->get( PrerequisiteChecker::class ) );
			},
		);
	}
}
