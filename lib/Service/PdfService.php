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
        // TODO: Get user source folder
        // TODO: Get file nodes array
        // TODO: Set output file if not empty string
        // TODO: Make output folder
        // TODO: Assemble string of all absolute file paths separated by space
        // TODO: Assemble String of output file path
        // TODO: Execute ghostscript
        // TODO: Copy output file into user folder
        // TODO: Delete source and destination folder
        $args = '';
        foreach ($files as $file) {
            $args .= escapeshellarg($file) . ' ';
        }
        $result = shell_exec('gs -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=' . escapeshellarg($outputfile) . ' -dBATCH ' . $args);
        if ($result === NULL) throw new Exception('PdfTools merge(): An error has ocurred.');
        return $outputfile;
    }


}
