<?php
/**
 * Prerequisites card, shared by the convert and revert screens.
 *
 * @package OneClickMultisite
 *
 * @var list<\OneClickMultisite\Conversion\PrerequisiteResult> $prerequisites
 * @var bool $ready Whether no check blocks the action.
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template variables scoped to include context.

defined( 'ABSPATH' ) || exit;

$hasWarnings = false;
foreach ( $prerequisites as $prerequisite ) {
	$hasWarnings = $hasWarnings || $prerequisite->isWarning();
}
?>
<div class="ocm-card">
	<div class="ocm-card__header">
		<h2><?php esc_html_e( 'Prerequisites', 'one-click-multisite' ); ?></h2>
		<?php if ( ! $ready ) : ?>
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
