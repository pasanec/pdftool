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

class PdfService {

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

	public function __construct(string $appName, LogService $logger, FileactionService $fs, SettingsService $s, $userId) {
		$this->appName = $appName;
        $this->userId = $userId;
        $this->logger = $logger;
        $this->fs = $fs;
        $this->s = $s;
	}

    public function merge(array $files, string $outputfile = ''): string {
        if ($this->batchCountPages($files) > $this->s->getMaxPageCount()) {
            throw new Exception('Max page count of $this->s->getMaxPageCount() exceeded.');
        }
        if ($this->batchCountPages($files)) {
            throw new Exception('Max page count of $this->s->getMaxPageCount() exceeded.');
        }
        // Get user source folder
        $userSourceFolder = $this->fs->tellUserSourceFolder((int)$files[0]['id']);
        $this->logger->log('::merge: $files[0] ' . json_encode($files[0]['id']));
        $this->logger->log('::merge: $userSourceFolder name ' . $userSourceFolder->getName());
        // Get file nodes array

        $sourceData = $this->fs->copyToAppFolder($files);
        $inputFolder = $sourceData[0];
        $fileNodes = $sourceData[1];
        // Set output file if not empty string
        // if ($outputfile === '') {
        //     $outputfile = substr_replace($fileNodes[0]->getName(), strlen($fileNodes[0]->getName()), -4) . '-merged.pdf';
        // } else if (!strpos($outputfile, '.pdf', -4) || !strpos($outputfile, '.PDF', -4)) {
        //     $outputfile .= '.pdf';
        // }
        $outputfile = rtrim($outputfile, '.PDF');
        $outputfile = rtrim($outputfile, '.pdf');
        $outputfile .= '.pdf';
        
        // Make output folder
        $outputFolder = $this->fs->createFolder('output-merge-' . uniqid($this->userId));
        // $this->logger->log('::merge: ' . $inputFolder->getName());
        // Assemble string of all absolute file paths separated by space
        $filePaths = ' ';
        $appFolder = $this->fs->tellAppFolder();
        foreach ($fileNodes as $fileNode) {
            $filePaths .= $appFolder . $inputFolder->getName() . '/' . escapeshellarg($fileNode->getName()) . ' ';
        }
        // Assemble String of output file path for ghostscript
        $outputPath = $appFolder . $outputFolder->getName() . '/' . escapeshellarg($outputfile) . ' ';

        // Execute ghostscript
        $this->logger->log('::merge: ' . 'gs -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=' . $outputPath . ' -dBATCH ' . $filePaths);
        $result = shell_exec('gs -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=' . $outputPath . ' -dBATCH ' . $filePaths);
        // TODO: Custom Exception
        // $this->logger->log('::merge: result: ' . $result);
        if ($result === NULL) {
            throw new Exception('PdfTools merge(): An error has ocurred.');
        }
        // Copy output file into user folder
        $this->logger->log('::merge: outputFolder: ' . $outputFolder->getName());
        $this->logger->log('::merge: userSourceFolder: ' . $userSourceFolder->getName());
        // $this->logger->log('::merge: TYPEOF: ' . gettype(($outputFolder->getFile($outputfile))));
        //TODO: Copying files doesn't work.
        $srcFile = [];
        $srcFile[] = $outputFolder->getFile($outputfile);

        // $userFile = $this->fs->copyFilesToUserFolder($outputFolder, $userSourceFolder);
        $userFile = $this->fs->copyFilesToUserFolder($srcFile, $userSourceFolder);
        $this->logger->log('::merge: userFile size: ' . sizeof($userFile));
        // Delete source and destination folder
        //TODO: Function delete() doesn't exist.
        $inputFolder->delete();
        $outputFolder->delete();

        return $userFile[0]->getInternalPath();
    }

    public function split(array $file, array $splitPoints): bool {
        if ($this->countPages($file['id']) > $this->s->getMaxPageCount()) {
            throw new Exception('Max page count of $this->s->getMaxPageCount() exceeded.');
        }
        
        $maxSplitPage = max(array_values($splitPoints));
        if ($maxSplitPage > $this->countPages($file['id'])) {
            throw new Exception('Page number ' . $maxSplitPage . ' greater than file page count of ' . $this->countPages($file['id']) . ' pages.' );
        }
        $userSourceFolder = $this->fs->tellUserSourceFolder((int)$file['id']);
        $this->logger->log('::merge: $files[0] ' . json_encode($file['id']));
        $this->logger->log('::merge: $userSourceFolder name ' . $userSourceFolder->getName());

        // TODO: Create i/o temp folders copy file in source folder.
        $sourceData = $this->fs->copyToAppFolder([$file]);
        $inputFolder = $sourceData[0];
        $fileNode = $sourceData[1][0];

        $outputfile = rtrim($outputfile, '.PDF');
        $outputfile = rtrim($outputfile, '.pdf');
        // $outputfile .= '.pdf';
        $exportFolderName = $outputfile .= '.pdf';

        $outputFolder = $this->fs->createFolder('output-split-' . uniqid($this->userId));

        // TODO: Assemble input filename with source folder path.
        $appFolder = $this->fs->tellAppFolder();
        $filePath = $appFolder . $inputFolder->getName() . '/' . escapeshellarg($fileNode->getName()) . ' ';

        // TODO: Assemble output filename with source folder path.
        $outputPath = $appFolder . $outputFolder->getName() . '/' . escapeshellarg($outputfile);

        // TODO: Sort splitpoints by value ascending.
        asort($splitPoints);
        // TODO: Run gs command.
        $firstPage = 1;
        $outputFiles = [];
        foreach ($splitPoints as $splitPoint) {
            $outputFile = "$outputPath $firstPage-$splitPoint.pdf";
            $this->logger->log('::split: ' . "gs -dNOPAUSE -dQUIET -dBATCH -sOutputFile=$outputFile -dFirstPage=$firstPage -dLastPage=$splitPoint -sDEVICE=pdfwrite $filePath");
            $result = shell_exec("gs -dNOPAUSE -dQUIET -dBATCH -sOutputFile=$outputFile -dFirstPage=$firstPage -dLastPage=$splitPoint -sDEVICE=pdfwrite $filePath");
            if ($result === NULL) {
                throw new Exception('PdfTools split(): An error has ocurred.');
            }
            $outputFileNames[] = $outputFile;
        }
        
        // TODO: Copy files to user folder.
        $srcFiles = [];
        foreach ($outputFileNames as $outputFileName) {
            $srcFiles[] = $outputFolder->getFile($outputFileName);
        }
        

        // TODO: Create collection folder in user folder.
        $exportFolder = $this->fs->createExportFolder($exportFolderName, $userSourceFolder);

        $userFile = $this->fs->copyFilesToUserFolder($srcFiles, $exportFolder);
        $this->logger->log('::merge: userFile size: ' . sizeof($userFile));

        return true;
    }

    public function countPages(int $fileId): int {
        $filePath = $this->fs->getAbsoluteFilepath($fileId);
        $pageCount = (int) shell_exec("exiftool -T -PageCount \"$filePath\"");
        return $pageCount;
    }

    public function batchCountPages(array $files): int {
        $pageCount = 0;
        foreach ($files as $file) {
            $pageCount += $this->countPages($file['id']);
        }
        return $pageCount;
    }


}
