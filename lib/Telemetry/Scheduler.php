<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2024, ownCloud GmbH
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
 *
 */

namespace OCA\ConfigReport\Telemetry;

use OCP\IConfig;

class Scheduler {
	/** @var int */
	public static $randomTest = -1;
	/** @var IConfig */
	private $config;

	public function __construct(
		IConfig $config
	) {
		$this->config = $config;
	}

	public function isDue(\DateTimeImmutable $utcNow): bool {
		# in qa this needs adjustments
		$telemetryStartHour = $this->config->getSystemValue('telemetry.startHour', 6);
		$telemetryEndHour = $this->config->getSystemValue('telemetry.endHour', 12);

		$transmissionStart = $utcNow->setTime($telemetryStartHour, 0);
		$transmissionEnd = $utcNow->setTime($telemetryEndHour, 0);
		if ($utcNow < $transmissionStart) {
			return false;
		}
		if ($utcNow > $transmissionEnd) {
			return false;
		}

		# only once a day
		$nextExecution = $this->config->getAppValue("configreport", "telemetry.nextSubmission", null);
		if ($nextExecution === null) {
			$max_minutes = ($telemetryEndHour - $telemetryStartHour) * 60;
			$offset_minutes = $this->random(0, $max_minutes);
			$interval = \DateInterval::createFromDateString("$offset_minutes minutes");
			$nextExecution = $transmissionStart->add($interval);

			$this->config->setAppValue("configreport", "telemetry.nextSubmission", $nextExecution->format(\DateTimeInterface::ATOM));
		} else {
			$nextExecution = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $nextExecution);
		}

		return $utcNow->getTimestamp() >= $nextExecution->getTimestamp();
	}

	public function storeNextExecution(\DateTimeImmutable $executionDateTime): void {
		# set next execution to now + 24h
		$this->config->setAppValue("configreport", "telemetry.nextSubmission", $executionDateTime->add(\DateInterval::createFromDateString('1 day'))->format(\DateTimeInterface::ATOM));
	}

	private function random(int $min, int $max): int {
		if (self::$randomTest >=0) {
			return self::$randomTest;
		}
		return random_int($min, $max);
	}
}
