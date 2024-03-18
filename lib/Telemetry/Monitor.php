<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OC\License\LicenseFetcher;
use OCA\ConfigReport\ReportDataCollector;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ILogger;

class Monitor {
	private IConfig $config;
	private ILogger $logger;
	private IClientService $clientService;
	private ReportDataCollector $collector;

	public function __construct(IConfig $config, ILogger $logger, IClientService $clientService, ReportDataCollector $collector) {
		$this->config = $config;
		$this->logger = $logger;
		$this->clientService = $clientService;
		$this->collector = $collector;
	}

	public function sendTelemetry(): void {
		$telemetryOptIn = $this->config->getSystemValue('telemetry.enabled', true);
		if (!$telemetryOptIn) {
			return;
		}

		$data = $this->collectTelemetryData();

		$client = $this->clientService->newClient();
		try {
			# TODO: use a real domain
			$client->post('https://3qyh826rmd.execute-api.ap-southeast-1.amazonaws.com/default/pocTelemetry', [
				'body' => json_encode($data, JSON_THROW_ON_ERROR),
			]);
		} catch (\Exception $e) {
			$this->logger->logException($e);
		}
	}

	private function getLicenseKey(): string {
		try {
			$lf = \OC::$server->query(LicenseFetcher::class);
			if ($lf instanceof LicenseFetcher) {
				$l = $lf->getOwncloudLicense();
				if ($l) {
					return $l->getLicenseString();
				}
			}
		} catch (\Exception $e) {
			$this->logger->logException($e);
		}

		return "";
	}

	private function collectTelemetryData(): array {
		$licenseKey = $this->getLicenseKey();
		$report = $this->collector->getTelemetryReport($licenseKey);
		return [
			'id' => $this->config->getSystemValue('instanceid', 'no-instanceid-set'),
			'report' => $report
		];
	}
}
