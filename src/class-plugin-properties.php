<?php
/**
 * Plugin properties value object.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite;

/**
 * Provides read access to core plugin metadata derived from the main plugin file.
 */
class PluginProperties {

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
	private function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Creates a new instance for the given plugin file.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 * @return self
	 */
	public static function new( string $plugin_file ): self {
		return new self( $plugin_file );
	}

	/**
	 * Returns the absolute path to the main plugin file.
	 *
	 * @return string
	 */
	public function plugin_file(): string {
		return $this->plugin_file;
	}

	/**
	 * Returns the plugin basename.
	 *
	 * @return string
	 */
	public function basename(): string {
		return plugin_basename( $this->plugin_file );
	}

	/**
	 * Returns the plugin directory URL with trailing slash.
	 *
	 * @return string
	 */
	public function url(): string {
		return plugin_dir_url( $this->plugin_file );
	}

	/**
	 * Returns the plugin directory path with trailing slash.
	 *
	 * @return string
	 */
	public function dir(): string {
		return plugin_dir_path( $this->plugin_file );
	}

	/**
	 * Returns the plugin version from the file header.
	 *
	 * @return string
	 */
	public function version(): string {
		$data = get_file_data( $this->plugin_file, array( 'Version' => 'Version' ) );
		return $data['Version'] ?? '1.0.0';
	}
}
