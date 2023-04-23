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

	public function __construct(string $appName, LogService $logger, FileactionService $fs, $userId) {
		$this->appName = $appName;
        $this->userId = $userId;
        $this->logger = $logger;
        $this->fs = $fs;
	}

    public function merge(array $files, string $outputfile = ''): string {
        // Get user source folder
        $userSourceFolder = $this->fs->tellUserSourceFolder((int)$files[0]['id']);
        $this->logger->log('::merge: $files[0] ' . json_encode($files[0]['id']));
        $this->logger->log('::merge: $userSourceFolder name ' . $userSourceFolder->getName());
        // Get file nodes array

        $sourceData = $this->fs->copyToAppFolder($files);
        $inputFolder = $sourceData[0];
        $fileNodes = $sourceData[1];
        // Set output file if not empty string
        if ($outputfile === '') {
            $outputfile = substr_replace($fileNodes[0]->getName(), strlen($fileNodes[0]->getName()), -4) . '-merged.pdf';
        } else if (!strpos($outputfile, '.pdf', -4) || !strpos($outputfile, '.PDF', -4)) {
            $outputfile .= '.pdf';
        }
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
        //TODO: Copying files doesn't work.
        $userFile = $this->fs->copyFilesToUserFolder($outputFolder, $userSourceFolder);
        $this->logger->log('::merge: userFile size: ' . sizeof($userFile));
        // Delete source and destination folder
        //TODO: Function delete() doesn't exist.
        // $this->inputFolder->delete();
        // $this->outputFolder->delete();

        return $userFile[0]->getInternalPath();
    }


}
