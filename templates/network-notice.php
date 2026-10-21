<?php
/**
 * Network Admin notice shown once, right after a successful conversion.
 *
 * @package OneClickMultisite
 *
 * @var array{success: bool, message: string, warnings: list<string>} $notice
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template variables scoped to include context.

defined( 'ABSPATH' ) || exit;
?>
<div class="notice notice-success is-dismissible">
	<p>
		<strong><?php esc_html_e( 'Conversion complete.', 'one-click-multisite' ); ?></strong>
		<?php esc_html_e( 'Your site is now a multisite network. You can add sites under Sites > Add New.', 'one-click-multisite' ); ?>
	</p>
</div>
<?php foreach ( $notice['warnings'] as $warning ) : ?>
	<div class="notice notice-warning">
		<p><?php echo esc_html( $warning ); ?></p>
	</div>
<?php endforeach; ?>
