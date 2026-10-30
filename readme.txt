=== One Click Multisite ===
Contributors: nareknovembit
Tags: multisite, network, network setup, convert, wp-config
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn a single WordPress site into a multisite network with one click, and back again. No hand-editing of wp-config.php or .htaccess.

== Description ==

Enabling multisite by hand means following WordPress's Network Setup steps: add a constant to wp-config.php, run the network installer, then copy two blocks of generated code into wp-config.php and .htaccess and log in again. One Click Multisite does all of that for you from **Tools → Convert to Multisite**.

**What it does**

* Checks first that the conversion can work. The site must not already be a network, wp-config.php must be writable, WordPress must be able to write files directly, and the site address must be one WordPress accepts. It also checks whether sub-directories or sub-domains are possible, and whether the rewrite rules can be written.
* Creates the network database tables with WordPress core's own installer, the same one the Network Setup screen uses.
* Adds the multisite constants to wp-config.php, above the "stop editing" line. Constants that are already defined are left alone, so running it twice never adds duplicates.
* Replaces the WordPress block in .htaccess with the multisite rewrite rules core would have asked you to paste.
* Keeps your permalink structure. WordPress's own installer resets it (to `/blog/%year%/%monthnum%/%day%/%postname%/` on sub-directory networks), which would change every existing URL; the plugin puts yours back.
* Takes you to the new Network Admin.

You can choose **sub-directories** (example.com/site1) or **sub-domains** (site1.example.com). Options that WordPress does not allow on your install are disabled, with the reason. For example, WordPress disables sub-directories once a site has posts older than a month, and sub-domains are not possible on localhost or an IP address.

**Revert to a single site**

Changed your mind? **Network Admin → Settings → Revert to Single Site** turns the network back into a single site, as long as it still holds only the main site. It removes the multisite constants from wp-config.php, restores the standard .htaccess rules, keeps network-activated plugins active, and deletes the network tables. Posts, pages, media, users and settings of the main site are not touched.

The revert refuses to run when it could lose something: when other sites exist (their tables and uploads would be orphaned), or when your account has no Administrator role on the main site (super admin rights disappear with the network, so you would be locked out).

== Installation ==

1. Install the plugin from Plugins → Add New, or upload the zip, and activate it.
2. **Back up your files and database.**
3. Go to **Tools → Convert to Multisite**.
4. Check that no prerequisite is marked as failed, pick a network type and click **Convert to Multisite**.
5. Log in again if WordPress asks you to. You land in the Network Admin.

== Frequently Asked Questions ==

= Is the conversion reversible? =

Yes, while the network holds only the main site: go to **Network Admin → Settings → Revert to Single Site**. If you have created other sites, delete them first (export their content if you need it); a single site has no place for their tables and uploads. A full backup restore always works too.

= Does it work for networks I set up by hand? =

The revert does, as long as the multisite constants are plain `define()` lines in wp-config.php. It also removes the `/blog` prefix WordPress adds to the main site's permalinks on sub-directory networks.

= Will my URLs change? =

No. WordPress's network installer resets the main site's permalink structure; the plugin restores yours right after, and a revert keeps it as well.

= Does it work on nginx? =

Yes, but nginx does not read .htaccess. The prerequisites screen says so before you convert. Afterwards, add the multisite rewrite rules to your nginx configuration; see the [nginx guide in the WordPress developer documentation](https://developer.wordpress.org/advanced-administration/server/web-server/nginx/).

= Why is "Sub-directories" disabled? =

WordPress disables sub-directory networks once the main site has published posts older than a month, because their permalinks could collide with the address of a new site. The same rule applies on the manual Network Setup screen.

= Why is "Sub-domains" disabled? =

Sub-domain networks need a real domain name. They are not possible on `localhost`, on an IP address, or when WordPress lives in a sub-folder of the domain.

= My host asks for FTP details when I install plugins. Will this work? =

No. The plugin writes wp-config.php directly and does not ask for FTP or SSH credentials. If WordPress cannot write files directly, the prerequisites screen tells you so and the conversion is not offered. Use WordPress's manual Network Setup steps instead.

= Should I deactivate my other plugins first? =

WordPress recommends it before enabling a network, and it is a good idea on a production site. After the conversion, each plugin is still active on the main site; network-activate the ones you want on every site.

= What happens when I delete the plugin? =

Your network keeps working. The plugin stores no settings. It only removes the short-lived result notices it keeps across redirects. Keep the plugin installed if you may want to revert later.

== Privacy ==

One Click Multisite does not collect, store or send any personal data, and it contains no tracking.

When you choose sub-domains, WordPress core's network installer (`populate_network()`) makes one HTTP request to a random sub-domain of your own site, to check that wildcard DNS works. That request goes only to your own domain.

== Screenshots ==

1. Tools → Convert to Multisite: the prerequisite checks and the network type choice.
2. Network Admin → Settings → Revert to Single Site.

== Changelog ==

= 1.1.0 =
* New: Revert to Single Site (Network Admin → Settings), for networks that only hold the main site.
* Fix: the main site's permalink structure is kept when converting. WordPress's installer used to reset it, changing existing URLs.
* Fix: commented-out `define()` lines in wp-config.php are no longer mistaken for real ones.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Adds Revert to Single Site and keeps your permalinks when converting.

= 1.0.0 =
Initial release.
