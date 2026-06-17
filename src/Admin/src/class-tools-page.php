<?php
/**
 * Tools page renderer.
 *
 * @package OneClickMultisite
 */

declare( strict_types=1 );

namespace OneClickMultisite\Admin;

use OneClickMultisite\Conversion\PrerequisiteChecker;

/**
 * Renders the Tools > Convert to Multisite admin page.
 */
class ToolsPage {

	/**
	 * Prerequisite checker service.
	 *
	 * @var PrerequisiteChecker
	 */
	private PrerequisiteChecker $checker;

	/**
	 * Constructor.
	 *
	 * @param PrerequisiteChecker $checker Prerequisite checker.
	 */
	public function __construct( PrerequisiteChecker $checker ) {
		$this->checker = $checker;
	}

	/**
	 * Registers the Tools submenu page.
	 *
	 * @return void
	 */
	public function register(): void {
		add_management_page(
			__( 'Convert to Multisite', 'one-click-multisite' ),
			__( 'Convert to Multisite', 'one-click-multisite' ),
			'manage_options',
			'one-click-multisite',
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the admin page HTML.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'one-click-multisite' ) );
		}

		$prerequisites = $this->checker->check();
		$all_pass      = $this->checker->all_pass();
		$notice        = get_transient( 'one_click_multisite_notice' );
		if ( $notice ) {
			delete_transient( 'one_click_multisite_notice' );
		}
		?>
		<div class="wrap ocm-wrap">
			<h1><?php esc_html_e( 'Convert to Multisite', 'one-click-multisite' ); ?></h1>
			<p class="ocm-subtitle"><?php esc_html_e( 'Convert this single-site WordPress installation into a multisite network.', 'one-click-multisite' ); ?></p>

			<?php if ( 'success' === $notice ) : ?>
				<div class="notice notice-success ocm-notice">
					<p>
						<strong><?php esc_html_e( 'Conversion complete!', 'one-click-multisite' ); ?></strong>
						<?php esc_html_e( 'Your site has been converted to a multisite network.', 'one-click-multisite' ); ?>
						<?php esc_html_e( 'Please log in again to access the Network Admin.', 'one-click-multisite' ); ?>
						<a href="<?php echo esc_url( admin_url( 'network/' ) ); ?>">
							<?php esc_html_e( 'Go to Network Admin &rarr;', 'one-click-multisite' ); ?>
						</a>
					</p>
				</div>
			<?php elseif ( $notice && 0 === strpos( $notice, 'error:' ) ) : ?>
				<div class="notice notice-error ocm-notice">
					<p><?php echo esc_html( substr( $notice, 6 ) ); ?></p>
				</div>
			<?php endif; ?>

			<div class="ocm-cards">

				<?php /* Card 1: Prerequisites */ ?>
				<div class="ocm-card">
					<div class="ocm-card__header">
						<h2><?php esc_html_e( 'Prerequisites', 'one-click-multisite' ); ?></h2>
						<?php if ( $all_pass ) : ?>
							<span class="ocm-badge ocm-badge--pass"><?php esc_html_e( 'All checks passed', 'one-click-multisite' ); ?></span>
						<?php else : ?>
							<span class="ocm-badge ocm-badge--fail"><?php esc_html_e( 'Action required', 'one-click-multisite' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="ocm-card__rows">
						<?php foreach ( $prerequisites as $prereq ) : ?>
							<div class="ocm-row <?php echo $prereq->passes() ? 'ocm-row--pass' : 'ocm-row--fail'; ?>">
								<span class="ocm-row__icon" aria-hidden="true">
									<?php echo $prereq->passes() ? '&#10003;' : '&#10007;'; ?>
								</span>
								<div class="ocm-row__body">
									<span class="ocm-row__label"><?php echo esc_html( $prereq->label() ); ?></span>
									<?php if ( ! $prereq->passes() && $prereq->message() ) : ?>
										<span class="ocm-row__message"><?php echo esc_html( $prereq->message() ); ?></span>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<?php /* Card 2: Conversion form */ ?>
				<div class="ocm-card">
					<div class="ocm-card__header">
						<h2><?php esc_html_e( 'Network Type', 'one-click-multisite' ); ?></h2>
					</div>
					<div class="ocm-card__body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'one_click_multisite_convert' ); ?>
							<input type="hidden" name="action" value="one_click_multisite_convert">

							<div class="ocm-options">
								<label class="ocm-option">
									<input type="radio" name="subdomain_install" value="0" checked>
									<span class="ocm-option__title"><?php esc_html_e( 'Sub-directories', 'one-click-multisite' ); ?></span>
									<span class="ocm-option__example"><?php esc_html_e( 'example.com/site1', 'one-click-multisite' ); ?></span>
								</label>
								<label class="ocm-option">
									<input type="radio" name="subdomain_install" value="1">
									<span class="ocm-option__title"><?php esc_html_e( 'Sub-domains', 'one-click-multisite' ); ?></span>
									<span class="ocm-option__example"><?php esc_html_e( 'site1.example.com', 'one-click-multisite' ); ?></span>
									<span class="ocm-option__note"><?php esc_html_e( 'Requires wildcard DNS.', 'one-click-multisite' ); ?></span>
								</label>
							</div>

							<div class="ocm-card__footer">
								<p class="ocm-warning">
									<?php esc_html_e( 'Warning: This action modifies wp-config.php and .htaccess. Back up your site before proceeding.', 'one-click-multisite' ); ?>
								</p>
								<button
									type="submit"
									class="button button-primary ocm-btn"
									<?php disabled( ! $all_pass ); ?>
								>
									<?php esc_html_e( 'Convert to Multisite', 'one-click-multisite' ); ?>
								</button>
								<?php if ( ! $all_pass ) : ?>
									<span class="ocm-btn-note"><?php esc_html_e( 'Resolve all prerequisite issues above to enable conversion.', 'one-click-multisite' ); ?></span>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</div>

			</div>
		</div>
		<?php
	}
}
