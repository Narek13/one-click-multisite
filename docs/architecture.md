# Architecture

One Click Multisite is an admin-only plugin: one Tools screen, one form handler, and a conversion service that drives WordPress core's own network installer. Nothing runs on the front end.

## Boot

`one-click-multisite.php` defines `ONE_CLICK_MULTISITE_{VERSION,FILE,DIR,URL}`, loads Composer's autoloader if `vendor/` exists (otherwise a small PSR-4 fallback), and calls `Plugin::instance()->boot()` on `plugins_loaded`.

`Plugin` is a singleton service locator with lazy `??=` getters, the same shape as A11yFleet and Order Archive. `boot()` returns immediately outside wp-admin; in wp-admin it registers `ToolsPage`, `Assets` and `ConversionController`.

## Layers

```
src/
├── Plugin.php                     service locator + boot
├── Admin/
│   ├── ToolsPage.php              Tools > Convert to Multisite, Plugins action link, Network Admin notice
│   ├── ConversionController.php   admin-post handler: capability, nonce, convert, redirect
│   ├── Notice.php                 per-user transient carrying the result across the redirect
│   └── Assets.php                 assets/admin.css on the plugin screen only
└── Conversion/
    ├── Environment.php            installation facts, derived with core's network helpers
    ├── PrerequisiteChecker.php    blocking checks and warnings
    ├── PrerequisiteResult.php     value object (passes / blocks / warning)
    ├── MultisiteConverter.php     install_network(), populate_network(), then the file changes
    ├── WpConfigEditor.php         pure string transform for wp-config.php
    ├── HtaccessRules.php          pure builder for the multisite rewrite rules
    └── ConversionResult.php       value object (success, error, warnings)
templates/
├── tools-page.php                 the Tools screen
└── network-notice.php             "Conversion complete" in the Network Admin
```

## Flow

1. **Tools screen.** `ToolsPage::render()` runs `PrerequisiteChecker::check()`:
   - blocking: already a network, wp-config.php missing or read-only, no direct file system access, a non-standard port, or no network type possible;
   - warnings: only one network type possible, or the server ignores .htaccess (nginx, IIS).

   Network types WordPress disallows (`allow_subdomain_install()`, `allow_subdirectory_install()`) are disabled in the form.
2. **Submit.** `ConversionController::handle()` checks `setup_network` and the nonce, then calls `MultisiteConverter::convert()`.
3. **Convert.** Mirrors `wp-admin/network.php`:
   1. `Environment` registers the `ms_global` table names on `$wpdb` for the request.
   2. `install_network()` creates the tables, then `populate_network()` fills them. `siteid_exists` (an earlier attempt) and `no_wildcard_dns` (a warning) do not stop the conversion.
   3. .htaccess is written with `insert_with_markers()` (Apache only).
   4. wp-config.php is written through `WP_Filesystem`, keeping the file's permissions. This step is last, because it switches multisite on.
4. **Redirect.** On success the admin goes to the Network Admin (WordPress may ask them to log in again), where `ToolsPage::renderNetworkNotice()` shows the result and any warnings. On failure they go back to the Tools screen with the error.

See [`adr/0001`](../adr/0001-follow-core-network-setup.md) for why the plugin drives core's installer instead of reimplementing it.

## Testing

- **Unit (PHPUnit + Brain Monkey):** `WpConfigEditor` (insertion points, duplicates, escaping), `HtaccessRules` (sub-directory and sub-domain rules, RewriteBase), `PrerequisiteChecker` (every blocking and warning path) and the value objects.
- **End to end (DDEV, Apache):** sub-directory and sub-domain conversions over HTTP, sub-site creation, blocked conversion, bad nonce. `ddev reset-single-site` returns the site to single-site between runs. See [SETUP.md](../SETUP.md).
