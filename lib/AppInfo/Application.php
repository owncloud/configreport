<?php

namespace OCA\ConfigReport\AppInfo;

use OCA\ConfigReport\Controller\ReportController;
use OCA\ConfigReport\ReportDataCollector;
use \OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;

/**
 * Class Application
 *
 * @package OCA\ConfigReport\AppInfo
 */
class Application extends App {

	/**
	 * Application constructor.
	 *
	 * @param string $appName
	 * @param array $urlParams
	 */
	public function __construct($appName, array $urlParams = []) {
		parent::__construct('configreport', $urlParams);
		$this->registerServices();
	}

	private function registerServices() {
		$container = $this->getContainer();

		$container->registerService('ReportDataCollector', function ($c) {
			return new ReportDataCollector(
				\OC::$server->getIntegrityCodeChecker(),
				\OC::$server->getUserManager(),
				\OC::$server->getGroupManager(),
				\OC_Util::getVersion(),
				\OC_Util::getVersionString(),
				\OC_Util::getEditionString(),
				\OCP\User::getDisplayName(),
				\OC::$server->getSystemConfig(),
				\OC::$server->getAppConfig(),
				\OC::$server->getDatabaseConnection()
			);
		});

		$container->registerService('ReportController', function (IAppContainer $c) {
			$server = $c->getServer();
			return new ReportController(
				$c->query('AppName'),
				$c->query('Request'),
				$server->getConfig(),
				$c->query('ReportDataCollector')
			);
		});
	}
}
