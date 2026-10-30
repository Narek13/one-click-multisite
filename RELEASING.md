# Releasing

One Click Multisite is published at https://wordpress.org/plugins/one-click-multisite
from the SVN repository https://plugins.svn.wordpress.org/one-click-multisite.
Releases are automated: pushing a `v*` tag publishes to SVN. Nobody commits to SVN by hand.

## Before approval
The SVN repository only exists once the WordPress.org review team approves the plugin.
Until then the deploy steps in both workflows skip themselves (they check for the
SVN secrets), so tags still build, run Plugin Check and attach the zip to the GitHub
release without failing.

1. Confirm the slug `one-click-multisite` is free on https://wordpress.org/plugins/.
2. Build the zip: push a tag (it is attached to the GitHub release) or run
   `bash bin/build-zip.sh` → `dist/one-click-multisite.zip`.
3. Upload it at https://wordpress.org/plugins/developers/add/ and reply to review
   emails in-thread.
4. On approval, add the secrets below, then deploy the approved version: Actions →
   Build & Distribute → Run workflow → *Use workflow from* the version's tag.

## One-time setup
Repository secrets on GitHub (Settings → Secrets and variables → Actions):
- `SVN_USERNAME`: `nareknovembit` (the case-sensitive wp.org username).
- `SVN_PASSWORD`: the SVN password from the "Account & Security" section of the
  WordPress.org profile (separate from the login password).

## Cutting a release
1. Bump the version in three places: the `Version:` header and `ONE_CLICK_MULTISITE_VERSION`
   in `one-click-multisite.php`, and `Stable tag:` in `readme.txt`. Add a changelog
   entry and an upgrade notice.
2. Regenerate the translation template (see SETUP.md).
3. `composer test` green.
4. Commit, then `git tag vX.Y.Z && git push origin main vX.Y.Z`.

`.github/workflows/build-and-distribute.yml` then:
- fails if the three version numbers or the tag disagree;
- builds `dist/one-click-multisite.zip` (`bin/build-zip.sh`; what is left out is
  defined once, in `.distignore`);
- runs Plugin Check on the built package;
- attaches the zip to the GitHub release;
- deploys the built package to SVN `trunk/` and `tags/X.Y.Z/`, and syncs
  `.wordpress-org/` to SVN `assets/` (once the SVN secrets exist).

## Plugin page assets
`.wordpress-org/` holds exactly what goes into SVN `assets/`: icons (PNG + SVG),
both banners and `screenshot-1..2.png` (captions are `== Screenshots ==` in
readme.txt). The sources to re-render them are in `docs/wporg-assets-source/`.

Changes to `readme.txt` or `.wordpress-org/` alone go out without a release:
pushing them to `main` runs `.github/workflows/wporg-readme-assets.yml`, which
updates the readme in SVN `trunk/` and the stable tag, and syncs `assets/`.
Do not bump the version for those.
