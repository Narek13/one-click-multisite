<?php

declare(strict_types=1);

namespace OneClickMultisite;

use OneClickMultisite\Admin\Assets;
use OneClickMultisite\Admin\ConversionController;
use OneClickMultisite\Admin\Notice;
use OneClickMultisite\Admin\RevertController;
use OneClickMultisite\Admin\RevertPage;
use OneClickMultisite\Admin\ToolsPage;
use OneClickMultisite\Conversion\Environment;
use OneClickMultisite\Conversion\MultisiteConverter;
use OneClickMultisite\Conversion\PrerequisiteChecker;
use OneClickMultisite\Revert\RevertChecker;
use OneClickMultisite\Revert\SingleSiteReverter;

/**
 * Plugin container + bootstrap.
 *
 * Deliberately a tiny service locator rather than a heavy DI container — a WP
 * plugin should boot fast and stay simple. Services are lazily instantiated
 * and shared.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Shared service instances keyed by class name.
	 *
	 * @var array<string, object>
	 */
	private array $services = array();

	/**
	 * Whether the plugin has already booted.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Returns the singleton plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		return self::$instance ??= new self();
	}

	/**
	 * Private constructor — use instance() instead.
	 */
	private function __construct() {
	}

	/**
	 * Boots the plugin: wires the Tools page, its assets and the conversion handler,
	 * and on a network the Revert to Single Site page and its handler.
	 *
	 * Everything is admin-only; nothing runs on the front end.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		if ( ! is_admin() ) {
			return;
		}

		$this->toolsPage()->register();
		$this->assets()->register();
		$this->conversionController()->register();

		if ( is_multisite() ) {
			$this->revertPage()->register();
			$this->revertController()->register();
		}
	}

	/**
	 * Returns the shared prerequisite checker.
	 *
	 * @return PrerequisiteChecker
	 */
	public function prerequisiteChecker(): PrerequisiteChecker {
		return $this->services[ PrerequisiteChecker::class ] ??= new PrerequisiteChecker( $this->environment() );
	}

	/**
	 * Returns the shared revert checker.
	 *
	 * @return RevertChecker
	 */
	public function revertChecker(): RevertChecker {
		return $this->services[ RevertChecker::class ] ??= new RevertChecker( $this->environment() );
	}

	/**
	 * Returns the shared network reverter.
	 *
	 * @return SingleSiteReverter
	 */
	public function reverter(): SingleSiteReverter {
		return $this->services[ SingleSiteReverter::class ] ??= new SingleSiteReverter( $this->revertChecker() );
	}

	/**
	 * Returns the shared installation facts.
	 *
	 * @return Environment
	 */
	private function environment(): Environment {
		return $this->services[ Environment::class ] ??= new Environment();
	}

	/**
	 * Returns the shared multisite converter.
	 *
	 * @return MultisiteConverter
	 */
	public function converter(): MultisiteConverter {
		return $this->services[ MultisiteConverter::class ] ??= new MultisiteConverter(
			$this->prerequisiteChecker()
		);
	}

	/**
	 * Returns the shared admin notice store.
	 *
	 * @return Notice
	 */
	private function notice(): Notice {
		return $this->services[ Notice::class ] ??= new Notice();
	}

	/**
	 * Returns the shared Tools page.
	 *
	 * @return ToolsPage
	 */
	private function toolsPage(): ToolsPage {
		return $this->services[ ToolsPage::class ] ??= new ToolsPage(
			$this->prerequisiteChecker(),
			$this->notice(),
		);
	}

	/**
	 * Returns the shared Revert to Single Site page.
	 *
	 * @return RevertPage
	 */
	private function revertPage(): RevertPage {
		return $this->services[ RevertPage::class ] ??= new RevertPage(
			$this->revertChecker(),
			$this->notice(),
		);
	}

	/**
	 * Returns the shared revert controller.
	 *
	 * @return RevertController
	 */
	private function revertController(): RevertController {
		return $this->services[ RevertController::class ] ??= new RevertController(
			$this->reverter(),
			$this->notice(),
		);
	}

	/**
	 * Returns the shared admin assets component.
	 *
	 * @return Assets
	 */
	private function assets(): Assets {
		return $this->services[ Assets::class ] ??= new Assets();
	}

	/**
	 * Returns the shared conversion controller.
	 *
	 * @return ConversionController
	 */
	private function conversionController(): ConversionController {
		return $this->services[ ConversionController::class ] ??= new ConversionController(
			$this->converter(),
			$this->notice(),
		);
	}
}
