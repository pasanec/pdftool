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
		$maxPageCount = $this->s->getMaxPageCount();
		$averagePageCount = $this->model->batchCountPages($files) / sizeof($files);
		if ($averagePageCount > $maxPageCount) {
			throw new MaxPageCountExceeded('The selected PDFs average ' . round($averagePageCount, 1) . ' pages per file, but the configured limit is ' . $maxPageCount . ' ' . $this->formatPages($maxPageCount) . '.');
		}
		$maxPdfs = $this->s->getMaxPdfs();
		$fileCount = sizeof($files);
		if ($fileCount > $maxPdfs) {
			throw new MaxPdfCountExceeded('You selected ' . $fileCount . ' ' . $this->formatPdfs($fileCount) . ', but the configured limit is ' . $maxPdfs . ' ' . $this->formatPdfs($maxPdfs) . '.');
		}
		return $this->model->merge($files, $outputfile);
	}

	private function normalizeFileList(array $files): array
	{
		if (isset($files['_data']) && is_array($files['_data']) && isset($files['_data']['id'])) {
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

			try {
				$normalized[] = $this->normalizeFile($file);
			} catch (Exception $e) {
				if (is_array($file)) {
					$normalized = array_merge($normalized, $this->normalizeFileList($file));
				}
			}
		}

		return $normalized;
	}

	private function normalizeFile($file): array
	{
		if (is_string($file)) {
			$decoded = json_decode($file, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return $this->normalizeFile($decoded);
			}
		}

		if (is_array($file) && isset($file['_data']) && is_array($file['_data']) && isset($file['_data']['id'])) {
			return $file;
		}

		if (is_array($file) && isset($file['id'])) {
			return ['_data' => ['id' => (int)$file['id']]];
		}

		if (is_array($file) && isset($file['nodes'])) {
			return $this->normalizeFile($file['nodes']);
		}

		if (is_array($file)) {
			foreach ($file as $entry) {
				if (is_array($entry) || is_string($entry)) {
					try {
						return $this->normalizeFile($entry);
					} catch (Exception $e) {
						continue;
					}
				}
			}
		}

		throw new Exception('No PDF file provided.');
	}

	public function split($file, array $splitPoints, string $outputFolder = ''): bool
	{
		$file = $this->normalizeFile($file);
		$pageCount = $this->countPages($file['_data']['id']);
		$maxPageCount = $this->s->getMaxPageCount();
		if ($pageCount > $maxPageCount) {
			throw new MaxPageCountExceeded('The selected PDF has ' . $pageCount . ' ' . $this->formatPages($pageCount) . ', but the configured limit is ' . $maxPageCount . ' ' . $this->formatPages($maxPageCount) . '.');
		}
		return $this->model->split($file, $splitPoints, $outputFolder);
	}

	private function formatPages(int $count): string
	{
		return $count === 1 ? 'page' : 'pages';
	}

	private function formatPdfs(int $count): string
	{
		return $count === 1 ? 'PDF' : 'PDFs';
	}

	public function countPages(int $fileId): int
	{
		return $this->model->countPages($fileId);
	}
}
