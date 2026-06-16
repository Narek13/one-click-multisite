<?php

declare(strict_types=1);

namespace MultisiteAutoEnabler\Admin;

use MultisiteAutoEnabler\Conversion\PrerequisiteChecker;

class ToolsPage
{
    private PrerequisiteChecker $checker;

    public function __construct(PrerequisiteChecker $checker)
    {
        $this->checker = $checker;
    }

    public function register(): void
    {
        add_management_page(
            __('Convert to Multisite', 'multisite-auto-enabler'),
            __('Convert to Multisite', 'multisite-auto-enabler'),
            'manage_options',
            'multisite-auto-enabler',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'multisite-auto-enabler'));
        }

        $prerequisites   = $this->checker->check();
        $allPass         = $this->checker->allPass();
        $notice          = get_transient('multisite_auto_enabler_notice');
        if ($notice) {
            delete_transient('multisite_auto_enabler_notice');
        }
        ?>
        <div class="wrap mae-wrap">
            <h1><?php esc_html_e('Convert to Multisite', 'multisite-auto-enabler'); ?></h1>
            <p class="mae-subtitle"><?php esc_html_e('Convert this single-site WordPress installation into a multisite network.', 'multisite-auto-enabler'); ?></p>

            <?php if ($notice === 'success'): ?>
                <div class="notice notice-success mae-notice">
                    <p>
                        <strong><?php esc_html_e('Conversion complete!', 'multisite-auto-enabler'); ?></strong>
                        <?php esc_html_e('Your site has been converted to a multisite network.', 'multisite-auto-enabler'); ?>
                        <?php esc_html_e('Please log in again to access the Network Admin.', 'multisite-auto-enabler'); ?>
                        <a href="<?php echo esc_url(admin_url('network/')); ?>">
                            <?php esc_html_e('Go to Network Admin &rarr;', 'multisite-auto-enabler'); ?>
                        </a>
                    </p>
                </div>
            <?php elseif ($notice && strpos($notice, 'error:') === 0): ?>
                <div class="notice notice-error mae-notice">
                    <p><?php echo esc_html(substr($notice, 6)); ?></p>
                </div>
            <?php endif; ?>

            <div class="mae-cards">

                <?php /* Card 1: Prerequisites */ ?>
                <div class="mae-card">
                    <div class="mae-card__header">
                        <h2><?php esc_html_e('Prerequisites', 'multisite-auto-enabler'); ?></h2>
                        <?php if ($allPass): ?>
                            <span class="mae-badge mae-badge--pass"><?php esc_html_e('All checks passed', 'multisite-auto-enabler'); ?></span>
                        <?php else: ?>
                            <span class="mae-badge mae-badge--fail"><?php esc_html_e('Action required', 'multisite-auto-enabler'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mae-card__rows">
                        <?php foreach ($prerequisites as $prereq): ?>
                            <div class="mae-row <?php echo $prereq->passes() ? 'mae-row--pass' : 'mae-row--fail'; ?>">
                                <span class="mae-row__icon" aria-hidden="true">
                                    <?php echo $prereq->passes() ? '&#10003;' : '&#10007;'; ?>
                                </span>
                                <div class="mae-row__body">
                                    <span class="mae-row__label"><?php echo esc_html($prereq->label()); ?></span>
                                    <?php if (!$prereq->passes() && $prereq->message()): ?>
                                        <span class="mae-row__message"><?php echo esc_html($prereq->message()); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php /* Card 2: Conversion form */ ?>
                <div class="mae-card">
                    <div class="mae-card__header">
                        <h2><?php esc_html_e('Network Type', 'multisite-auto-enabler'); ?></h2>
                    </div>
                    <div class="mae-card__body">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('multisite_auto_enabler_convert'); ?>
                            <input type="hidden" name="action" value="multisite_auto_enabler_convert">

                            <div class="mae-options">
                                <label class="mae-option">
                                    <input type="radio" name="subdomain_install" value="0" checked>
                                    <span class="mae-option__title"><?php esc_html_e('Sub-directories', 'multisite-auto-enabler'); ?></span>
                                    <span class="mae-option__example"><?php esc_html_e('example.com/site1', 'multisite-auto-enabler'); ?></span>
                                </label>
                                <label class="mae-option">
                                    <input type="radio" name="subdomain_install" value="1">
                                    <span class="mae-option__title"><?php esc_html_e('Sub-domains', 'multisite-auto-enabler'); ?></span>
                                    <span class="mae-option__example"><?php esc_html_e('site1.example.com', 'multisite-auto-enabler'); ?></span>
                                    <span class="mae-option__note"><?php esc_html_e('Requires wildcard DNS.', 'multisite-auto-enabler'); ?></span>
                                </label>
                            </div>

                            <div class="mae-card__footer">
                                <p class="mae-warning">
                                    <?php esc_html_e('Warning: This action modifies wp-config.php and .htaccess. Back up your site before proceeding.', 'multisite-auto-enabler'); ?>
                                </p>
                                <button
                                    type="submit"
                                    class="button button-primary mae-btn"
                                    <?php disabled(!$allPass); ?>
                                >
                                    <?php esc_html_e('Convert to Multisite', 'multisite-auto-enabler'); ?>
                                </button>
                                <?php if (!$allPass): ?>
                                    <span class="mae-btn-note"><?php esc_html_e('Resolve all prerequisite issues above to enable conversion.', 'multisite-auto-enabler'); ?></span>
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
