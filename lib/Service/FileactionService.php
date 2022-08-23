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

use OCA\PdfTool\Service\LogService;

use Exception;

use OCP\Files\IRootFolder;

class FilactionService {
    /** @var string */
    private string $appName;

    /** @var string */
    private string $userId;

    /** @var LogService */
    private LogService $logger;

    public function __construct(string $appName, LogService $logger, IRootFolder $rootFolder, $userId) {
        $this->appName = $appName;
        $this->logger = $logger;
        $this->rootFolder = $rootFolder;
        $this->userId = $userId;
    }

    private function inSameFolder(array $files) : bool {
        if(!sizeof($files)) {
            $this->logger->log('OCA\PdfTool\Service\FileactionService::inSameFolder: empty $files array from user ' . $userId . '.');
            return false;
        }
        if ($files[0]->gettype() !== 'file') return false;

        $folder = $files[0]->getParent();
        $storage = $files[0]->getStorage()->getId();
        foreach ($files as $file) {
            if($folder !== $file->getParent() || $storage !== $file->getStorage()->getId()) return false;
        }
        return true;
    }

    private function hasPermissions(int $fileId): bool {
        try {
            $file = $this->rootFolder->getById($fileId);
            if ($file->gettype() !== 'file') return false;
            $folder = $file->getParent();
            if($file->getPermissions() > 0 && $folder->getPermissions() > 5) return true;
            return false;
        } catch (Exception $e) {
            $message = 'OCA\PdfTool\ServiceFilactionService::checkPermissions: ' . $userId . 'tried to open file or folder.';
            $this->logger->log($message, $e);
            return false;
        }

    }
}