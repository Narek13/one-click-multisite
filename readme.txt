=== One Click Multisite ===
Contributors: nareknovembit
Tags: multisite, network, network setup, convert, wp-config
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn a single WordPress site into a multisite network with one click. No hand-editing of wp-config.php or .htaccess.

== Description ==

Enabling multisite by hand means following WordPress's Network Setup steps: add a constant to wp-config.php, run the network installer, then copy two blocks of generated code into wp-config.php and .htaccess and log in again. One Click Multisite does all of that for you from **Tools → Convert to Multisite**.

**What it does**

* Checks first that the conversion can work. The site must not already be a network, wp-config.php must be writable, WordPress must be able to write files directly, and the site address must be one WordPress accepts. It also checks whether sub-directories or sub-domains are possible, and whether the rewrite rules can be written.
* Creates the network database tables with WordPress core's own installer, the same one the Network Setup screen uses.
* Adds the multisite constants to wp-config.php, above the "stop editing" line. Constants that are already defined are left alone, so running it twice never adds duplicates.
* Replaces the WordPress block in .htaccess with the multisite rewrite rules core would have asked you to paste.
* Takes you to the new Network Admin.

You can choose **sub-directories** (example.com/site1) or **sub-domains** (site1.example.com). Options that WordPress does not allow on your install are disabled, with the reason. For example, WordPress disables sub-directories once a site has posts older than a month, and sub-domains are not possible on localhost or an IP address.

**What it does not do**

It does not undo a conversion. Read the FAQ before converting a live site.

== Installation ==

1. Install the plugin from Plugins → Add New, or upload the zip, and activate it.
2. **Back up your files and database.**
3. Go to **Tools → Convert to Multisite**.
4. Check that no prerequisite is marked as failed, pick a network type and click **Convert to Multisite**.
5. Log in again if WordPress asks you to. You land in the Network Admin.

== Frequently Asked Questions ==

= Is the conversion reversible? =

Not with one click. The conversion changes wp-config.php and .htaccess and creates network database tables. To go back to a single site, restore your backup. If you have no backup, remove the block marked "Added by One Click Multisite" from wp-config.php, restore the standard WordPress rules in .htaccess (Settings → Permalinks → Save), and drop the network tables (`wp_blogs`, `wp_site`, `wp_sitemeta` and the others).

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

Your network keeps working. The plugin stores no settings. It only removes the short-lived result notice it keeps for the Network Admin redirect.

== Privacy ==

One Click Multisite does not collect, store or send any personal data, and it contains no tracking.

When you choose sub-domains, WordPress core's network installer (`populate_network()`) makes one HTTP request to a random sub-domain of your own site, to check that wildcard DNS works. That request goes only to your own domain.

== Screenshots ==

1. Tools → Convert to Multisite: the prerequisite checks and the network type choice.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
