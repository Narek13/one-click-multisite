# WordPress.org submission checklist

Steps to get One Click Multisite listed on WordPress.org. ✅ is done in the codebase; ⬜ needs doing.

## Code
- ✅ GPL-2.0-or-later license and `LICENSE` file.
- ✅ `readme.txt` valid: Contributors `nareknovembit`, Stable tag `1.0.0` matching the plugin header and `ONE_CLICK_MULTISITE_VERSION`, Tested up to 7.1, Installation / FAQ / Privacy / Screenshots / Upgrade Notice.
- ✅ Text domain equals the slug (`one-click-multisite`); no `load_plugin_textdomain()` (WordPress.org loads translations itself); `languages/one-click-multisite.pot` shipped.
- ✅ No Plugin URI pointing at a page that does not exist.
- ✅ Globals prefixed; input sanitized, output escaped; nonce and `setup_network` capability on the form handler.
- ✅ No external HTTP calls of its own and no tracking. Core's wildcard-DNS probe (sub-domain installs only) is disclosed under `== Privacy ==`.
- ✅ wp-config.php written through `WP_Filesystem`, values escaped with `var_export()`, no duplicate `define()`s; .htaccess through `insert_with_markers()`.
- ✅ No runtime Composer packages; the zip ships no `vendor/`.
- ✅ `uninstall.php` removes the plugin's transients and leaves the network intact.
- ✅ `composer test` green (PHPCS, PHPStan level 6, PHPUnit).
- ✅ Plugin Check on the release zip: no errors, no warnings.
- ✅ In DDEV (WordPress 7.1.3, Apache, `WP_DEBUG` on): sub-directory and sub-domain conversions over HTTP, sub-site created and served, blocked conversion with a read-only wp-config.php, bad nonce rejected (403), and a clean debug log.
- ✅ nginx path in DDEV (`webserver_type: nginx-fpm`): rewrite-rules warning on the Tools screen, conversion completes, follow-up notice in the Network Admin.
- ⬜ Clicked through by a human in a browser, including the re-login after converting.

## Decisions (you)
- ⬜ Slug `one-click-multisite` is free on https://wordpress.org/plugins/.
- ⬜ Employment contract allows publishing this personal plugin.

## Build the zip
- Locally: `bash bin/build-zip.sh` → `dist/one-click-multisite.zip`.
- Or push a `v1.0.0` tag: `.github/workflows/build-and-distribute.yml` checks that the version numbers agree, builds the zip and attaches it to the GitHub release.
- What is left out is defined once, in `.distignore`.

## Submit
1. https://wordpress.org/plugins/developers/add/ and upload the zip.
2. Review takes days to a few weeks; reply to review emails in-thread.
3. On approval you get an SVN repository.

## After approval (SVN)
- Commit the plugin to `trunk/`, copy to `tags/1.0.0/`.
- Copy everything in `.wordpress-org/` (except `source/`) into the SVN `assets/` folder: icons (PNG and SVG), both banners, `screenshot-1.png` (its caption is in `== Screenshots ==` in readme.txt). Sources to re-render them are in `.wordpress-org/source/`.
- Releases: bump the version in `one-click-multisite.php` (header and `ONE_CLICK_MULTISITE_VERSION`) and the readme.txt Stable tag, regenerate the POT, commit to `trunk`, copy to a new `tags/x.y.z`.
