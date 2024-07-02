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

use OCA\ConfigReport\Http\ReportResponse;
use OCA\ConfigReport\ReportDataCollector;
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
}
