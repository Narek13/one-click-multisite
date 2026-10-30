<?php
/**
 * Tools > Convert to Multisite.
 *
 * @package OneClickMultisite
 *
 * @var list<\OneClickMultisite\Conversion\PrerequisiteResult> $prerequisites
 * @var bool $canConvert
 * @var bool $subdomainAllowed
 * @var bool $subdirectoryAllowed
 * @var array{success: bool, message: string, warnings: list<string>}|null $notice
 * @var array{success: bool, message: string, warnings: list<string>}|null $revertNotice
 * @var string $action
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template variables scoped to include context.

defined( 'ABSPATH' ) || exit;

// Pre-select whichever network type is allowed; sub-directories when both are.
$preferSubdomain = $subdomainAllowed && ! $subdirectoryAllowed;
?>
<div class="wrap ocm-wrap">
	<h1><?php esc_html_e( 'Convert to Multisite', 'one-click-multisite' ); ?></h1>
	<p class="ocm-subtitle"><?php esc_html_e( 'Convert this single-site WordPress installation into a multisite network.', 'one-click-multisite' ); ?></p>

	<?php if ( null !== $notice && ! $notice['success'] ) : ?>
		<div class="notice notice-error ocm-notice">
			<p>
				<strong><?php esc_html_e( 'The conversion did not complete.', 'one-click-multisite' ); ?></strong>
				<?php echo esc_html( $notice['message'] ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( null !== $revertNotice && $revertNotice['success'] ) : ?>
		<div class="notice notice-success ocm-notice">
			<p>
				<strong><?php esc_html_e( 'Reverted to a single site.', 'one-click-multisite' ); ?></strong>
				<?php esc_html_e( 'The network settings and tables have been removed. You can convert again at any time.', 'one-click-multisite' ); ?>
			</p>
		</div>
		<?php foreach ( $revertNotice['warnings'] as $warning ) : ?>
			<div class="notice notice-warning ocm-notice"><p><?php echo esc_html( $warning ); ?></p></div>
		<?php endforeach; ?>
	<?php endif; ?>

	<div class="ocm-cards">

		<?php
		$ready = $canConvert;
		require __DIR__ . '/partials/prerequisites.php';
		?>

		<div class="ocm-card">
			<div class="ocm-card__header">
				<h2 id="ocm-network-type"><?php esc_html_e( 'Network Type', 'one-click-multisite' ); ?></h2>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( $action ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">

				<div class="ocm-card__body">
					<fieldset class="ocm-options" aria-labelledby="ocm-network-type">
						<label class="ocm-option<?php echo $subdirectoryAllowed ? '' : ' ocm-option--disabled'; ?>">
							<input type="radio" name="subdomain_install" value="0" <?php checked( ! $preferSubdomain ); ?> <?php disabled( ! $subdirectoryAllowed ); ?>>
							<span class="ocm-option__title"><?php esc_html_e( 'Sub-directories', 'one-click-multisite' ); ?></span>
							<span class="ocm-option__example"><?php esc_html_e( 'example.com/site1', 'one-click-multisite' ); ?></span>
						</label>
						<label class="ocm-option<?php echo $subdomainAllowed ? '' : ' ocm-option--disabled'; ?>">
							<input type="radio" name="subdomain_install" value="1" <?php checked( $preferSubdomain ); ?> <?php disabled( ! $subdomainAllowed ); ?>>
							<span class="ocm-option__title"><?php esc_html_e( 'Sub-domains', 'one-click-multisite' ); ?></span>
							<span class="ocm-option__example"><?php esc_html_e( 'site1.example.com', 'one-click-multisite' ); ?></span>
							<span class="ocm-option__note"><?php esc_html_e( 'Requires wildcard DNS.', 'one-click-multisite' ); ?></span>
						</label>
					</fieldset>
				</div>

				<div class="ocm-card__footer">
					<p class="ocm-warning">
						<?php esc_html_e( 'This changes wp-config.php and .htaccess and creates the network database tables. Back up your files and database before converting.', 'one-click-multisite' ); ?>
					</p>
					<button type="submit" class="button button-primary ocm-btn" <?php disabled( ! $canConvert ); ?>>
						<?php esc_html_e( 'Convert to Multisite', 'one-click-multisite' ); ?>
					</button>
					<?php if ( ! $canConvert ) : ?>
						<span class="ocm-btn-note"><?php esc_html_e( 'Resolve the failed prerequisites above to enable the conversion.', 'one-click-multisite' ); ?></span>
					<?php endif; ?>
				</div>
			</form>
		</div>

	</div>
</div>
