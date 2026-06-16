<?php

declare(strict_types=1);

namespace MultisiteAutoEnabler\Admin;

use MultisiteAutoEnabler\Conversion\MultisiteConverter;

class ConversionController
{
    private MultisiteConverter $converter;

    public function __construct(MultisiteConverter $converter)
    {
        $this->converter = $converter;
    }

    public function handle(): void
    {
        check_admin_referer('multisite_auto_enabler_convert');

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'multisite-auto-enabler'));
        }

        $subdomainInstall = isset($_POST['subdomain_install']) && sanitize_key($_POST['subdomain_install']) === '1';

        $result = $this->converter->convert($subdomainInstall);

        if ($result->success()) {
            set_transient('multisite_auto_enabler_notice', 'success', 120);
            wp_safe_redirect(admin_url('tools.php?page=multisite-auto-enabler'));
            exit;
        }

        set_transient(
            'multisite_auto_enabler_notice',
            'error:' . $result->message(),
            30
        );

        wp_safe_redirect(admin_url('tools.php?page=multisite-auto-enabler'));
        exit;
    }
}
