<?php

/**
 *
 * @copyright Copyright (c) 2023, Immanuel Pasanec (immanuel@pasanec.de)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace OCA\PdfTool\Service;

use OCP\IConfig;

class SettingsService
{
	private IConfig $config;
	private string $appName;

	public function __construct(IConfig $config, string $appName)
	{
		$this->config = $config;
		$this->appName = $appName;
	}

	public function getEngine(): string
	{
		return $this->config->getAppValue($this->appName, 'pdf_tool_engine', 'tcpdf');
	}

	public function setEngine(string $engine): void
	{
		$this->config->setAppValue($this->appName, 'pdf_tool_engine', $engine);
	}

	public function getMaxPages(): int
	{
		return (int)$this->config->getAppValue($this->appName, 'pdf_tool_max_pages', '60');
	}

	public function getMaxPageCount(): int
	{
		return $this->getMaxPages();
	}

	public function setMaxPages(int $maxPages): void
	{
		$this->config->setAppValue($this->appName, 'pdf_tool_max_pages', $maxPages);
	}

	public function getMaxPdfs(): int
	{
		return (int)$this->config->getAppValue($this->appName, 'pdf_tool_max_pdfs', '10');
	}

	public function setMaxPdfs(int $maxPdfs): void
	{
		$this->config->setAppValue($this->appName, 'pdf_tool_max_pdfs', $maxPdfs);
	}

	public function isGsAvailable(): bool
	{
		$output = shell_exec('which gs');
		return !empty($output) && strpos($output, 'no gs in') === false;
	}
}
