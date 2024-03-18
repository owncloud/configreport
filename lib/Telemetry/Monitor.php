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

require_once __DIR__ . '/../../vendor/autoload.php';

use OC\License\LicenseFetcher;
use OCA\ConfigReport\ReportDataCollector;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ILogger;
use Ramsey\Uuid\Uuid;

class Monitor {
	/** @var IConfig */
	private $config;
	/** @var ILogger */
	private $logger;
	/** @var IClientService */
	private $clientService;
	/** @var ReportDataCollector */
	private $collector;

	public function __construct(IConfig $config, ILogger $logger, IClientService $clientService, ReportDataCollector $collector) {
		$this->config = $config;
		$this->logger = $logger;
		$this->clientService = $clientService;
		$this->collector = $collector;
	}

	public function sendTelemetry(): bool {
		# only customer telemetry is sent over
		$licenseKey = $this->getLicenseKey();
		if ($licenseKey === "") {
			return false;
		}

		# telemetry is an opt-out feature
		$telemetryOptIn = $this->config->getSystemValue('telemetry.enabled', true);
		if (!$telemetryOptIn) {
			return false;
		}

		$data = $this->collectTelemetryData();

		$client = $this->clientService->newClient();
		$requestId = Uuid::uuid4()->toString();
		try {
			$telemetryUrl = 'https://telemetry.owncloud.com/oc10-telemetry';
			$client->post($telemetryUrl, [
				'body' => json_encode($data, JSON_THROW_ON_ERROR),
				'headers' => [
					'X-Request-Id' => $requestId,
				]
			]);
			$this->logger->info("Telemetry data submitted to $telemetryUrl with request id: $requestId");
			return true;
		} catch (\Exception $e) {
			$this->logger->logException($e);
			return false;
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
		$report['id'] = $this->getTelemetryId();
		return  $report;
	}

	private function getTelemetryId() {
		$id = $this->config->getAppValue("configreport", "telemetry.id", null);
		if ($id === null) {
			$id = Uuid::uuid4()->toString();
			$this->config->setAppValue("configreport", "telemetry.id", $id);
		}

		return $this->config->getAppValue("configreport", "telemetry.id", null);
	}
}
