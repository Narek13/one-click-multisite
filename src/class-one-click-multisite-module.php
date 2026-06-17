<?php
/**
 * Root services module.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite;

use OneClickMultisite\Module\ServiceModule;

/**
 * Registers core plugin services: basename, URL, and version.
 */
class OneClickMultisiteModule implements ServiceModule {

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 */
	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string, callable>
	 */
	public function services(): array {
		$plugin_file = $this->plugin_file;

		return array(
			'one-click-multisite.plugin-basename' => static function () use ( $plugin_file ): string {
				return plugin_basename( $plugin_file );
			},
			'one-click-multisite.plugin-url'      => static function () use ( $plugin_file ): string {
				return plugin_dir_url( $plugin_file );
			},
			'one-click-multisite.plugin-version'  => static function () use ( $plugin_file ): string {
				$data = get_file_data( $plugin_file, array( 'Version' => 'Version' ) );
				return $data['Version'] ?? '1.0.0';
			},
		);
	}
}
