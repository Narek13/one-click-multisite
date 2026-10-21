# Local setup

## DDEV

```bash
cd one-click-multisite
ddev start
```

On first run the `post-start` hook (`.ddev/commands/setup.sh`):

1. downloads and installs WordPress into `.ddev/wordpress` (gitignored);
2. sets pretty permalinks and removes the sample plugins;
3. activates this plugin.

The site runs on **Apache** (`webserver_type: apache-fpm`), so the .htaccess rules the plugin writes are actually used. The repo is mounted read-only at `wp-content/plugins/one-click-multisite`, so edits here are live immediately.

| Command | What it does |
|---|---|
| `ddev launch wp-admin` | wp-admin, `admin` / `admin` |
| `ddev launch /wp-admin/tools.php?page=one-click-multisite` | the plugin screen |
| `ddev reset-single-site` | undo a conversion: removes the plugin's wp-config.php block, drops the network and sub-site tables, restores single-site .htaccess |
| `ddev composer install` | dev dependencies inside the container |

### Testing a conversion

1. Turn on debugging once: `ddev wp config set WP_DEBUG true --raw && ddev wp config set WP_DEBUG_LOG true --raw`.
2. Convert from the Tools screen. Sub-directories are only offered while the site has no posts older than a month; to re-enable them on an old dev database, run `ddev wp db query "UPDATE wp_posts SET post_date=NOW(), post_date_gmt=UTC_TIMESTAMP()"`.
3. Check `wp-content/debug.log` inside the container (`ddev exec -d /var/www/html/.ddev/wordpress tail wp-content/debug.log`).
4. `ddev reset-single-site` and repeat for the other network type.

To test the nginx path, set `webserver_type: nginx-fpm` in `.ddev/config.yaml` and `ddev restart`: the Tools screen shows the rewrite-rules warning, and the Network Admin notice points to the nginx guide.

DDEV on macOS uses Mutagen, so files the plugin writes inside the container (wp-config.php, .htaccess) can take a moment to appear on the host.

## Existing WordPress install

Copy or symlink this folder into `wp-content/plugins/` and activate it. No build step is needed: the plugin falls back to its own PSR-4 autoloader when `vendor/` is absent.

## Quality checks

```bash
composer install
composer phpcs        # WordPress-Extra + WordPress-Docs + PHPCompatibilityWP
composer phpstan      # level 6
composer phpunit
composer test         # all three
```

The same checks run in CI (`.github/workflows/php-qa.yml`, `phpunit.yml`) on PHP 8.1 to 8.4.

## Plugin Check

Run it against the built zip, not the repo: the repo contains `.ddev/wordpress` and `vendor/`, which Plugin Check would scan too.

```bash
bash bin/build-zip.sh
ddev wp plugin install plugin-check --activate
ddev wp plugin check /var/www/html/dist/one-click-multisite --slug=one-click-multisite
```

## Translations template

```bash
bash bin/build-zip.sh
ddev exec "wp i18n make-pot /var/www/html/dist/one-click-multisite /var/www/html/languages/one-click-multisite.pot --slug=one-click-multisite --domain=one-click-multisite"
```
