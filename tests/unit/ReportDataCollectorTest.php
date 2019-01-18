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

		$this->integrityChecker = $this->createMock(Checker::class);
		$this->overwriteService('IntegrityCodeChecker', $this->integrityChecker);
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

	protected function tearDown() {
		$this->restoreService('IntegrityCodeChecker');
		parent::tearDown();
	}

	public function testGetOcMigrationArray() {
		//Todo - To fix this test, the ReportDataCollector needs some refactoring
		$this->markTestSkipped('This test can be skipped for now. But needs to be fixed');
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

	public function testGetOCTablesArray() {
		$result = $this->invokePrivate($this->reportDataCollector, 'getOCTablesArray', []);
		/**
		 * We cannot test all tables. But we can test few tables like
		 * oc_groups, oc_accounts
		 */

		$dbtype = \OC::$server->getSystemConfig()->getValue('dbtype');
		if ($dbtype === 'mysql') {
			$expectedResult = [
				'oc_accounts' => [
					'fields' => [
						['id' => [
							'type' => 'bigint',
							'null' => false,
							'default' => null,
							'autoIncrement' => true
						]],
						['email' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['user_id' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['lower_user_id' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['display_name' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['quota' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['last_login' => [
							'type' => 'integer',
							'null' => false,
							'default' => '0',
							'autoIncrement' => false
						]],
						['backend' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['home' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['state' => [
							'type' => 'smallint',
							'null' => false,
							'default' => '0',
							'autoIncrement' => false
						]]
					],
					'index' => [
						[
							'indexName' => 'PRIMARY',
							'columns' => [
								'id',
							],
							'unique' => true
						],
						[
							'indexName' => 'UNIQ_907AA303A76ED395',
							'columns' => [
								'user_id',
							],
							'unique' => true
						],
						[
							'indexName' => 'lower_user_id_index',
							'columns' => [
								'lower_user_id',
							],
							'unique' => true
						],
						[
							'indexName' => 'display_name_index',
							'columns' => [
								'display_name',
							],
							'unique' => false
						],
						[
							'indexName' => 'email_index',
							'columns' => [
								'email',
							],
							'unique' => false
						],
					],
					[
						'primaryColumns' => ['id']
					]
				],
				'oc_groups' => [
					'fields' => [
						['gid' => [
							'type' => 'string',
							'null' => false,
							'default' => '',
							'autoIncrement' => false
						]]
					],
					'index' => [
						[
							'indexName' => 'PRIMARY',
							'columns' => [
								'gid',
							],
							'unique' => true
						],
					],
					[
						'primaryColumns' => ['gid']
					]
				]
			];
		} elseif ($dbtype === 'pgsql') {
			$expectedResult = [
				'oc_accounts' => [
					'fields' => [
						['id' => [
							'type' => 'bigint',
							'null' => false,
							'default' => null,
							'autoIncrement' => true
						]],
						['email' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['user_id' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['lower_user_id' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['display_name' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['quota' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['last_login' => [
							'type' => 'integer',
							'null' => false,
							'default' => '0',
							'autoIncrement' => false
						]],
						['backend' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['home' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['state' => [
							'type' => 'smallint',
							'null' => false,
							'default' => '0',
							'autoIncrement' => false
						]]
					],
					'index' => [
						[
							'indexName' => 'lower_user_id_index',
							'columns' => [
								'lower_user_id',
							],
							'unique' => true
						],
						[
							'indexName' => 'display_name_index',
							'columns' => [
								'display_name',
							],
							'unique' => false
						],
						[
							'indexName' => 'oc_accounts_pkey',
							'columns' => [
								'id',
							],
							'unique' => true
						],
						[
							'indexName' => 'email_index',
							'columns' => [
								'email',
							],
							'unique' => false
						],
						[
							'indexName' => 'uniq_907aa303a76ed395',
							'columns' => [
								'user_id',
							],
							'unique' => true
						],
					],
					[
						'primaryColumns' => ['id']
					]
				],
				'oc_groups' => [
					'fields' => [
						['gid' => [
							'type' => 'string',
							'null' => false,
							'default' => '',
							'autoIncrement' => false
						]]
					],
					'index' => [
						[
							'indexName' => 'oc_groups_pkey',
							'columns' => [
								'gid',
							],
							'unique' => true
						],
					],
					[
						'primaryColumns' => ['gid']
					]
				]
			];
		} elseif ($dbtype === 'sqlite' || $dbtype === 'sqlite3') {
			$expectedResult = [
				'oc_accounts' => [
					'fields' => [
						['id' => [
							'type' => 'integer',
							'null' => false,
							'default' => null,
							'autoIncrement' => true
						]],
						['email' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['user_id' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['lower_user_id' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['display_name' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['quota' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['last_login' => [
							'type' => 'integer',
							'null' => false,
							'default' => '0',
							'autoIncrement' => false
						]],
						['backend' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['home' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['state' => [
							'type' => 'smallint',
							'null' => false,
							'default' => '0',
							'autoIncrement' => false
						]]
					],
					'index' => [
						[
							'indexName' => 'primary',
							'columns' => [
								'id',
							],
							'unique' => true
						],
						[
							'indexName' => 'email_index',
							'columns' => [
								'email',
							],
							'unique' => false
						],
						[
							'indexName' => 'display_name_index',
							'columns' => [
								'display_name',
							],
							'unique' => false
						],
						[
							'indexName' => 'lower_user_id_index',
							'columns' => [
								'lower_user_id',
							],
							'unique' => true
						],
						[
							'indexName' => 'UNIQ_907AA303A76ED395',
							'columns' => [
								'user_id',
							],
							'unique' => true
						],
					],
					[
						'primaryColumns' => ['id']
					]
				],
				'oc_groups' => [
					'fields' => [
						['gid' => [
							'type' => 'string',
							'null' => false,
							'default' => '',
							'autoIncrement' => false
						]]
					],
					'index' => [
						[
							'indexName' => 'primary',
							'columns' => [
								'gid',
							],
							'unique' => true
						],
					],
					[
						'primaryColumns' => ['gid']
					]
				]
			];
		} elseif ($dbtype === 'oci') {
			$expectedResult = [
				'oc_accounts' => [
					'fields' => [
						['id' => [
							'type' => 'bigint',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['email' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['user_id' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['lower_user_id' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['display_name' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['quota' => [
							'type' => 'string',
							'null' => true,
							'default' => null,
							'autoIncrement' => false
						]],
						['last_login' => [
							'type' => 'integer',
							'null' => false,
							'default' => '0',
							'autoIncrement' => false
						]],
						['backend' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['home' => [
							'type' => 'string',
							'null' => false,
							'default' => null,
							'autoIncrement' => false
						]],
						['state' => [
							'type' => 'smallint',
							'null' => false,
							'default' => '0',
							'autoIncrement' => false
						]]
					],
					'index' => [
						[
							'indexName' => 'primary',
							'columns' => [
								"\"id\"",
							],
							'unique' => true
						],
						[
							'indexName' => 'uniq_907aa303a76ed395',
							'columns' => [
								"\"user_id\"",
							],
							'unique' => true
						],
						[
							'indexName' => 'lower_user_id_index',
							'columns' => [
								"\"lower_user_id\"",
							],
							'unique' => true
						],
						[
							'indexName' => 'display_name_index',
							'columns' => [
								"\"display_name\"",
							],
							'unique' => false
						],
						[
							'indexName' => 'email_index',
							'columns' => [
								"\"email\"",
							],
							'unique' => false
						],
					],
					[
						'primaryColumns' => ["\"id\""]
					]
				],
				'oc_groups' => [
					'fields' => [
						['gid' => [
							'type' => 'string',
							'null' => false,
							'default' => '',
							'autoIncrement' => false
						]]
					],
					'index' => [
						[
							'indexName' => 'primary',
							'columns' => [
								"\"gid\"",
							],
							'unique' => true
						],
					],
					[
						'primaryColumns' => ["\"gid\""]
					]
				]
			];
		}

		if ($dbtype === 'oci') {
			$this->asserttrue(isset($result['tableNames']["\"oc_accounts\""]));
			$this->assertEquals($expectedResult['oc_accounts'], $result['tableNames']["\"oc_accounts\""], "", 0.0, 10, true);
			$this->assertEquals($expectedResult['oc_groups'], $result['tableNames']["\"oc_groups\""], '', 0.0, 10, true);
		} else {
			$this->asserttrue(isset($result['tableNames']['oc_accounts']));
			$this->assertEquals($expectedResult['oc_accounts'], $result['tableNames']['oc_accounts'], "", 0.0, 10, true);
			$this->assertEquals($expectedResult['oc_groups'], $result['tableNames']['oc_groups'], '', 0.0, 10, true);
		}
	}
}
