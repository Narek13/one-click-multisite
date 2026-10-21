# 2. No runtime dependencies

- **Status:** Accepted
- **Date:** 2026-10-21

## Context
The first version shipped `psr/container` in `vendor/` for a small container and module layer. WordPress loads every plugin's Composer packages into one PHP process, so a plugin that loads `psr/container` 1.x first makes this plugin's `get( string $id )` signature fatal. Prefixing (Strauss, PHP-Scoper) would fix that at the cost of a build step, for a dependency that only supplied an interface.

## Decision
Ship no Composer runtime packages. Composer is used for dev tooling only (PHPCS, PHPStan, PHPUnit, Brain Monkey). Services are wired by one `final class Plugin` service locator, the same shape as A11yFleet and Order Archive. The main plugin file falls back to a small PSR-4 autoloader when `vendor/` is absent, and the release zip leaves `vendor/` out.

## Consequences
- No class-version conflicts with other plugins, and the zip stays tiny.
- Dropping the plugin folder into `wp-content/plugins` works without `composer install`.
- Adding a runtime library later means adding prefixing at the same time.
