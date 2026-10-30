# Architecture

One Click Multisite is an admin-only plugin: one Tools screen, one form handler, and a conversion service that drives WordPress core's own network installer. Nothing runs on the front end.

## Boot

`one-click-multisite.php` defines `ONE_CLICK_MULTISITE_{VERSION,FILE,DIR,URL}`, loads Composer's autoloader if `vendor/` exists (otherwise a small PSR-4 fallback), and calls `Plugin::instance()->boot()` on `plugins_loaded`.

`Plugin` is a singleton service locator with lazy `??=` getters, the same shape as A11yFleet and Coldstash. `boot()` returns immediately outside wp-admin; in wp-admin it registers `ToolsPage`, `Assets` and `ConversionController`, and on a network also `RevertPage` and `RevertController`.

## Layers

```
src/
├── Plugin.php                     service locator + boot
├── Admin/
│   ├── ToolsPage.php              Tools > Convert to Multisite, Plugins action link, Network Admin notice
│   ├── ConversionController.php   admin-post handler: capability, nonce, convert, redirect
│   ├── RevertPage.php             Network Admin > Settings > Revert to Single Site
│   ├── RevertController.php       admin-post handler: capability, nonce, backup confirmation, revert
│   ├── Notice.php                 per-user, per-context transient carrying a result across the redirect
│   └── Assets.php                 assets/admin.css on the plugin screen only
├── Conversion/
│   ├── Environment.php            installation facts, derived with core's network helpers
│   ├── PrerequisiteChecker.php    blocking checks and warnings
│   ├── PrerequisiteResult.php     value object (passes / blocks / warning)
│   ├── MultisiteConverter.php     install_network(), populate_network(), then the file changes
│   ├── WpConfigEditor.php         pure string transform for wp-config.php
│   ├── HtaccessRules.php          pure builder for the multisite rewrite rules
│   └── ConversionResult.php       value object (success, error, warnings)
└── Revert/
    ├── RevertChecker.php          one network, one site (site 1), admin role, removable constants
    └── SingleSiteReverter.php     plugins, wp-config.php, /blog prefix, .htaccess, network tables
templates/
├── tools-page.php                 the Tools screen
├── revert-page.php                the Revert to Single Site screen
├── network-notice.php             "Conversion complete" in the Network Admin
└── partials/prerequisites.php     the checks card both screens share
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
   3. The permalink structure `populate_network()` just reset is restored, so URLs do not change.
   4. .htaccess is written with `insert_with_markers()` (Apache only).
   5. wp-config.php is written through `WP_Filesystem`, keeping the file's permissions. This step is last, because it switches multisite on.
4. **Redirect.** On success the admin goes to the Network Admin (WordPress may ask them to log in again), where `ToolsPage::renderNetworkNotice()` shows the result and any warnings. On failure they go back to the Tools screen with the error.

See [`adr/0001`](../adr/0001-follow-core-network-setup.md) for why the plugin drives core's installer instead of reimplementing it.

## Revert flow

1. **Checks.** `RevertChecker` blocks unless there is one network with one site (site 1, no legacy `ms-files.php` uploads), the current user has a real Administrator role on the main site, and every multisite constant is a plain `define()` in wp-config.php that `WpConfigEditor::remove()` can take out. Users without a role on the main site and servers without .htaccess are warnings.
2. **Submit.** `RevertController::handle()` checks `manage_network_options`, the nonce and the backup confirmation.
3. **Revert.** `SingleSiteReverter` merges network-activated plugins into `active_plugins`, writes wp-config.php without the constants (if that fails, nothing has changed), removes a `/blog` permalink prefix, restores the single-site .htaccess rules, and drops the network tables.
4. **Redirect.** The admin lands on Tools > Convert to Multisite of the single site (possibly after logging in again), which shows the result.

See [`adr/0004`](../adr/0004-revert-only-single-site-networks.md).

## Testing

- **Unit (PHPUnit + Brain Monkey):** `WpConfigEditor` (insertion points, duplicates, escaping), `HtaccessRules` (sub-directory and sub-domain rules, RewriteBase), `PrerequisiteChecker` (every blocking and warning path) and the value objects.
- **End to end (DDEV, Apache):** convert → revert round trips for both network types over HTTP (wp-config.php byte-identical afterwards, URLs unchanged, network-activated plugins kept), revert blocked by a second site and by the lockout guard, missing backup confirmation, bad nonces. See [SETUP.md](../SETUP.md).
