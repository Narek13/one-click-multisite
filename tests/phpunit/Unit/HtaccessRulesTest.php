<?php

declare(strict_types=1);

namespace OneClickMultisite\Tests\Unit;

use OneClickMultisite\Conversion\HtaccessRules;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the multisite rewrite rule builder.
 */
final class HtaccessRulesTest extends TestCase {

	/**
	 * GIVEN a sub-directory network at the domain root
	 * WHEN the rules are built
	 * THEN they match the optional site prefix exactly as core's Network Setup prints them.
	 */
	public function testSubdirectoryRulesMatchCore(): void {
		$lines = HtaccessRules::lines( false, '/' );

		self::assertContains( 'RewriteBase /', $lines );
		self::assertContains( 'RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]', $lines );
		self::assertContains( 'RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]', $lines );
		self::assertContains( 'RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]', $lines );
	}

	/**
	 * GIVEN a sub-domain network
	 * WHEN the rules are built
	 * THEN no site prefix is matched.
	 */
	public function testSubdomainRulesHaveNoSitePrefix(): void {
		$lines = HtaccessRules::lines( true, '/' );

		self::assertContains( 'RewriteRule ^wp-admin$ wp-admin/ [R=301,L]', $lines );
		self::assertContains( 'RewriteRule ^(wp-(content|admin|includes).*) $1 [L]', $lines );
		self::assertStringNotContainsString( '[_0-9a-zA-Z-]', implode( "\n", $lines ) );
	}

	/**
	 * GIVEN a site that lives in a sub-folder with core in its own directory
	 * WHEN the rules are built
	 * THEN RewriteBase uses the site path and core paths are prefixed with the core directory.
	 */
	public function testUsesSitePathAndCoreDirectory(): void {
		$lines = HtaccessRules::lines( false, '/blog/', 'wp/' );

		self::assertContains( 'RewriteBase /blog/', $lines );
		self::assertContains( 'RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) wp/$2 [L]', $lines );
	}

	/**
	 * GIVEN any network type
	 * WHEN the rules are built
	 * THEN the Authorization header is passed through for REST API / application passwords.
	 */
	public function testPassesAuthorizationHeader(): void {
		self::assertSame( 'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]', HtaccessRules::lines( true, '/' )[1] );
	}
}
