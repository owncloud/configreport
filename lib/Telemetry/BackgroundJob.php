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

use OC\BackgroundJob\Job;
use OCA\ConfigReport\AppInfo\Application;

class BackgroundJob extends Job {
	protected function run($argument) {
		$config = \OC::$server->getConfig();
		$utcNow = new \DateTimeImmutable("now", new \DateTimeZone('UTC'));
		$scheduler = new Scheduler($config);
		if (!$scheduler->isDue($utcNow)) {
			return;
		}
		# send telemetry
		$m = $this->getMonitor();
		if ($m->sendTelemetry()) {
			$scheduler->storeNextExecution($utcNow);
		}
	}

	private function getMonitor(): Monitor {
		return new Monitor(
			\OC::$server->getConfig(),
			\OC::$server->getLogger(),
			\OC::$server->getHTTPClientService(),
			Application::getCollector()
		);
	}
}
