<?php

/**
 *
 * @copyright Copyright (c) 2025, Immanuel Pasanec (immanuel@pasanec.de)
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

namespace OCA\PdfTool\Models;

use OCA\PdfTool\Service\FileactionService;
use OCA\PdfTool\Service\LogService;
use OCA\PdfTool\Service\SettingsService;
use OCP\Files\IRootFolder;
use Exception;

class GhostScript implements IPdf
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

	public function __construct(string $appName, LogService $logger, FileactionService $fs, SettingsService $s, $userId)
	{
		$this->appName = $appName;
		$this->userId = $userId;
		$this->logger = $logger;
		$this->fs = $fs;
		$this->s = $s;
	}

	public function merge(array $files, string $outputfile = ''): string
	{
		if ($this->batchCountPages($files) / sizeof($files) > $this->s->getMaxPageCount()) {
			throw new Exception('Max page count of $this->s->getMaxPageCount() exceeded.');
		}
		// Get user source folder
		$userSourceFolder = $this->fs->tellUserSourceFolder((int)$files[0]['_data']['id']);
		$this->logger->log('::merge: $files[0] ' . json_encode($files[0]['_data']['id']));
		$this->logger->log('::merge: $userSourceFolder name ' . $userSourceFolder->getName());
		// Get file nodes array

		$sourceData = $this->fs->copyToAppFolder($files);
		$inputFolder = $sourceData[0];
		$fileNodes = $sourceData[1];
		$outputfile = rtrim($outputfile, '.PDF');
		$outputfile = rtrim($outputfile, '.pdf');
		$outputfile .= '.pdf';

		// Make output folder
		$outputFolder = $this->fs->createFolder('output-merge-' . uniqid($this->userId));
		$filePaths = ' ';
		$appFolder = $this->fs->tellAppFolder();
		foreach ($fileNodes as $fileNode) {
			$filePaths .= $appFolder . $inputFolder->getName() . '/' . escapeshellarg($fileNode->getName()) . ' ';
		}
		$outputPath = $appFolder . $outputFolder->getName() . '/' . escapeshellarg($outputfile) . ' ';

		$this->logger->log('::merge: ' . 'gs -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=' . $outputPath . ' -dBATCH ' . $filePaths);
		$result = shell_exec('gs -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=' . $outputPath . ' -dBATCH ' . $filePaths);
		if ($result === NULL) {
			throw new Exception('PdfTools merge(): An error has ocurred.');
		}

		// Copy output file into user folder
		$this->logger->log('::merge: outputFolder: ' . $outputFolder->getName());
		$this->logger->log('::merge: userSourceFolder: ' . $userSourceFolder->getName());
		$sourceFile = [];
		$sourceFile[] = $outputFolder->getFile($outputfile);

		$userFile = $this->fs->copyFilesToUserFolder($sourceFile, $userSourceFolder);
		$this->logger->log('::merge: userFile size: ' . sizeof($userFile));
		$inputFolder->delete();
		$outputFolder->delete();

		return $userFile[0]->getInternalPath();
	}

	public function split(array $file, array $pageNumbers): bool
	{
		$pageCount = $this->countPages($file['_data']['id']);
		if ($pageCount > $this->s->getMaxPageCount()) {
			throw new Exception('Max page count of ' . $this->s->getMaxPageCount() . ' exceeded.');
		}

		// Validate page numbers
		if (!empty($pageNumbers)) {
			$maxSplitPage = max(array_values($pageNumbers));
			if ($maxSplitPage > $pageCount) {
				throw new Exception('Page number ' . $maxSplitPage . ' greater than file page count of ' . $pageCount . ' pages.');
			}
		}

		$userSourceFolder = $this->fs->tellUserSourceFolder((int)$file['_data']['id']);
		$this->logger->log('::split: File ID ' . json_encode($file['_data']['id']));
		$this->logger->log('::split: User source folder name: ' . $userSourceFolder->getName());

		$inputFolder = null;
		$outputFolder = null;

		try {
			$sourceData = $this->fs->copyToAppFolder([$file]);
			$inputFolder = $sourceData[0];
			$fileNode = $sourceData[1][0];

			$originalFileName = $fileNode->getName();
			$outputfile = pathinfo($originalFileName, PATHINFO_FILENAME);

			$exportFolderName = $outputfile . '_split';

			$finalExportFolderName = $exportFolderName;
			$counter = 1;
			while ($userSourceFolder->nodeExists($finalExportFolderName)) {
				$finalExportFolderName = $exportFolderName . ' (' . $counter . ')';
				$counter++;
			}
			$exportFolderName = $finalExportFolderName;
			$exportFolder = $this->fs->createExportFolder($exportFolderName, $userSourceFolder);

			$outputFolder = $this->fs->createFolder('output-split-' . uniqid($this->userId));
			$appFolder = $this->fs->tellAppFolder();

			$inputFilePath = $appFolder . $inputFolder->getName() . '/' . $fileNode->getName();

			asort($pageNumbers);
			$pageNumbers = array_unique(array_values($pageNumbers));

			$firstPage = 1;
			$outputFilePaths = [];

			foreach ($pageNumbers as $lastPage) {
				if ($lastPage < $firstPage) {
					continue; // Skip if the page number is out of order
				}

				$outputFileName = $outputfile . "_p" . $firstPage . "-" . $lastPage . ".pdf";
				$outputFilePath = $appFolder . $outputFolder->getName() . '/' . $outputFileName;

				$command = sprintf(
					'gs -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s -dFirstPage=%d -dLastPage=%d -sDEVICE=pdfwrite %s',
					escapeshellarg($outputFilePath),
					$firstPage,
					$lastPage,
					escapeshellarg($inputFilePath)
				);

				$this->logger->log('::split: Executing: ' . $command);
				exec($command, $cmdOutput, $return_var);

				if ($return_var !== 0) {
					$this->logger->log('::split: GhostScript error: ' . implode("\n", $cmdOutput), ['level' => 'error']);
					throw new Exception('PdfTools split(): An error has occurred with GhostScript.');
				}

				$outputFilePaths[] = $outputFileName;
				$firstPage = $lastPage + 1;
			}

			// Process the last part of the PDF
			if ($firstPage <= $pageCount) {
				$lastPage = $pageCount;
				$outputFileName = $outputfile . "_p" . $firstPage . "-" . $lastPage . ".pdf";
				$outputFilePath = $appFolder . $outputFolder->getName() . '/' . $outputFileName;

				$command = sprintf(
					'gs -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s -dFirstPage=%d -dLastPage=%d -sDEVICE=pdfwrite %s',
					escapeshellarg($outputFilePath),
					$firstPage,
					$lastPage,
					escapeshellarg($inputFilePath)
				);

				$this->logger->log('::split: Executing: ' . $command);
				exec($command, $cmdOutput, $return_var);

				if ($return_var !== 0) {
					$this->logger->log('::split: GhostScript error: ' . implode("\n", $cmdOutput), ['level' => 'error']);
					throw new Exception('PdfTools split(): An error has occurred with GhostScript.');
				}
				$outputFilePaths[] = $outputFileName;
			}

			// Copy files to user folder
			$srcFiles = [];
			foreach ($outputFilePaths as $outputFileName) {
				if ($outputFolder->fileExists($outputFileName)) {
					$srcFiles[] = $outputFolder->getFile($outputFileName);
				} else {
					$this->logger->log('::split: Could not find generated file: ' . $outputFileName, ['level' => 'warning']);
				}
			}

			if (empty($srcFiles)) {
				throw new Exception('PdfTools split(): No files were generated.');
			}

			$this->fs->copyFilesToUserFolder($srcFiles, $exportFolder);
			$this->logger->log('::split: Successfully created ' . count($srcFiles) . ' split files.');

			return true;
		} catch (Exception $e) {
			$this->logger->log('::split: Exception: ' . $e->getMessage(), ['level' => 'error']);
			throw $e;
		} finally {
			if ($inputFolder !== null) {
				$inputFolder->delete();
			}
			if ($outputFolder !== null) {
				$outputFolder->delete();
			}
		}
	}

	public function countPages(int $fileId): int
	{
		if ($this->fs->getMimeType($fileId) !== 'application/pdf') {
			throw new Exception('File with id ' . $fileId . ' is not a PDF.');
		}
		$filePath = $this->fs->getAbsoluteFilepath($fileId);
		$escapedPath = addcslashes($filePath, '()\\');
		$command = "gs -q -dNODISPLAY -dNOSAFER -c \"(" . $escapedPath . ") (r) file runpdfbegin pdfpagecount = quit\"";
		$pageCount = (int) shell_exec($command);
		return $pageCount;
	}

	public function batchCountPages(array $files): int
	{
		$pageCount = 0;
		foreach ($files as $file) {
			$pageCount += $this->countPages($file['_data']['id']);
		}
		return $pageCount;
	}
}
