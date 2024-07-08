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

namespace OCA\ConfigReport;

class PHPInfo {
	private $sensitiveServerConfigs = [
		'HTTP_COOKIE',
		'PATH',
		'Cookie',
		'include_path',
	];

	/**
	 * Calls phpinfo() and parses the data - irrelevant and sensitive data will be removed.
	 * @return array
	 */
	public function parsePhpInfo(): array {
		$p = $this->load();
		return $this->parse($p);
	}

	/**
	 * @internal
	 *
	 * Parses the text or html based output of PHPInfo::load()
	 * @param string $phpInfo
	 * @return array
	 */
	public function parse(string $phpInfo): array {
		if ($phpInfo === "") {
			return [];
		}
		if (str_contains($phpInfo, "<html")) {
			return $this->parseHtml($phpInfo);
		}
		return $this->parseText($phpInfo);
	}

	/**
	 * @internal
	 *
	 * Calls phpinfo() and captures stdout which is returned as string
	 * @return string
	 */
	public function load(): string {
		// Get the phpinfo, parse it, and record it (parts from http://www.php.net/manual/en/function.phpinfo.php#87463)
		\ob_start();
		\phpinfo(INFO_ALL & ~INFO_ENVIRONMENT & ~INFO_CREDITS & ~INFO_VARIABLES & ~INFO_LICENSE);
		$ob = \ob_get_clean();
		return $ob === false ? '' : $ob;
	}

	private function parseHtml(string $phpInfo): array {
		$phpinfo = \preg_replace(
			['#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
				'#<h1>Configuration</h1>#', "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
				"#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
				'#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
				. '<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
				'#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
				'#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
				"# +#", '#<tr>#', '#</tr>#'],
			['$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
				'<h2>PHP Configuration</h2>' . "\n" . '<tr><td>PHP Version</td><td>$2</td></tr>' .
				"\n" . '<tr><td>PHP Egg</td><td>$1</td></tr>',
				'<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
				'<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
				'<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'],
			$phpInfo
		);

		$sections = \explode('<h2>', \strip_tags($phpinfo, '<h2><th><td>'));
		unset($sections[0]);

		$result = [];
		$sensitiveServerConfigs = \array_flip($this->sensitiveServerConfigs);
		foreach ($sections as $section) {
			$n = \substr($section, 0, \strpos($section, '</h2>'));
			\preg_match_all(
				'#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
				$section,
				$matches,
				PREG_SET_ORDER
			);
			foreach ($matches as $match) {
				if (isset($sensitiveServerConfigs[$match[1]])) {
					continue;
					// filter all key which contain 'password'
				}
				if (!isset($match[3])) {
					$value = $match[2] ?? null;
				} elseif ($match[2] == $match[3]) {
					$value = $match[2];
				} else {
					$value = \array_slice($match, 2);
				}
				$result[$n][$match[1]] = $value;
			}
		}

		return $result;
	}

	private function parseText(string $phpInfo): array {
		[$header, $body] = explode(' _______________________________________________________________________', $phpInfo);
		if ($header === '' || $body === '') {
			return [];
		}
		$header = explode(PHP_EOL, trim($header));
		$body = explode(PHP_EOL, trim($body));

		$array = $this->parseSingleTextBlock($header);

		$processedBody = $this->parseTextBlocks($body, ['Configuration', 'Environment', 'PHP Variables', 'PHP License']);
		foreach ($processedBody as $k => $v) {
			switch($k) {
				case 'Configuration':
					$modules = $this->parseTextBlocks($v, get_loaded_extensions());
					foreach ($modules as $moduleName => $moduleSettings) {
						$array['Configuration'][$moduleName] = $this->parseSingleTextBlock($moduleSettings);
					}
					break;
				case 'PHP License':
					array_shift($v);
					$array[$k] = implode(' ', $v);
					break;
				default:
					$array[$k] = $this->parseSingleTextBlock($v);
					break;
			}
		}
		return $array;
	}

	private function parseTextBlocks($blocks, $blockKeys): array {
		$settings = [];
		$currentKey = null;
		$currentBlock = [];
		foreach ($blocks as $line) {
			$line = trim($line);

			if (\in_array($line, $blockKeys)) {
				# Each extension block starts with the name of the extension. And if the current line is such a line, then we
				#   need to start a new block, but before that, we need to process the currentBlock and assign its results
				#   to the currentKey
				if ($currentKey != null) {
					$settings[$currentKey] = $currentBlock;
				}
				$currentKey = $line;
				$currentBlock = [];
			}

			# If the currentKey is not null, then we are in an extension block, and so this line gets added to the currentBlock
			#   currentKey would be null when this foreach loop starts, and until the first extension block is encountered
			if ($currentKey != null) {
				$currentBlock[] = $line;
			}
		}
		if ($currentKey != null) {
			$settings[$currentKey] = $currentBlock;
		}
		return $settings;
	}

	private function parseSingleTextBlock($block): array {
		$settings = [];
		$currentKey = null;
		$sensitiveServerConfigs = \array_flip($this->sensitiveServerConfigs);

		foreach ($block as $line) {
			$line = trim($line);
			if ($line !== '') {
				if (strpos($line, '=>') !== false) {
					$parts = explode('=>', $line);
					$parts[0] = trim($parts[0]);
					$parts[1] = trim($parts[1]);
					switch(\count($parts)) {
						case 2:
							if (
								$parts[0] !== 'Variable' &&
								$parts[1] !== 'Value'
							) {
								$currentKey = $parts[0];
								// filter all key which contain 'password'
								if (!isset($sensitiveServerConfigs[$currentKey])) {
									$settings[$currentKey] = $parts[1];
								}
							}
							break;
						case 3:
							$parts[2] = trim($parts[2]);
							if (
								$parts[0] !== 'Directive' &&
								$parts[1] !== 'Local Value' &&
								$parts[2] !== 'Master Value'
							) {
								$currentKey = $parts[0];
								// filter all key which contain 'password'
								if (!isset($sensitiveServerConfigs[$currentKey])) {
									$settings[$currentKey] = [
										'Local Value' => $parts[1],
										'Master Value' => $parts[2]
									];
								}
							}
							break;
					}
				} else {
					if ($currentKey !== null) {
						if (!isset($sensitiveServerConfigs[$currentKey])) {
							$settings[$currentKey] .= $line;
						}
					}
				}
			} else {
				$currentKey = null;
			}
		}
		return $settings;
	}
}
