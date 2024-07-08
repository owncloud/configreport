<?php
/**
 * @copyright Copyright (c) 2016, ownCloud GmbH.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

namespace OCA\ConfigReport;
use OC\Helper\UserTypeHelper;
use OC\IntegrityCheck\Checker;
use OC\SystemConfig;
use OC\User\Manager;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use Symfony\Component\EventDispatcher\GenericEvent;
use OCP\Files\External\Service\IGlobalStoragesService;
use OCP\Files\External\IStorageConfig;

/**
 * @package OCA\ConfigReport\Report
 */
class ReportDataCollector {
	/**
	 * @var Checker
	 */
	private $integrityChecker;

	/**
	 * @var Manager
	 */
	private $userManager;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var UserTypeHelper
	 */
	private $userTypeHelper;

	/**
	 * @var array
	 */
	private $version;

	/**
	 * @var string
	 */
	private $versionString;

	/**
	 * @var string
	 */
	private $editionString;

	/**
	 * @var string
	 */
	private $displayName;

	/**
	 * @var SystemConfig
	 */
	private $systemConfig;

	/**
	 * @var object
	 */
	private $appConfigData;

	/**
	 * @var array
	 */
	private $apps;

	/**
	 * @var IAppConfig
	 */
	private $appConfig;

	/**
	 * @var IDBConnection
	 */
	private $connection;

	/**
	 * @var IGlobalStoragesService
	 */
	private $globalStoragesService;
	
	/**
	 * @var array
	 */
	private $obscuredkeys;

	private const ETC_OS_RELEASE = '/etc/os-release';
	private const ETC_LSB_RELEASE = '/etc/lsb-release';

	public function __construct(
		Checker $integrityChecker,
		Manager $userManager,
		UserTypeHelper $userTypeHelper,
		IGroupManager $groupManager,
		array $version,
		string $versionString,
		string $editionString,
		string $displayName,
		SystemConfig $systemConfig,
		IAppConfig $appConfig,
		IDBConnection $connection,
		IGlobalStoragesService $globalStoragesService
	) {
		$this->integrityChecker = $integrityChecker;
		$this->userManager = $userManager;
		$this->userTypeHelper = $userTypeHelper;
		$this->groupManager = $groupManager;

		$this->version = $version;
		$this->versionString = $versionString;
		$this->editionString = $editionString;
		$this->displayName = $displayName;

		$this->systemConfig = $systemConfig;
		$this->apps = \OC_App::listAllApps();
		$this->appConfig = $appConfig;
		$this->connection = $connection;
		$this->globalStoragesService = $globalStoragesService;

		$event = new GenericEvent();
		$this->appConfigData = \OC::$server->getEventDispatcher()->dispatch($event, 'OCA\ConfigReport::loadData');
		$this->obscuredkeys = [
			'server_user',
			'wopi.token.key',
			'wopi.proxy.key',
		];
	}

