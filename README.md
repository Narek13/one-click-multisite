# One Click Multisite

Convert a single-site WordPress installation into a multisite network from **Tools → Convert to Multisite**, without hand-editing wp-config.php or .htaccess.

Status: **1.0.0, not yet released on WordPress.org**. See [SUBMISSION.md](SUBMISSION.md).

## How it works

The plugin runs WordPress core's own network installer, the same code behind Tools → Network Setup. It then applies the two snippets that screen normally asks you to paste:

1. **Prerequisites.** The site must be a single site, wp-config.php must be writable with direct file system access, and the site address must not use a non-standard port. Network types WordPress disallows on this install are disabled. Servers that ignore .htaccess (nginx) get a warning instead of a block.
2. **Network tables.** `install_network()` and `populate_network()`, exactly as `wp-admin/network.php` calls them.
3. **.htaccess.** The multisite rules core prints, written with `insert_with_markers()`.
4. **wp-config.php.** The multisite constants, inserted above the "stop editing" line. Values are escaped with `var_export()`, existing constants are skipped, and the file keeps its permissions.
5. **Network Admin.** The admin lands there, with a notice listing any follow-up (wildcard DNS, nginx rules).

Design notes: [docs/architecture.md](docs/architecture.md) and [adr/](adr/).

## Requirements

- WordPress 6.4+
- PHP 8.1+
- Direct file system access (no FTP/SSH credential prompt)
- Apache with mod_rewrite for automatic rewrite rules; on nginx, add them to the server config by hand

## Development

```bash
composer install
ddev start          # WordPress on Apache, plugin activated (see SETUP.md)
composer test       # PHPCS + PHPStan + PHPUnit
bash bin/build-zip.sh
```

## License

GPL-2.0-or-later
