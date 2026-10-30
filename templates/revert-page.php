<?php
/**
 * Network Admin > Settings > Revert to Single Site.
 *
 * @package OneClickMultisite
 *
 * @var list<\OneClickMultisite\Conversion\PrerequisiteResult> $prerequisites
 * @var bool $canRevert
 * @var array{success: bool, message: string, warnings: list<string>}|null $notice
 * @var string $action
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template variables scoped to include context.

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ocm-wrap">
	<h1><?php esc_html_e( 'Revert to Single Site', 'one-click-multisite' ); ?></h1>
	<p class="ocm-subtitle"><?php esc_html_e( 'Turn this network back into a single WordPress site. Possible while the network holds only its main site.', 'one-click-multisite' ); ?></p>

	<?php if ( null !== $notice && ! $notice['success'] ) : ?>
		<div class="notice notice-error ocm-notice">
			<p>
				<strong><?php esc_html_e( 'The revert did not complete.', 'one-click-multisite' ); ?></strong>
				<?php echo esc_html( $notice['message'] ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="ocm-cards">

		<?php
		$ready = $canRevert;
		require __DIR__ . '/partials/prerequisites.php';
		?>

		<div class="ocm-card">
			<div class="ocm-card__header">
				<h2><?php esc_html_e( 'What happens', 'one-click-multisite' ); ?></h2>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( $action ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">

				<div class="ocm-card__body">
					<ul class="ocm-steps">
						<li><?php esc_html_e( 'The multisite constants are removed from wp-config.php.', 'one-click-multisite' ); ?></li>
						<li><?php esc_html_e( 'The standard WordPress rewrite rules are restored in .htaccess.', 'one-click-multisite' ); ?></li>
						<li><?php esc_html_e( 'Network-activated plugins stay active on the site.', 'one-click-multisite' ); ?></li>
						<li><?php esc_html_e( 'The network tables (sites, network options, sign-ups) are deleted. Network settings are lost.', 'one-click-multisite' ); ?></li>
						<li><?php esc_html_e( 'Your posts, pages, media, users and site settings are not touched.', 'one-click-multisite' ); ?></li>
					</ul>
					<label class="ocm-confirm">
						<input type="checkbox" name="confirm_backup" value="1" required <?php disabled( ! $canRevert ); ?>>
						<?php esc_html_e( 'I have a backup of my files and database.', 'one-click-multisite' ); ?>
					</label>
				</div>

				<div class="ocm-card__footer">
					<p class="ocm-warning">
						<?php esc_html_e( 'You may have to log in again afterwards.', 'one-click-multisite' ); ?>
					</p>
					<button type="submit" class="button button-primary ocm-btn" <?php disabled( ! $canRevert ); ?>>
						<?php esc_html_e( 'Revert to Single Site', 'one-click-multisite' ); ?>
					</button>
					<?php if ( ! $canRevert ) : ?>
						<span class="ocm-btn-note"><?php esc_html_e( 'Resolve the failed checks above to enable the revert.', 'one-click-multisite' ); ?></span>
					<?php endif; ?>
				</div>
			</form>
		</div>

	</div>
</div>
