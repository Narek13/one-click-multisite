<?php

declare(strict_types=1);

namespace OneClickMultisite\Conversion;

/**
 * Adds constant definitions to the contents of a wp-config.php file.
 *
 * Pure string transform, no I/O, so it can be unit-tested against any
 * wp-config.php layout. Values are emitted with var_export(), so a quote or
 * backslash in a domain or path can never break out of the PHP literal.
 */
final class WpConfigEditor {

	/**
	 * The line WordPress tells users to add custom constants above.
	 */
	public const STOP_EDITING_MARKER = "/* That's all, stop editing!";

	/**
	 * Matches the line that loads wp-settings.php — the latest point at which
	 * constants still take effect. Covers `require_once ABSPATH . 'wp-settings.php';`,
	 * `require_once( ABSPATH . '/wp-settings.php' );` and `__DIR__` variants.
	 */
	private const WP_SETTINGS_PATTERN = '/^[ \t]*(?:if\s*\([^\n]*wp-settings\.php[^\n]*\)\s*\{\s*)?require(?:_once)?\b[^;\n]*wp-settings\.php[^;\n]*;/m';

	/**
	 * Inserts `define()` calls for every constant that is not already defined.
	 *
	 * The block goes directly above the "stop editing" marker. When the marker is
	 * missing it goes above the line that loads wp-settings.php instead.
	 *
	 * @param string                         $contents  Current wp-config.php contents.
	 * @param array<string, bool|int|string> $constants Constant name => value.
	 * @return string|null New contents, or null when no safe insertion point exists.
	 */
	public function insert( string $contents, array $constants ): ?string {
		$lines = array();
		foreach ( $constants as $name => $value ) {
			if ( $this->defines( $contents, $name ) ) {
				continue;
			}
			$lines[] = sprintf( 'define( %s, %s );', var_export( $name, true ), var_export( $value, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- generates PHP source, not debug output.
		}

		if ( array() === $lines ) {
			return $contents;
		}

		$offset = $this->insertionOffset( $contents );
		if ( null === $offset ) {
			return null;
		}

		$block = "/* Multisite. Added by One Click Multisite. */\n" . implode( "\n", $lines ) . "\n\n";

		return substr( $contents, 0, $offset ) . $block . substr( $contents, $offset );
	}

	/**
	 * Returns whether the contents already define the given constant.
	 *
	 * @param string $contents wp-config.php contents.
	 * @param string $name     Constant name.
	 * @return bool
	 */
	public function defines( string $contents, string $name ): bool {
		$pattern = '/\bdefine\s*\(\s*([\'"])' . preg_quote( $name, '/' ) . '\1\s*,/i';

		return 1 === preg_match( $pattern, $contents );
	}

	/**
	 * Finds the byte offset where the constants block should be inserted.
	 *
	 * @param string $contents wp-config.php contents.
	 * @return int|null
	 */
	private function insertionOffset( string $contents ): ?int {
		$marker = strpos( $contents, self::STOP_EDITING_MARKER );
		if ( false !== $marker ) {
			return $marker;
		}

		if ( 1 === preg_match( self::WP_SETTINGS_PATTERN, $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
			return (int) $matches[0][1];
		}

		return null;
	}
}
