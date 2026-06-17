<?php
/**
 * Prerequisite checker.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite\Conversion;

/**
 * Checks whether the WordPress installation meets the requirements for multisite conversion.
 */
class PrerequisiteChecker {

	/**
	 * Runs all prerequisite checks and returns their results.
	 *
	 * @return PrerequisiteResult[]
	 */
	public function check(): array {
		return array(
			$this->check_single_site(),
			$this->check_wp_config_writable(),
			$this->check_htaccess_writable(),
		);
	}

	/**
	 * Returns true when all prerequisites pass.
	 *
	 * @return bool
	 */
	public function all_pass(): bool {
		foreach ( $this->check() as $result ) {
			if ( ! $result->passes() ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks that the site is a single-site installation.
	 *
	 * @return PrerequisiteResult
	 */
	private function check_single_site(): PrerequisiteResult {
		$label = __( 'Single site installation', 'one-click-multisite' );

		if ( is_multisite() ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'This site is already running as a multisite network.', 'one-click-multisite' )
			);
		}

		if ( defined( 'WP_ALLOW_MULTISITE' ) && WP_ALLOW_MULTISITE ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'WP_ALLOW_MULTISITE is already defined. A previous conversion may be incomplete.', 'one-click-multisite' )
			);
		}

		return new PrerequisiteResult( $label, true );
	}

	/**
	 * Checks that wp-config.php exists and is writable.
	 *
	 * @return PrerequisiteResult
	 */
	private function check_wp_config_writable(): PrerequisiteResult {
		$label  = __( 'wp-config.php is writable', 'one-click-multisite' );
		$config = $this->find_wp_config();

		if ( null === $config ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'wp-config.php could not be located.', 'one-click-multisite' )
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- WP_Filesystem is not available at prerequisite-check time.
		if ( ! is_writable( $config ) ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'wp-config.php is not writable. Please check file permissions.', 'one-click-multisite' )
			);
		}

		return new PrerequisiteResult( $label, true );
	}

	/**
	 * Checks that .htaccess is writable (or can be created).
	 *
	 * @return PrerequisiteResult
	 */
	private function check_htaccess_writable(): PrerequisiteResult {
		$label    = __( '.htaccess is writable', 'one-click-multisite' );
		$htaccess = ABSPATH . '.htaccess';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- WP_Filesystem is not available at prerequisite-check time.
		if ( file_exists( $htaccess ) && ! is_writable( $htaccess ) ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( '.htaccess exists but is not writable. Please check file permissions.', 'one-click-multisite' )
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- WP_Filesystem is not available at prerequisite-check time.
		if ( ! file_exists( $htaccess ) && ! is_writable( ABSPATH ) ) {
			return new PrerequisiteResult(
				$label,
				false,
				__( 'The WordPress root directory is not writable. Cannot create .htaccess.', 'one-click-multisite' )
			);
		}

		return new PrerequisiteResult( $label, true );
	}

	/**
	 * Locates wp-config.php in standard locations.
	 *
	 * @return string|null Absolute path to wp-config.php, or null if not found.
	 */
	public function find_wp_config(): ?string {
		$locations = array(
			ABSPATH . 'wp-config.php',
			dirname( ABSPATH ) . '/wp-config.php',
		);

		foreach ( $locations as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return null;
	}
}
