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

use DateTimeImmutable;
use DateTimeZone;
use OCA\ConfigReport\Telemetry\Scheduler;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SchedulerTest extends TestCase {
	/**
	 * @var IConfig|MockObject
	 */
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValue')->willReturnCallback(function ($key) {
			if ($key === 'telemetry.startHour') {
				return 6;
			}
			if ($key === 'telemetry.endHour') {
				return 12;
			}
			throw new \RuntimeException(':bomb:');
		});
	}

	public function testOutOfAllowedTimeframe(): void {
		$utcNow = new DateTimeImmutable("now", new DateTimeZone('UTC'));
		$tooLate = $utcNow->setTime(14, 0);
		$tooEarly = $utcNow->setTime(4, 0);

		$scheduler = new Scheduler($this->config);

		self::assertFalse($scheduler->isDue($tooLate));
		self::assertFalse($scheduler->isDue($tooEarly));
	}

	public function testFirstTime(): void {
		# 09:00 is later than 08:03 which is the calculated next execution time
		$due = new DateTimeImmutable("2024-07-02 09:00:00", new DateTimeZone('UTC'));

		$nextExecution = 0;
		$this->config->method('setAppValue')->willReturnCallback(function ($app, $key, $value) use (&$nextExecution) {
			if ($app === 'configreport' && $key === 'telemetry.nextSubmission') {
				$nextExecution = $value;
				return;
			}
			throw new \RuntimeException(':bomb:');
		});

		$scheduler = new Scheduler($this->config);
		Scheduler::$randomTest = 123;

		self::assertTrue($scheduler->isDue($due));
		self::assertEquals('2024-07-02T08:03:00+00:00', $nextExecution);
	}

	public function testNextExecution(): void {
		# 09:00 is later than 08:03 which is the calculated next execution time
		$due = new DateTimeImmutable("2024-07-02 09:00:00", new DateTimeZone('UTC'));

		$this->config->method('setAppValue')->willReturnCallback(function ($app, $key, $value) {
			throw new \RuntimeException(':bomb:');
		});
		$this->config->method('getAppValue')->willReturnCallback(function ($app, $key) use (&$nextExecution) {
			if ($app === 'configreport' && $key === 'telemetry.nextSubmission') {
				return '2024-07-02T08:03:00+00:00';
			}
			throw new \RuntimeException(':bomb:');
		});

		$scheduler = new Scheduler($this->config);
		Scheduler::$randomTest = 123;

		self::assertTrue($scheduler->isDue($due));
	}
}
