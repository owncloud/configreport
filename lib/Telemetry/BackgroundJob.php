<?php
/**
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

use OC\BackgroundJob\TimedJob;
use OCA\ConfigReport\AppInfo\Application;

class BackgroundJob extends TimedJob {
	public function __construct() {
		// Run once a day
		$this->setInterval(60 * 60 * 24);
	}

	protected function run($argument) {
		# send telemetry
		$m = $this->getMonitor();
		$m->sendTelemetry();
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
