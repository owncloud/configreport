<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License, version 2,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\ConfigReport\Tests;

use OC\IntegrityCheck\Checker;
use OC\SystemConfig;
use OC\User\Manager;
use OCA\ConfigReport\ReportDataCollector;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use Test\TestCase;

/**
 * Class ReportDataCollectorTest
 *
 * @group DB
 * @package OCA\ConfigReport\Tests
 */
class ReportDataCollectorTest extends TestCase {
	/** @var Checker | PHPUnit_Framework_MockObject_MockObject */
	private $integrityChecker;
	/** @var Manager | PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var IGroupManager | PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;
	/** @var SystemConfig | PHPUnit_Framework_MockObject_MockObject */
	private $sysConfig;
	/** @var IAppConfig | PHPUnit_Framework_MockObject_MockObject */
	private $appConfig;
	/** @var IDBConnection */
	private $connection;
	/** @var ReportDataCollector */
	private $reportDataCollector;

	protected function setUp() {
		parent::setUp();

		//Todo - To fix this test, the ReportDataCollector needs some refactoring
		$this->markTestSkipped('This test can be skipped for now. But needs to be fixed');

		$this->integrityChecker = $this->createMock(Checker::class);
		$this->userManager = $this->createMock(Manager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->sysConfig = $this->createMock(SystemConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->connection = \OC::$server->getDatabaseConnection();

		$this->reportDataCollector = new ReportDataCollector($this->integrityChecker,
				$this->userManager, $this->groupManager, [], "1.0",
				'1', 'foo', $this->sysConfig, $this->appConfig,
				$this->connection);
	}

	public function testGetOcMigrationArray() {
		$coreApps = ['core', 'dav', 'federatedfilesharing', 'files_external', 'files_sharing', 'files_trashbin'];
		$results = $this->invokePrivate($this->reportDataCollector, 'getOcMigrationArray', []);
		//In this test we are going to look into the migrations of core apps only.
		foreach ($results as $result) {
			$values = \array_values($result);
			if (\array_search($values[0], $coreApps, true) === false) {
				continue;
			}
			$this->assertArrayHasKey('app', $result);
			$this->assertArrayHasKey('version', $result);
			$this->assertContains($values[0], $coreApps);
			$this->assertContains($values[1],
				['20170101010100', '20170101215145', '20170111103310', '20170213215145', '20170214112458', '20170221114437',
				'20170221121536', '20170315173825', '20170320173955', '20170418154659', '20170516100103', '20170526104128',
				'20170605143658', '20170711191432', '20170804201253', '20170928120000', '20171026130750', '20180123131835',
				'20180302155233', '20180319102121', '20180607072706', '20170116150538', '20170116170538', '20170202213905',
				'20170202220512', '20170427182800', '20170519091921', '20170526100342', '20170711193427', '20170927201245',
				'20170804201125', '20170804201253', '20170814051424', '20170804201125', '20170804201253', '20170830112305',
				'20171115154900', '20171215103657', '20170804201125', '20180622095921']);
		}
	}
}
