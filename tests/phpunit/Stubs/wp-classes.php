<?php
/**
 * Minimal stand-ins for the WordPress classes the revert checks instantiate.
 * Brain Monkey stubs functions, not classes, so these are loaded by the bootstrap.
 *
 * @package OneClickMultisite
 */

declare(strict_types=1);

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Commenting.FunctionComment.Missing, Squiz.Commenting.VariableComment.Missing, Squiz.Commenting.ClassComment.Missing, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound, Generic.Commenting.DocComment.MissingShort, Generic.CodeAnalysis.UnusedFunctionParameter -- test doubles of WordPress core classes.

if ( ! class_exists( 'WP_User' ) ) {
	class WP_User {
		/** @var array<int, list<string>> User ID => roles on the main site. */
		public static array $rolesByUser = array();

		/** @var list<string> */
		public array $roles;

		public function __construct( int $id = 0, string $name = '', int $siteId = 0 ) {
			$this->roles = self::$rolesByUser[ $id ] ?? array();
		}
	}
}

if ( ! class_exists( 'WP_User_Query' ) ) {
	class WP_User_Query {
		/** @var array<int, int> Site ID (0 = everyone) => number of users. */
		public static array $totals = array();

		/** @var array<string, mixed> */
		private array $args;

		/**
		 * @param array<string, mixed> $args Query arguments.
		 */
		public function __construct( array $args = array() ) {
			$this->args = $args;
		}

		public function get_total(): int {
			return self::$totals[ (int) ( $this->args['blog_id'] ?? 0 ) ] ?? 0;
		}
	}
}

if ( ! class_exists( 'WP_Filesystem_Base' ) ) {
	class WP_Filesystem_Base {
		/**
		 * @param string $file File path.
		 * @return string|false
		 */
		public function get_contents( $file ) {
			return is_readable( $file ) ? file_get_contents( $file ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions -- test double.
		}
	}
}
