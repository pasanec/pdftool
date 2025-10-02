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
use setasign\Fpdi\PdfReader\PageBoundaries;
use setasign\Fpdi\Tcpdf\Fpdi;
use Smalot\PdfParser\Parser as PdfParserParser;

class TcPdf implements IPdf
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

	/** @var PdfParserParser */
	private $pdfParser;

	/** @var Fpdi */
	private $fpdfi;

	public function __construct(string $appName, LogService $logger, FileactionService $fs, SettingsService $s, PdfParserParser $pdfParser, Fpdi $fpdfi, $userId)
	{
		$this->appName = $appName;
		$this->userId = $userId;
		$this->logger = $logger;
		$this->fs = $fs;
		$this->s = $s;
		$this->pdfParser = $pdfParser;
		$this->fpdfi = $fpdfi;
	}

	public function merge(array $files, string $outputfile = ''): string
	{
		if ($this->batchCountPages($files) / sizeof($files) > $this->s->getMaxPageCount()) {
			throw new Exception('Max page count of ' . $this->s->getMaxPageCount() . ' exceeded.');
		}

		$userSourceFolder = $this->fs->tellUserSourceFolder((int)$files[0]['_data']['id']);
		$this->logger->log('::merge: $files[0] ' . json_encode($files[0]['_data']['id']));
		$this->logger->log('::merge: $userSourceFolder name ' . $userSourceFolder->getName());

		$sourceData = $this->fs->copyToAppFolder($files);
		$inputFolder = $sourceData[0];
		$fileNodes = $sourceData[1];

		$outputfile = rtrim($outputfile, '.PDF');
		$outputfile = rtrim($outputfile, '.pdf');
		$outputfile .= '.pdf';

		$outputFolder = $this->fs->createFolder('output-merge-' . uniqid($this->userId));
		$appFolder = $this->fs->tellAppFolder();
		$outputPath = $appFolder . $outputFolder->getName() . '/' . $outputfile;

		foreach ($fileNodes as $fileNode) {
			$filePath = $appFolder . $inputFolder->getName() . '/' . $fileNode->getName();
			$pageCount = $this->fpdfi->setSourceFile($filePath);
			for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
				$templateId = $this->fpdfi->importPage($pageNo);
				$size = $this->fpdfi->getTemplateSize($templateId);
				$this->fpdfi->AddPage($size['orientation'], [$size['width'], $size['height']]);
				$this->fpdfi->useTemplate($templateId);
			}
		}

		$this->fpdfi->Output($outputPath, 'F');

		$srcFile = [];
		$srcFile[] = $outputFolder->getFile($outputfile);

		$userFile = $this->fs->copyFilesToUserFolder($srcFile, $userSourceFolder);
		$this->logger->log('::merge: userFile size: ' . sizeof($userFile));
		$inputFolder->delete();
		$outputFolder->delete();

		return $userFile[0]->getInternalPath();
	}

	public function split(array $file, array $pageNumbers, string $outputfile = ''): bool
	{
		$fileId = (int) $file['_data']['id'];
		if ($this->countPages($fileId) > $this->s->getMaxPageCount()) {
			throw new Exception('Max page count of ' . $this->s->getMaxPageCount() . ' exceeded.');
		}

		$maxSplitPage = max(array_values($pageNumbers));
		if ($maxSplitPage > $this->countPages($fileId)) {
			throw new Exception('Page number ' . $maxSplitPage . ' greater than file page count of ' . $this->countPages($fileId) . ' pages.');
		}
		$userSourceFolder = $this->fs->tellUserSourceFolder($fileId);
		$this->logger->log('::split: $fileId ' . json_encode($fileId));
		$this->logger->log('::split: $userSourceFolder name ' . $userSourceFolder->getName());

		$sourceData = $this->fs->copyToAppFolder([$file]);
		$inputFolder = $sourceData[0];
		$fileNode = $sourceData[1][0];

		$outputfileBase = rtrim($fileNode->getName(), '.PDF');
		$outputfileBase = rtrim($outputfileBase, '.pdf');

		$outputFolder = $this->fs->createFolder('output-split-' . uniqid($this->userId));
		$appFolder = $this->fs->tellAppFolder();
		$filePath = $appFolder . $inputFolder->getName() . '/' . $fileNode->getName();

		asort($pageNumbers);

		$firstPage = 1;
		$outputFileNames = [];

		$pageCount = $this->fpdfi->setSourceFile($filePath);

		foreach ($pageNumbers as $pageNumber) {
			if ($pageNumber >= $firstPage) {
				$pdf = new Fpdi();
				$pdf->setSourceFile($filePath);
				for ($i = $firstPage; $i <= $pageNumber; $i++) {
					try {
						$templateId = $pdf->importPage($i);
					} catch (\Throwable $t) {
						$this->logger->log("::split: Could not import page $i. Skipping. Error: " . $t->getMessage());
						continue;
					}
					$size = $pdf->getTemplateSize($templateId);
					if ($size['width'] == 0 || $size['height'] == 0) {
						$this->logger->log("::split: Skipping page $i due to invalid dimensions (width or height is zero).");
						continue;
					}
					$pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
					$pdf->useTemplate($templateId);
				}
				$outputFileName = $outputfileBase . "_$firstPage-$pageNumber.pdf";
				$outputPath = $appFolder . $outputFolder->getName() . '/' . $outputFileName;
				$pdf->Output($outputPath, 'F');
				$outputFileNames[] = $outputFileName;
				$firstPage = $pageNumber + 1;
			}
		}

		if ($firstPage <= $pageCount) {
			$pdf = new Fpdi();
			$pdf->setSourceFile($filePath);
			for ($i = $firstPage; $i <= $pageCount; $i++) {
				try {
					$templateId = $pdf->importPage($i);
				} catch (\Throwable $t) {
					$this->logger->log("::split: Could not import page $i. Skipping. Error: " . $t->getMessage());
					continue;
				}
				$size = $pdf->getTemplateSize($templateId);
				if ($size['width'] == 0 || $size['height'] == 0) {
					$this->logger->log("::split: Skipping page $i due to invalid dimensions (width or height is zero).");
					continue;
				}
				$pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
				$pdf->useTemplate($templateId);
			}
			$outputFileName = $outputfileBase . "_$firstPage-$pageCount.pdf";
			$outputPath = $appFolder . $outputFolder->getName() . '/' . $outputFileName;
			$pdf->Output($outputPath, 'F');
			$outputFileNames[] = $outputFileName;
		}


		$srcFiles = [];
		foreach ($outputFileNames as $outputFileName) {
			$srcFiles[] = $outputFolder->getFile($outputFileName);
		}

		$exportFolderName = $outputfileBase . '_split';
		$exportFolder = $this->fs->createExportFolder($exportFolderName, $userSourceFolder);

		$userFile = $this->fs->copyFilesToUserFolder($srcFiles, $exportFolder);
		$this->logger->log('::split: userFile size: ' . sizeof($userFile));

		$inputFolder->delete();
		$outputFolder->delete();

		return true;
	}

	public function countPages(int $fileId): int
	{
		if ($this->fs->getMimeType($fileId) !== 'application/pdf') {
			throw new Exception('File with id ' . $fileId . ' is not a PDF.');
		}
		try {
			$filePath = $this->fs->getAbsoluteFilepath($fileId);
			$this->logger->log("::countPages: $filePath ");
			$pdf = $this->pdfParser->parseFile($filePath);
			$details = $pdf->getDetails();
			if (isset($details['Pages'])) {
				$pageCount = $details['Pages'];
				return $pageCount;
			} else {
				// Fallback.
				$pages = $pdf->getPages();
				return count($pages);
			}
		} catch (Exception $e) {
			throw new Exception('Could not count pages of file with id ' . $fileId . '. ' . $e->getMessage());
		}
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
