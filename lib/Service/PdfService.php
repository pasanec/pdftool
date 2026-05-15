<?php

/**
 *
 * @copyright Copyright (c) 2022, Immanuel Pasanec (immanuel@pasanec.de)
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

namespace OCA\PdfTool\Service;

use OCP\Files\IRootFolder;

use Exception;
use OCA\PdfTool\Models\GhostScript;
use OCA\PdfTool\Models\TcPdf;
use OCA\PdfTool\Models\IPdf;

class PdfService
{

	/** @var string */
	private $appName;

	/** @var string */
	private $userId;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var LogService */
	private $logger;

	/** @var FileactionService */
	private $fs;

	/** @var SettingsService */
	private $s;

	/** @var string */
	private $engine;

	/** @var IPdf */
	private $model;


	public function __construct(string $appName, LogService $logger, FileactionService $fs, SettingsService $s, TcPdf $tcpdf, GhostScript $gs, $userId)
	{
		$this->appName = $appName;
		$this->userId = $userId;
		$this->logger = $logger;
		$this->fs = $fs;
		$this->s = $s;
		$engine = $s->getEngine();
		if ($engine === 'tcpdf') {
			$this->model = $tcpdf;
		} elseif ($engine === 'gs') {
			$this->model = $gs;
		} else {
			throw new Exception('Unknown PDF engine configured: ' . $engine);
		}
	}

	public function merge(array $files, string $outputfile = ''): string
	{
		$files = $this->normalizeFileList($files);
		if (sizeof($files) === 0) {
			throw new Exception('No PDF files provided for merge.');
		}
		if ($this->model->batchCountPages($files) / sizeof($files) > $this->s->getMaxPageCount()) {
			throw new Exception('Max page count of ' . $this->s->getMaxPageCount() . ' exceeded.');
		}
		if (sizeof($files) > $this->s->getMaxPdfs()) {
			throw new Exception('Max PDF count of ' . $this->s->getMaxPdfs() . ' exceeded.');
		}
		return $this->model->merge($files, $outputfile);
	}

	private function normalizeFileList(array $files): array
	{
		if (isset($files['_data']) && is_array($files['_data'])) {
			return [$files];
		}

		if (isset($files['id'])) {
			return [['_data' => ['id' => (int)$files['id']]]];
		}

		if (isset($files['nodes']) && is_array($files['nodes'])) {
			return $this->normalizeFileList($files['nodes']);
		}

		$normalized = [];
		foreach ($files as $file) {
			if (is_string($file)) {
				$decoded = json_decode($file, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
					$normalized = array_merge($normalized, $this->normalizeFileList($decoded));
				}
				continue;
			}

			if (is_array($file)) {
				$normalized = array_merge($normalized, $this->normalizeFileList($file));
			}
		}

		return $normalized;
	}

	public function split(array $file, array $splitPoints): bool
	{
		$pageCount = $this->countPages($file['_data']['id']);
		if ($pageCount > $this->s->getMaxPageCount()) {
			throw new Exception('Max page count of ' . $this->s->getMaxPageCount() . ' exceeded.');
		}
		return $this->model->split($file, $splitPoints);
	}

	public function countPages(int $fileId): int
	{
		return $this->model->countPages($fileId);
	}
}
