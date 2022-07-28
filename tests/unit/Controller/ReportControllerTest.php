<?php
/**
 * @author Patrick Jahns <pjahns@owncloud.com>
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

namespace OCA\ConfigReport\Controller;

use Google\Service\Analytics\Resource\Data;
use OCA\ConfigReport\Http\ReportResponse;
use OCA\ConfigReport\ReportDataCollector;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IRequest;
use Test\TestCase;

/**
 * Class ReportControllerTest
 *
 * @package OCA\ConfigReport\Controller
 */
class ReportControllerTest extends TestCase {

	/** @var IConfig */
	private $config;
	/** @var IRequest */
	private $request;
	/** @var ReportController */
	private $controller;
	/** @var ReportDataCollector */
	private $reportDataCollector;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();

		$this->reportDataCollector = $this->getMockBuilder(ReportDataCollector::class)
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new ReportController(
			'configreport',
			$this->request,
			$this->config,
			$this->reportDataCollector
		);
	}

	public function testGetReport() {
		$expectedValue = \json_encode([]);
		$this->reportDataCollector->method('getReportJson')->willReturn($expectedValue);
		$result = $this->controller->getReport();
		$this->assertInstanceOf(ReportResponse::class, $result);
	}

	public function testGetCli() {
		$expectedValue = \json_encode([]);
		$this->reportDataCollector->method('getReportJson')->willReturn($expectedValue);

		// without auth-token

		/** @var Response $result */
		$result = $this->controller->fromCli();

		$this->assertEquals(403, $result->getStatus());
		$this->assertInstanceOf(DataResponse::class, $result);

		// with auth-token
		$this->config->method('getAppValue')->willReturn('secret');
		$this->request->method('getParam')->willReturn('secret');

		/** @var Response $result */
		$result = $this->controller->fromCli();

		$this->assertEquals(200, $result->getStatus());
		$this->assertInstanceOf(ReportResponse::class, $result);
	}

	public function testGetCliWrongAuthToken() {
		$expectedValue = \json_encode([]);
		$this->reportDataCollector->method('getReportJson')->willReturn($expectedValue);

		// with auth-token
		$this->config->method('getAppValue')->willReturn('secret');
		$this->request->method('getParam')->willReturn('wrong');

		/** @var Response $result */
		$result = $this->controller->fromCli();

		$this->assertEquals(403, $result->getStatus());
		$this->assertInstanceOf(DataResponse::class, $result);
	}
}
