<?php
declare(strict_types=1);

namespace Vierwd\ComposerGithooks;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Symfony\Component\Process\Process;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface {

	/** @var \Composer\Package\PackageInterface */
	protected $package;

	/** @var \Composer\IO\IOInterface */
	protected $io;

	public function activate(Composer $composer, IOInterface $io): void {
		$this->package = $composer->getPackage();
		$this->io = $io;
	}

	/**
	 * Make sure the installer is executed after the autoloader is created
	 */
	public static function getSubscribedEvents(): array {
		return [
			ScriptEvents::POST_AUTOLOAD_DUMP => 'installHooks',
		 ];
	}

	/**
	 * Run the installer
	 *
	 * @param \Composer\Script\Event $event
	 */
	public function installHooks(Event $event): void {
		// only install githooks on live-server. Dev-Version has correct githooks set up as git template
		if (!empty($_SERVER['VIERWD_CONFIG'])) {
			return;
		}

		// only setup githooks, if this plugin is part of the package.
		// It will not be part of the package if it is currently being uninstalled
		if (!isset($this->package->getRequires()['vierwd/composer-githooks']) && !isset($this->package->getDevRequires()['vierwd/composer-githooks'])) {
			return;
		}

		if (!$this->checkGitVersion()) {
			return;
		}

		if ($this->isHookPathSet()) {
			return;
		}

		$this->setHookPath();
	}

	protected function checkGitVersion(): bool {
		// Check git-version
		$process = new Process('git --version');
		$process->run();
		if (!$process->isSuccessful()) {
			$this->io->warning('vierwd/composer-githooks: Could not execute git');
			return false;
		}

		$version = str_replace('git version ', '', trim($process->getOutput()));
		if (version_compare($version, '2.9.0', '<')) {
			$this->io->warning('vierwd/composer-githooks: git version is too old: ' . $version . '. Please link the githooks in .git/hooks');
			return false;
		}

		return true;
	}

	protected function isHookPathSet(): bool {
		$process = new Process('git config core.hooksPath');
		$process->run();
		if (trim($process->getOutput()) === 'githooks/') {
			// nothing to do. githooks directory is already set
			$this->io->debug('vierwd/composer-githooks: git-hooks configuration variable already set.');
			return true;
		}

		return false;
	}

	protected function setHookPath(): void {
		$this->io->notice('vierwd/composer-githooks: Enabling hooks');
		$process = new Process('git config core.hooksPath githooks/');
		$process->run();
		if ($process->isSuccessful()) {
			$this->io->notice('vierwd/composer-githooks: Success.');
		} else {
			$this->io->warning('vierwd/composer-githooks: Failed.');
		}
	}
}
