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
use setasign\Fpdi\Tcpdf\Fpdi;

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

		$pdf = new Fpdi();

		foreach ($fileNodes as $fileNode) {
			$filePath = $appFolder . $inputFolder->getName() . '/' . $fileNode->getName();
			$pageCount = $pdf->setSourceFile($filePath);
			for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
				$templateId = $pdf->importPage($pageNo);
				$size = $pdf->getTemplateSize($templateId);
				$pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
				$pdf->useTemplate($templateId);
			}
		}

		$pdf->Output($outputPath, 'F');

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
		$fileId = (int) $file['id'];
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

		$originalPdf = new Fpdi();
		$pageCount = $originalPdf->setSourceFile($filePath);

		foreach ($pageNumbers as $pageNumber) {
			if ($pageNumber >= $firstPage) {
				$newPdf = new Fpdi();
				for ($i = $firstPage; $i <= $pageNumber; $i++) {
					$templateId = $newPdf->importPage($i, Fpdi::PAGE_BOX_TRIMBOX, true, Fpdi::PAGE_BOX_TRIMBOX);
					$size = $newPdf->getTemplateSize($templateId);
					$newPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
					$newPdf->useTemplate($templateId);
				}
				$outputFileName = $outputfileBase . "_$firstPage-$pageNumber.pdf";
				$outputPath = $appFolder . $outputFolder->getName() . '/' . $outputFileName;
				$newPdf->Output($outputPath, 'F');
				$outputFileNames[] = $outputFileName;
				$firstPage = $pageNumber + 1;
			}
		}

		if ($firstPage <= $pageCount) {
			$newPdf = new Fpdi();
			for ($i = $firstPage; $i <= $pageCount; $i++) {
				$templateId = $newPdf->importPage($i, Fpdi::PAGE_BOX_TRIMBOX, true, Fpdi::PAGE_BOX_TRIMBOX);
				$size = $newPdf->getTemplateSize($templateId);
				$newPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
				$newPdf->useTemplate($templateId);
			}
			$outputFileName = $outputfileBase . "_$firstPage-$pageCount.pdf";
			$outputPath = $appFolder . $outputFolder->getName() . '/' . $outputFileName;
			$newPdf->Output($outputPath, 'F');
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
		$filePath = $this->fs->getAbsoluteFilepath($fileId);
		$pdf = new Fpdi();
		return $pdf->setSourceFile($filePath);
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