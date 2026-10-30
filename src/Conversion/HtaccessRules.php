<?php

declare(strict_types=1);

namespace OneClickMultisite\Conversion;

/**
 * Builds the Apache rewrite rules for a multisite network, and the
 * single-site rules to restore when a network is reverted.
 *
 * The network rules mirror what WordPress core prints in network_step2()
 * (wp-admin/includes/network.php), so the result is exactly what a manual
 * Network Setup would ask the admin to paste.
 */
final class HtaccessRules {

	/**
	 * Returns the rule lines for the `# BEGIN WordPress` block.
	 *
	 * @param bool   $subdomainInstall True for a sub-domain network.
	 * @param string $base             Site path from the home URL, with leading and trailing slash (e.g. "/" or "/blog/").
	 * @param string $rewriteBase      Path of WordPress core relative to the home path when core lives in its own directory (e.g. "wp/"), else "".
	 * @return list<string>
	 */
	public static function lines( bool $subdomainInstall, string $base, string $rewriteBase = '' ): array {
		$subdirMatch         = $subdomainInstall ? '' : '([_0-9a-zA-Z-]+/)?';
		$subdirReplacement01 = $subdomainInstall ? '' : '$1';
		$subdirReplacement12 = $subdomainInstall ? '$1' : '$2';

		return array(
			'RewriteEngine On',
			'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]',
			'RewriteBase ' . $base,
			'RewriteRule ^index\.php$ - [L]',
			'',
			'# add a trailing slash to /wp-admin',
			'RewriteRule ^' . $subdirMatch . 'wp-admin$ ' . $subdirReplacement01 . 'wp-admin/ [R=301,L]',
			'',
			'RewriteCond %{REQUEST_FILENAME} -f [OR]',
			'RewriteCond %{REQUEST_FILENAME} -d',
			'RewriteRule ^ - [L]',
			'RewriteRule ^' . $subdirMatch . '(wp-(content|admin|includes).*) ' . $rewriteBase . $subdirReplacement12 . ' [L]',
			'RewriteRule ^' . $subdirMatch . '(.*\.php)$ ' . $rewriteBase . $subdirReplacement12 . ' [L]',
			'RewriteRule . index.php [L]',
		);
	}

	/**
	 * Returns the single-site rule lines, exactly as WP_Rewrite::mod_rewrite_rules()
	 * writes them. Core never writes .htaccess while multisite is on, so after
	 * reverting a network these rules have to be put back explicitly.
	 *
	 * @param string $base             Site path from the home URL, with leading and trailing slash.
	 * @param bool   $prettyPermalinks Whether the site uses a permalink structure; plain permalinks need no rules.
	 * @return list<string> Empty for plain permalinks.
	 */
	public static function singleSiteLines( string $base, bool $prettyPermalinks = true ): array {
		if ( ! $prettyPermalinks ) {
			return array();
		}

		return array(
			'<IfModule mod_rewrite.c>',
			'RewriteEngine On',
			'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]',
			'RewriteBase ' . $base,
			'RewriteRule ^index\.php$ - [L]',
			'RewriteCond %{REQUEST_FILENAME} !-f',
			'RewriteCond %{REQUEST_FILENAME} !-d',
			'RewriteRule . ' . $base . 'index.php [L]',
			'</IfModule>',
		);
	}
}
