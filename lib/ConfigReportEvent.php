<?php
/**
 * @author Sujith Haridasan
 * @copyright Copyright (c) 2017, ownCloud GmbH.
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

namespace OCA\ConfigReport;
use Symfony\Component\EventDispatcher\GenericEvent;

class ConfigReportEvent extends GenericEvent {

	protected $order;

	public function __construct(){
		$this->order = [];
	}

	/**
	 * @return array
	 */
	public function getConfigReportData() {
		return $this->order;
	}

	/**
	 * @param array $data
	 */
	public function addConfigReportData($data) {
		array_push($this->order, $data);
	}
}
