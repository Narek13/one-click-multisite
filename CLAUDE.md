# CLAUDE.md

One Click Multisite: a WordPress plugin that converts a single-site installation into a multisite network from **Tools → Convert to Multisite**. Intended for WordPress.org (see `SUBMISSION.md`).

## Rules

- **Never auto-commit.** Make edits and stop. The user creates commits themselves.
- No `syde/` or `inpsyde/` packages. This is a personal plugin targeting WordPress.org; public, neutral dependencies only.
- **wp.org-bound:** WordPress Coding Standards (`phpcs.xml.dist`) and the plugin guidelines: prefix globals, escape output, sanitize input, no remote code, no runtime Composer packages.

## Structure

Deliberately mirrors **A11yFleet** and **Order Archive for WooCommerce** (sibling folders in `wp-content/plugins/`), the reference for how these plugins are built: PSR-4 `src/` (`OneClickMultisite\` → `src/`), a `final class Plugin` singleton service locator, constants plus a fallback autoloader in the main file, `templates/`, `adr/`, `docs/`, `bin/build-zip.sh`, and the same phpcs/phpstan/phpunit/DDEV/CI setup.

- `src/Plugin.php`: service locator and boot (admin only).
- `src/Admin/`: `ToolsPage`, `ConversionController`, `Notice`, `Assets`.
- `src/Conversion/`: `Environment` (core's network helpers), `PrerequisiteChecker`, `MultisiteConverter`, the pure `WpConfigEditor` and `HtaccessRules`, and value objects.

The converter runs core's own installer (`install_network()`, `populate_network()`) the way `wp-admin/network.php` does, then writes .htaccess and finally wp-config.php. See `docs/architecture.md` and `adr/0001`.

Deliberate divergence from the references: DDEV runs **Apache** (`apache-fpm`), so .htaccess is exercised (`adr/0003`).

## Conventions

- PHP 8.1+, `declare(strict_types=1)`, `final` classes, constructor promotion, camelCase methods and variables.
- WordPress formatting (tabs, padded parentheses, `array()`, Yoda conditions), not PSR-12. PHPStan level 6.
- Capability `setup_network` everywhere, as core's Network Setup uses.
- Unit tests: PHPUnit 9.6 + Brain Monkey, `final class … extends TestCase`, GIVEN/WHEN/THEN docblocks. The conversion itself is tested end to end in DDEV, not mocked.

## Local development

```bash
composer install            # dev tooling
ddev start                  # WordPress on Apache, plugin activated
ddev reset-single-site      # undo a conversion
```

Admin: https://one-click-multisite.ddev.site/wp-admin (admin / admin). Details in `SETUP.md`.

The host shell's default PHP is 8.0; use `export PATH="/opt/homebrew/opt/php@8.3/bin:$PATH"` (or `ddev composer …`) for the toolchain.

## Lint, test, release

```bash
composer phpcs | phpcs:fix | phpstan | phpunit
composer test               # all three (CI gate)
bash bin/build-zip.sh       # dist/one-click-multisite.zip, exclusions from .distignore
```

Run Plugin Check against `dist/`, not the repo (see `SETUP.md`). Tag `vX.Y.Z` to build the release zip in CI.
