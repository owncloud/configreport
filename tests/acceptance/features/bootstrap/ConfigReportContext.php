<?php
/**
 * ownCloud
 *
 * @author David Toledo <dtoledo@owncloud.com>
 * @copyright Copyright (c) 2018 David Toledo dtoledo@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use Behat\Behat\Context\Context;

require_once 'bootstrap.php';

/**
 * Context for config report specific steps
 */
class ConfigReportContext implements Context {

	/**
	 * @var FeatureContext
	 */
	private $featureContext;
}
