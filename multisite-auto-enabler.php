<?php
/**
 * Plugin Name: Multisite Auto-Enabler
 * Description: Convert your single WordPress site into a multisite network with one click.
 * Version: 1.0
 * Author: Your Name
 */

add_action('init', function () {
    if (!is_multisite() && !defined('WP_ALLOW_MULTISITE')) {
        maybe_add_allow_multisite();
    }
});

function maybe_add_allow_multisite() {
    $config_path = ABSPATH . 'wp-config.php';

    if (!is_writable($config_path)) {
        return;
    }

    $contents = file_get_contents($config_path);
    if (strpos($contents, "WP_ALLOW_MULTISITE") === false) {
        $allow_multisite = "\ndefine('WP_ALLOW_MULTISITE', true);\n";
        $contents = preg_replace('/(\/\* That\'s all, stop editing.*)/', $allow_multisite . '$1', $contents);
        file_put_contents($config_path, $contents);
    }
}

add_action('init', function () {
    add_action('admin_footer', 'multisite_setup_button');
});

function multisite_setup_button() {
    $screen = get_current_screen();
    //var_dump($screen);die;
    if ($screen->base !== 'network') return;

    ?>
    <script>
    document.querySelectorAll('.wrap > p').forEach(el => {
        if (el.textContent.includes('To enable the Network feature')) {
            el.remove();
        }
    });
    </script>
    <form method="post">
        <?php wp_nonce_field('auto_setup_multisite_action', 'auto_setup_multisite_nonce'); ?>
        <?php submit_button('Finish Multisite Setup Automatically', 'primary', 'auto_setup_multisite'); ?>
    </form>
    <?php
}

add_action('admin_init', function () {

    if (isset($_POST['auto_setup_multisite']) && current_user_can('manage_options') &&
        check_admin_referer('auto_setup_multisite_action', 'auto_setup_multisite_nonce')) {

        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;

        $wp_config_path = ABSPATH . 'wp-config.php';
        $htaccess_path  = ABSPATH . '.htaccess';

        if ($wp_filesystem->is_writable($wp_config_path) && $wp_filesystem->is_writable($htaccess_path)) {
            $wp_config = $wp_filesystem->get_contents($wp_config_path);

            if (
                strpos($wp_config, "define('MULTISITE', true)") === false
                || strpos($wp_config, "define('SUBDOMAIN_INSTALL'") === false
            ) {
                $domain = $_SERVER['HTTP_HOST'];
                $multisite_code = <<<PHP

/* Multisite */
define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', false);
define('DOMAIN_CURRENT_SITE', '$domain');
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);

PHP;
                $wp_config = preg_replace('/(\/\* That\'s all, stop editing.*)/', $multisite_code . '$1', $wp_config);
                $wp_filesystem->put_contents($wp_config_path, $wp_config);
            }

            $rules = <<<HTACCESS
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]

# Add a trailing slash to /wp-admin
RewriteRule ^wp-admin$ wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^(wp-(content|admin|includes).*) $1 [L]
RewriteRule ^(.*\.php)$ $1 [L]
RewriteRule . index.php [L]
HTACCESS;
            $wp_filesystem->put_contents($htaccess_path, $rules);

            wp_redirect(admin_url('/network/'));
            exit;
        }
    }
});
