# CLAUDE.md

WordPress plugin that converts a single-site installation to a multisite network with one click from **Tools > Convert to Multisite**.

## Rules

- **Never auto-commit.** Make edits and stop. The user creates commits themselves.
- No `syde/` or `inpsyde/` packages — this is a personal plugin targeting WordPress.org.

## Architecture

Modular OOP design without an external DI container:

- `src/Plugin.php` — bootstrapper (equivalent to `Inpsyde\Modularity\Package`)
- `src/Container.php` — minimal PSR-11 container
- `src/Module/ServiceModule.php` / `ExecutableModule.php` — interfaces
- `src/OneClickMultisiteModule.php` — root services (url, basename, version)
- `src/Conversion/` — `PrerequisiteChecker`, `MultisiteConverter` and value objects
- `src/Admin/` — `AdminModule`, `ToolsPage`, `ConversionController`

PSR-4 namespaces: `OneClickMultisite\` → `src/`, with dual-path mappings for sub-modules (see `composer.json`).

## Local development

```bash
composer install   # install deps + generate autoloader
ddev start         # start DDEV; WordPress is downloaded and configured on first run
```

DDEV provides PHP 8.1 for the dev toolchain (PHPUnit, PHPCS) even though production supports PHP 7.4+.

Admin URL: https://one-click-multisite.ddev.site/wp-admin (admin / admin)

## Lint and test

```bash
npm run phpcs             # PHPCS with WordPress Coding Standards
npm run phpcs:fix         # auto-fix PHPCS violations
npm run phpstan           # PHPStan level 6 static analysis
npm run phpunit           # PHPUnit test suite
npm run check             # lint + test (CI gate)
```

Copy `phpcs.xml.dist` → `phpcs.xml` and `phpstan.neon.dist` → `phpstan.neon` for local overrides (both are git-ignored).
