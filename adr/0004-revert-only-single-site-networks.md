# 4. Revert to a single site, only while the network holds just the main site

- **Status:** Accepted (replaces the "reverting is manual" consequence of ADR-0001)
- **Date:** 2026-10-30

## Context
WordPress has no way back from multisite. Undoing a conversion by hand means editing wp-config.php and .htaccess again and dropping the network tables, which is the same error-prone work the plugin exists to remove.

A network with more than one site cannot become a single site without losing data: each extra site has its own `wp_N_*` tables and `uploads/sites/N/` folder, which a single-site install never reads. Super admin rights live in the network tables, so dropping them can also lock an admin out.

## Decision
Offer **Network Admin → Settings → Revert to Single Site**, and only allow it when nothing can be lost (`RevertChecker`):

- exactly one network and one site, which is site 1 (single-site table prefix) on a post-3.5 network (no `ms-files.php` uploads);
- the current user holds a real Administrator role on the main site;
- every multisite constant is a plain one-line `define()` in wp-config.php itself, so `WpConfigEditor::remove()` can take it out and verify nothing is left.

`SingleSiteReverter` then runs in the order that keeps the site working if a step fails: merge network-activated plugins into the site's `active_plugins`; write wp-config.php without the constants (the point of no return; a failure here changes nothing); drop the `/blog` permalink prefix a hand-made sub-directory network adds; restore the single-site .htaccess rules; drop the network tables. Accounts without a role on the main site are kept and flagged as a warning beforehand.

The same work exposed that `populate_network()` resets the main site's permalink structure, so the converter now restores it right after.

## Consequences
- Convert → revert returns the original wp-config.php byte for byte and keeps URLs, plugins, content and users (verified end to end in DDEV for both network types).
- Networks with extra sites have to delete them first. The screen says so, with the count.
- Network-level settings (network options, sign-ups) are deleted with the tables; they have no single-site equivalent.
- The `users.spam`/`deleted` columns stay; single-site WordPress ignores them.
