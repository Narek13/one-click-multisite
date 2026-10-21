# 3. DDEV with Apache for local development

- **Status:** Accepted
- **Date:** 2026-10-21

## Context
The plugin rewrites wp-config.php and .htaccess and creates database tables, so it has to be tested against a real WordPress that can be converted and reset repeatedly. A11yFleet and Order Archive use DDEV with nginx; nginx ignores .htaccess, which would hide half of what this plugin does.

## Decision
Use the same DDEV layout as the other plugins (WordPress in `.ddev/wordpress`, repo mounted read-only into `wp-content/plugins/${DDEV_PROJECT}`, `post-start` setup hook), with `webserver_type: apache-fpm`. `wp-cli.yml` declares `mod_rewrite` so `wp rewrite flush --hard` writes .htaccess. `ddev reset-single-site` undoes a conversion (wp-config.php block, network and sub-site tables, .htaccess).

## Consequences
- Every conversion path, including .htaccess, runs for real in development.
- The nginx path is exercised by switching `webserver_type` temporarily.
- `.ddev/` is excluded from the distributed plugin (`.distignore`).
