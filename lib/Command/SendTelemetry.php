<?php
/**
 * @copyright Copyright (c) 2024, ownCloud GmbH.
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

namespace OCA\ConfigReport\Command;

use OCA\ConfigReport\AppInfo\Application;
use OCA\ConfigReport\Telemetry\Monitor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendTelemetry extends Command {
	protected function configure() {
		$this
			->setName('configreport:send-telemetry')
			->setDescription('send telemetry to backend');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$monitor = new Monitor(
			\OC::$server->getConfig(),
			\OC::$server->getLogger(),
			\OC::$server->getHTTPClientService(),
			Application::getCollector()
		);

		$monitor->sendTelemetry();
		return 0;
	}
}
