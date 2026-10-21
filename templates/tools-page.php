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
 * @var string $action
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template variables scoped to include context.

defined( 'ABSPATH' ) || exit;

$hasWarnings = false;
foreach ( $prerequisites as $prerequisite ) {
	$hasWarnings = $hasWarnings || $prerequisite->isWarning();
}

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

	<div class="ocm-cards">

		<div class="ocm-card">
			<div class="ocm-card__header">
				<h2><?php esc_html_e( 'Prerequisites', 'one-click-multisite' ); ?></h2>
				<?php if ( ! $canConvert ) : ?>
					<span class="ocm-badge ocm-badge--fail"><?php esc_html_e( 'Action required', 'one-click-multisite' ); ?></span>
				<?php elseif ( $hasWarnings ) : ?>
					<span class="ocm-badge ocm-badge--warn"><?php esc_html_e( 'Ready, with notes', 'one-click-multisite' ); ?></span>
				<?php else : ?>
					<span class="ocm-badge ocm-badge--pass"><?php esc_html_e( 'All checks passed', 'one-click-multisite' ); ?></span>
				<?php endif; ?>
			</div>
			<ul class="ocm-card__rows">
				<?php foreach ( $prerequisites as $prerequisite ) : ?>
					<?php
					if ( $prerequisite->passes() ) {
						$rowClass    = 'ocm-row--pass';
						$icon        = '✓';
						$statusLabel = __( 'Passed:', 'one-click-multisite' );
					} elseif ( $prerequisite->isWarning() ) {
						$rowClass    = 'ocm-row--warn';
						$icon        = '!';
						$statusLabel = __( 'Note:', 'one-click-multisite' );
					} else {
						$rowClass    = 'ocm-row--fail';
						$icon        = '✗';
						$statusLabel = __( 'Failed:', 'one-click-multisite' );
					}
					?>
					<li class="ocm-row <?php echo esc_attr( $rowClass ); ?>">
						<span class="ocm-row__icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
						<div class="ocm-row__body">
							<span class="ocm-row__label">
								<span class="screen-reader-text"><?php echo esc_html( $statusLabel ); ?></span>
								<?php echo esc_html( $prerequisite->label() ); ?>
							</span>
							<?php if ( ! $prerequisite->passes() && '' !== $prerequisite->message() ) : ?>
								<span class="ocm-row__message"><?php echo esc_html( $prerequisite->message() ); ?></span>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

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
