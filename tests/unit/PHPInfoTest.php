<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2024, ownCloud GmbH
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

use OC\Files\External\StorageConfig;
use OC\Helper\UserTypeHelper;
use OC\IntegrityCheck\Checker;
use OC\SystemConfig;
use OC\User\Manager;
use OCA\ConfigReport\PHPInfo;
use OCA\ConfigReport\ReportDataCollector;
use OCP\Files\External\Auth\AuthMechanism;
use OCP\Files\External\Backend\Backend;
use OCP\Files\External\DefinitionParameter;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use Test\TestCase;
use OCP\Files\External\Service\IGlobalStoragesService;

class PHPInfoTest extends TestCase {
    public function testText(): void {
        $phpInfoText = file_get_contents(__DIR__ . '/data/phpinfo.txt');

        $p = new PHPInfo();
        $d = $p->parse($phpInfoText);
        self::assertEquals('7.4.33', $d['Configuration']['Core']['PHP Version']);
        self::assertArrayNotHasKey('include_path', $d['Configuration']['Core']);
        self::assertArrayNotHasKey('PHP Variables', $d);
    }

    public function testHtml(): void {
        $phpInfoText = file_get_contents(__DIR__ . '/data/phpinfo.html');

        $p = new PHPInfo();
        $d = $p->parse($phpInfoText);
        # testing one element
        self::assertEquals('8.3.8', $d['Core']['PHP Version']);
        self::assertArrayNotHasKey('include_path', $d['Core']);
        self::assertArrayNotHasKey('PHP Variables', $d);
    }
}