	public function getReportJson(): string {
		return \json_encode($this->getReport(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
	}

	/**
	 * @param array $report
	 */
	public function addEventListenerReportData(&$report): void {
		foreach ($this->appConfigData->getArguments() as $index) {
			foreach ($index as $innerKey => $innerVal) {
				if (!\array_key_exists($innerKey, $report)) {
					$report[$innerKey] = $innerVal;
				}
			}
		}
	}

	public function getReport(): array {
		$report = [
			'basic' => $this->getBasicDetailArray(IConfig::SENSITIVE_VALUE),
			'stats' => $this->getStatsDetailArray(),
			'config' => $this->getSystemConfigDetailArray(),
			'integritychecker' => $this->getIntegrityCheckerDetailArray(),
			'core' => $this->getCoreConfigArray(),
			'apps' => $this->getAppsDetailArray(),
			'mounts' => $this->getMountsArray(),
			'tables' => $this->getOCTablesArray(),
			'migrations' => $this->getOcMigrationArray(),
			'phpinfo' => $this->getPhpInfoDetailArray()
		];

		//Now apps can add their values to report array
		$this->addEventListenerReportData($report);

		return $report;
	}

	public function getTelemetryReport(string $licenseKey): array {
		return [
			'basic' => $this->getBasicDetailArray($licenseKey),
			'stats' => $this->getStatsDetailArray(),
			'config' => $this->getSystemConfigDetailArray(),
			'mounts' => $this->getMountsSimplified(),
			'phpinfo' => $this->getPhpInfoDetailArray()
		];
	}

	private function getMountsSimplified(): array {
		/** @var IStorageConfig[] $mounts */
		$mounts = $this->globalStoragesService->getStorageForAllUsers();

		$mountsArray = [];

		foreach ($mounts as $mount) {
			if ($mount->getType() === IStorageConfig::MOUNT_TYPE_PERSONAl) {
				continue;
			}
			$mountsArray[] = [
				'id' => $mount->getId(),
				'storage' => $mount->getBackend()->getText(),
				'type' => $mount->getType() === IStorageConfig::MOUNT_TYPE_ADMIN ? 'Admin' : 'Personal'
			];
		}
		return $mountsArray;
	}

	private function getMountsArray(): array {
		/** @var IStorageConfig[] $mounts */
		$mounts = $this->globalStoragesService->getStorageForAllUsers();

		$mountsArray = [];

		foreach ($mounts as $mount) {
			if ($mount->getType() === IStorageConfig::MOUNT_TYPE_PERSONAl) {
				continue;
			}
			$applicableUsers = \implode(', ', $mount->getApplicableUsers());
			$applicableGroups = \implode(', ', $mount->getApplicableGroups());

			if ($applicableUsers === '' && $applicableGroups === '') {
				$applicableUsers = 'All';
			}

			$configuration = $mount->getBackendOptions();
			$this->hideMountPasswords($mount, $configuration);
			$mountsArray[] = [
				'id' => $mount->getId(),
				'mount_point' => $mount->getMountPoint(),
				'storage' => $mount->getBackend()->getText(),
				'authentication_type' => $mount->getAuthMechanism()->getText(),
				'configuration' => $configuration,
				'options' => $mount->getMountOptions(),
				'applicable_users' => $applicableUsers,
				'applicable_groups' => $applicableGroups,
				'type' => $mount->getType() === IStorageConfig::MOUNT_TYPE_ADMIN ? 'Admin' : 'Personal'
			];
		}
		return $mountsArray;
	}

	private function hideMountPasswords(IStorageConfig $mount, array &$configArray): void {
		$backend = $mount->getBackend();
		$auth = $mount->getAuthMechanism();

		$backendParameters = $backend->getParameters();
		$authParameters = $auth->getParameters();

		foreach ($configArray as $key => $value) {
			if (
				(
					isset($backendParameters[$key]) &&
					$backendParameters[$key]->getType() === \OCP\Files\External\DefinitionParameter::VALUE_PASSWORD
				) || (
					isset($authParameters[$key]) &&
					$authParameters[$key]->getType() === \OCP\Files\External\DefinitionParameter::VALUE_PASSWORD
				)
			) {
				$configArray[$key] = IConfig::SENSITIVE_VALUE;
			}
		}
	}

	private function getIntegrityCheckerDetailArray(): array {
		return [
			'passing' => $this->integrityChecker->hasPassedCheck(),
			'enabled' => $this->integrityChecker->isCodeCheckEnforced(),
			'result' => $this->integrityChecker->getResults(),
		];
	}

	private function getBasicDetailArray(string $licenseKey): array {
		return [
			'license key' => $licenseKey,
			'date' => \date('r'),
			'ownCloud version' => \implode('.', $this->version),
			'ownCloud version string' => $this->versionString,
			'ownCloud edition' => $this->editionString,
			'server OS' => PHP_OS,
			'server OS version' => \php_uname(),
			'server SAPI' => PHP_SAPI,
			'webserver version' => $_SERVER['SERVER_SOFTWARE'] ?? '???',
			'hostname' => $_SERVER['HTTP_HOST'] ?? '???',
			'logged-in user' => $this->displayName,
			'distro' => $this->getLinuxDistribution(),
			'docker' => $this->isDocker() ? 'yes' : 'no',
		];
	}

	private function getStatsDetailArray(): array {
		$users = [];
		$this->userManager->callForAllUsers(function (IUser $user) use (&$users) {
			if (!isset($users[$user->getBackendClassName()])) {
				$users[$user->getBackendClassName()] = ['total_count' => 0, 'guest_count' => 0, 'seen' => 0, 'logged in (30 days)' => 0];
			}
			$users[$user->getBackendClassName()]['total_count']++;

			if ($this->userTypeHelper->isGuestUser($user->getUID()) === true) {
				$users[$user->getBackendClassName()]['guest_count']++;
			}

			if ($user->getLastLogin() > 0) {
				$users[$user->getBackendClassName()]['seen']++;
				if ($user->getLastLogin() > \strtotime('-30 days')) {
					$users[$user->getBackendClassName()]['logged in (30 days)']++;
				}
			}
		});

		$groupCount = [];
		$groups = $this->groupManager->search('');
		foreach ($groups as $group) {
			$backendName = \get_class($group->getBackend());
			if (!isset($groupCount[$backendName])) {
				$groupCount[$backendName] = 0;
			}
			$groupCount[$backendName]++;
		}

		return [
			'users' => $users,
			'groups' => $groupCount,
		];
	}

	private function getSystemConfigDetailArray(): array {
		$keys = $this->systemConfig->getKeys();
		$result = [];
		foreach ($keys as $key) {
			$result[$key] = $this->systemConfig->getFilteredValue($key);
		}

		return $this->sanitizeValues($result);
	}

	/**
	 * Sanitize values from the given hash array by removing
	 * sensitive values
	 */
	private function sanitizeValues(array $values): array {
		foreach ($values as $key => $value) {
			if (\stripos($key, 'password') !== false) {
				$values[$key] = IConfig::SENSITIVE_VALUE;
			}
			if (\in_array($key, $this->obscuredkeys)) {
				$values[$key] = IConfig::SENSITIVE_VALUE;
			}
		}
		return $values;
	}

	private function getCoreConfigArray(): array {
		// Get core config data
		$appConfig = $this->appConfig->getValues('core', false);
		// this one is reported separately already
		unset($appConfig['oc.integritycheck.checker']);
		return $this->sanitizeValues($appConfig);
	}

	private function getAppsDetailArray(): array {
		// Get app data
		foreach ($this->apps as &$app) {
			if ($app['active']) {
				$appConfig = $this->appConfig->getValues($app['id'], false);
				$app['appconfig'] = $this->sanitizeValues($appConfig);
			}
		}
		return $this->apps;
	}

	private function getOcMigrationArray(): array {
		//Get data from oc_migrations table
		$queryBuilder = $this->connection->getQueryBuilder();
		/* @phpstan-ignore-next-line @phan-suppress-next-line PhanDeprecatedFunction */
		return $queryBuilder
			->select('app', 'version')
			->from('migrations')
			->execute()
			->fetchAll();
	}

	private function getOCTablesArray(): array {
		$ocTables = [];
		//Get tables structure/description/schema from owncloud db
		/* @phpstan-ignore-next-line @phan-suppress-next-line PhanUndeclaredMethod */
		$schemaManager = $this->connection->getSchemaManager();
		$tableNames = $schemaManager->listTableNames();
		foreach ($tableNames as $tableName) {
			$ocTables['tableNames'][$tableName] = [];
			$tableDetail = $schemaManager->listTableDetails($tableName);
			$columns = $tableDetail->getColumns();
			foreach ($columns as $column) {
				$ocTables['tableNames'][$tableName]['fields'][] = [
					$column->getName() =>
						[
							'type' => $column->getType()->getName(),
							'null' => !$column->getNotnull(),
							'default' => $column->getDefault(),
							'autoIncrement' => $column->getAutoincrement(),
						]
				];
			}
			$indexes = $tableDetail->getIndexes();
			foreach ($indexes as $index) {
				$ocTables['tableNames'][$tableName]['index'][] = [
					'indexName' => $index->getName(),
					'columns' => $index->getColumns(),
					'unique' => $index->isUnique()
				];
			}
			if ($tableDetail->hasPrimaryKey()) {
				$primaryColumns = $tableDetail->getPrimaryKeyColumns();
			} else {
				$primaryColumns = [];
			}
			$ocTables['tableNames'][$tableName][] = [
				'primaryColumns' => $primaryColumns
			];
		}

		return $ocTables;
	}

	private function getLinuxDistribution(): string {
		if (file_exists(self::ETC_OS_RELEASE)) {
			$content = file_get_contents(self::ETC_OS_RELEASE);
			return $content === false ? "" : $content;
		}
		if (file_exists(self::ETC_LSB_RELEASE)) {
			$content = file_get_contents(self::ETC_LSB_RELEASE);
			return $content === false ? "" : $content;
		}
		return "";
	}

	private function getPhpInfoDetailArray(): array {
		return (new PHPInfo())->parsePhpInfo();
	}

	private function isDocker(): bool {
		return file_exists('/.dockerenv');
	}
}
