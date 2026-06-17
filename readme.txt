=== One Click Multisite ===
Contributors: narekzakarian
Tags: multisite, network, convert, wp-config, tools
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Convert a single-site WordPress installation to a multisite network with one click from Tools > Convert to Multisite.

== Description ==

One Click Multisite adds a **Convert to Multisite** page under the WordPress Tools menu. With a single button click it:

* Runs a prerequisites check (writable wp-config.php, writable .htaccess, not already multisite)
* Creates the multisite network database tables via WordPress core functions
* Writes all required constants to wp-config.php
* Writes multisite rewrite rules to .htaccess
* Redirects you to the new Network Admin dashboard

You can choose between **sub-directory** (example.com/site1) and **sub-domain** (site1.example.com) network types. Sub-domain installs require wildcard DNS configured at your host.

**No external dependencies.** The plugin uses only WordPress core APIs and standard PHP.

== Installation ==

1. Upload the `one-click-multisite` folder to `/wp-content/plugins/`.
2. Run `composer install --no-dev` inside the plugin folder (or download the release zip which includes the autoloader).
3. Activate the plugin from the Plugins screen.
4. Go to **Tools > Convert to Multisite**.
5. Confirm all prerequisites are green, choose your network type, and click **Convert to Multisite**.

== Frequently Asked Questions ==

= Is this reversible? =
The conversion modifies wp-config.php and .htaccess and creates database tables. Always back up your site and database before converting. Reverting requires manually removing the added constants from wp-config.php, restoring .htaccess, and dropping the multisite tables.

= Does it support sub-domain networks? =
Yes. Select "Sub-domains" on the tools page. Your hosting must support wildcard DNS for the domain.

= What PHP version is required? =
PHP 7.4 or newer.

== Screenshots ==

1. The Tools > Convert to Multisite page showing prerequisite checks and the conversion form.

== Changelog ==

= 1.0.0 =
* Initial release.
