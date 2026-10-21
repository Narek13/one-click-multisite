# 1. Follow core's Network Setup, then write the files it asks for

- **Status:** Accepted
- **Date:** 2026-10-21

## Context
WordPress already ships a network installer: Tools → Network Setup (`wp-admin/network.php`). It creates the network tables, populates the network, and then prints a wp-config.php snippet and an .htaccess snippet for the admin to paste by hand. Those manual edits are the step people get wrong, and the reason this plugin exists.

Re-implementing the installer would drift from core over time: the schema it creates (including the `users.spam`/`deleted` columns) and the snippets it prints change between releases.

## Decision
Run core's own functions in the order `network.php` runs them: register the `ms_global` table names on `$wpdb`, `install_network()`, `populate_network()`. Derive domain, path and rewrite base with core's helpers (`get_clean_basedomain()`, `allow_subdomain_install()`, `allow_subdirectory_install()`, the `network_step2()` path logic). Then apply the two snippets automatically:

- **.htaccess** through core's `insert_with_markers()`, with the exact rules `network_step2()` prints (`HtaccessRules`). Written first, because those rules still serve a single site.
- **wp-config.php** through `WP_Filesystem` (direct method only), as a pure string transform (`WpConfigEditor`): values via `var_export()`, constants that already exist are skipped, inserted above the "stop editing" line or the wp-settings.php include, never appended. Written last, because it switches multisite on.

Conversion is refused when WordPress would need FTP/SSH credentials, since the plugin has no place to ask for them.

## Consequences
- A one-click network is identical to a hand-made one, and core fixes to the installer flow through.
- `no_wildcard_dns` from `populate_network()` is treated as the warning it is; core does the same.
- nginx and IIS ignore .htaccess, so on those servers the admin still adds rewrite rules by hand. The prerequisites screen says so up front.
- Reverting is manual (or `ddev reset-single-site` in development).
