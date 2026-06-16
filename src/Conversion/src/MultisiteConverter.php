<?php

declare(strict_types=1);

namespace MultisiteAutoEnabler\Conversion;

class MultisiteConverter
{
    private PrerequisiteChecker $checker;

    public function __construct(PrerequisiteChecker $checker)
    {
        $this->checker = $checker;
    }

    public function convert(bool $subdomainInstall): ConversionResult
    {
        if (!$this->checker->allPass()) {
            return new ConversionResult(
                false,
                __('One or more prerequisites are not met.', 'multisite-auto-enabler')
            );
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // wpdb only registers multisite table properties when is_multisite() or
        // WP_ALLOW_MULTISITE is defined. Neither is true here (the prerequisite check
        // enforces that), so we register them manually before doing anything else.
        // Without this, every query in populate_network() runs against an empty table
        // name, fails silently, and returns null — indistinguishable from success.
        global $wpdb;
        if (empty($wpdb->site)) {
            $bp                     = $wpdb->base_prefix;
            $wpdb->blogs            = $bp . 'blogs';
            $wpdb->blogmeta         = $bp . 'blogmeta';
            $wpdb->signups          = $bp . 'signups';
            $wpdb->site             = $bp . 'site';
            $wpdb->sitemeta         = $bp . 'sitemeta';
            $wpdb->sitecategories   = $bp . 'sitecategories';
            $wpdb->registration_log = $bp . 'registration_log';
        }

        // populate_network() only inserts rows — the tables themselves must be
        // created first. make_db_current_silent('ms_global') runs CREATE TABLE IF
        // NOT EXISTS for wp_blogs, wp_site, wp_sitemeta, wp_signups, etc.
        make_db_current_silent('ms_global');

        $domain    = (string) parse_url(get_option('siteurl'), PHP_URL_HOST);
        $path      = (string) parse_url(get_option('siteurl'), PHP_URL_PATH);
        $path      = trailingslashit($path ?: '/');
        $siteTitle = (string) get_option('blogname');
        $adminEmail = (string) get_option('admin_email');

        // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
        $networkError = populate_network(1, $domain, $adminEmail, $siteTitle, $path, (int) $subdomainInstall);

        if (is_wp_error($networkError)) {
            // 'siteid_exists' means the DB was already populated by a previous
            // conversion attempt. The tables and rows are intact — we can still
            // (re-)write wp-config.php and .htaccess.
            if (!in_array('siteid_exists', $networkError->get_error_codes(), true)) {
                return new ConversionResult(false, $networkError->get_error_message());
            }
        }

        $configResult = $this->writeWpConfig($subdomainInstall, $domain, $path);
        if (!$configResult->success()) {
            return $configResult;
        }

        $htaccessResult = $this->writeHtaccess($subdomainInstall);
        if (!$htaccessResult->success()) {
            return $htaccessResult;
        }

        wp_cache_flush();

        return new ConversionResult(true);
    }

    private function writeWpConfig(bool $subdomainInstall, string $domain, string $path): ConversionResult
    {
        $configFile = $this->checker->findWpConfig();
        if ($configFile === null) {
            return new ConversionResult(false, __('wp-config.php not found.', 'multisite-auto-enabler'));
        }

        $contents = (string) file_get_contents($configFile);
        if ($contents === '') {
            return new ConversionResult(false, __('wp-config.php is empty or unreadable.', 'multisite-auto-enabler'));
        }

        $subdomainValue = $subdomainInstall ? 'true' : 'false';

        $constants = <<<PHP

define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', {$subdomainValue} );
define( 'DOMAIN_CURRENT_SITE', '{$domain}' );
define( 'PATH_CURRENT_SITE', '{$path}' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );

PHP;

        $marker = "/* That's all, stop editing!";
        $altMarker = "/* That's all, stop editing!";

        if (strpos($contents, $marker) !== false) {
            $contents = str_replace($marker, $constants . $marker, $contents);
        } elseif (strpos($contents, $altMarker) !== false) {
            $contents = str_replace($altMarker, $constants . $altMarker, $contents);
        } else {
            $contents .= $constants;
        }

        if (file_put_contents($configFile, $contents) === false) {
            return new ConversionResult(false, __('Could not write to wp-config.php.', 'multisite-auto-enabler'));
        }

        return new ConversionResult(true);
    }

    private function writeHtaccess(bool $subdomainInstall): ConversionResult
    {
        $htaccessFile = ABSPATH . '.htaccess';

        if ($subdomainInstall) {
            // Sub-domain: each site runs on its own domain, no path prefix needed.
            $lines = [
                'RewriteEngine On',
                'RewriteBase /',
                'RewriteRule ^index\.php$ - [L]',
                '',
                'RewriteCond %{REQUEST_FILENAME} -f [OR]',
                'RewriteCond %{REQUEST_FILENAME} -d',
                'RewriteRule ^ - [L]',
                'RewriteRule ^(wp-(content|admin|includes).*) $1 [L]',
                'RewriteRule ^(.*\.php)$ $1 [L]',
                'RewriteRule . index.php [L]',
            ];
        } else {
            // Sub-directory: sites live under path prefixes, e.g. /site1/.
            $lines = [
                'RewriteEngine On',
                'RewriteBase /',
                'RewriteRule ^index\.php$ - [L]',
                '',
                '# add a trailing slash to /wp-admin',
                'RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]',
                '',
                'RewriteCond %{REQUEST_FILENAME} -f [OR]',
                'RewriteCond %{REQUEST_FILENAME} -d',
                'RewriteRule ^ - [L]',
                'RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]',
                'RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]',
                'RewriteRule . index.php [L]',
            ];
        }

        $inserted = insert_with_markers($htaccessFile, 'WordPress', $lines);

        if (!$inserted) {
            return new ConversionResult(false, __('Could not write to .htaccess.', 'multisite-auto-enabler'));
        }

        return new ConversionResult(true);
    }
}
