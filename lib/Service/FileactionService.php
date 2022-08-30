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

use Exception;

use OCP\Files\IRootFolder;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Files\Folder;
use OCP\Files\NotPermittedException;
use OCP\IConfig;

class FileactionService {
    /** @var string */
    private string $appName;

    /** @var string */
    private string $userId;

    /** @var LogService */
    private LogService $logger;

    /** @var IAppData */
    private IAppData $appData;

    /** @var IRootFolder */
    private IRootFolder $rootFolder;

    /** @var IConfig */
    private IConfig $config;

    public function __construct(string $appName, LogService $logger, IRootFolder $rootFolder, IAppData $appData, IConfig $config, string $userId) {
        $this->appName = $appName;
        $this->logger = $logger;
        $this->rootFolder = $rootFolder;
        $this->appData = $appData;
        $this->userId = $userId;
        $this->config = $config;
    }

    public function tellUserSourceFolder(int $fileId): Folder {
        return $this->rootFolder->getById($fileId)->getParent();
    }

    public function tellAppFolder(): string {
        $dataFolder = $this->config->getSystemValue('datadirectory');
        $instanceId = $this->config->getSystemValue('instanceid');
        $appFolder = $dataFolder . '/appdata_' . $instanceId . '/' . $this->appName . '/';
        return $appFolder;
    }

    public function copyToAppFolder(array $files): array {
        if(!sizeof($files)) {
            $this->logger->log('OCA\PdfTool\Service\FileactionService::copyToAppFolder: empty $files array from user ' . $this->userId . '.');
            throw new EmptyFilesArray('Empty $files array from user ' . $this->userId);
        }
        if(!$this->hasPermissions($files[0])) {
            $this->logger->log('OCA\PdfTool\Service\FileactionService::copyToAppFolder: ' . $this->userId . ' has no read permission in folder.');
            throw new NoReadPermissionInFolder($this->userId . ' has no read permission in folder of file ' . $files[0]);
        }
        $sourceNodes = [];
        try {
            foreach ($files as $file) {
                $sourceNodes[] = $this->rootFolder->getById($file);
            }
        } catch (Exception $e) {
            throw $e;
        }
        if(!$this->inSameFolder($sourceNodes)) {
            $fileList = '';
            foreach ($files as $file) {
                $fileList .= $file . ' ';
            }
            throw new FilesNotInSameFolder($fileList);
        }
        $sourceFolder = $this->appData->newFolder('source-' . uniqid($this->userId));
        $nodes = [];
        try {
            foreach ($sourceNodes as $sourceNode) {
                $nodes[] = $sourceFolder->newFile($sourceNode->getName(), $sourceNode->fopen('r'));
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $nodes;
    }

    public function createFolder(string $name): ISimpleFolder {
        // Returns ISimpleFolder
        return $this->appData->newFolder($name . '-' . uniqid($this->userId));
    }

    public function cleanup(string $folder): void {
        try {
            $this->appData->getFolder($folder)->delete();
        } catch (Exception $e) {
            $this->logger->log('OCA\PdfTool\Service\FileactionService::cleanup: of ' . $folder . ' failed', $e);
            // Cleanup is not vital for successful pdf convertion.
            // throw $e;
        }
    }

    public function copyFilesToUserFolder(ISimpleFolder $outputFolder, Folder $userFolder): void {
        if ($userFolder->getPermissions() < 4) {
            throw new NotPermittedException('No read permission for ' . $userFolder->getFullPath() . ' by ' . $this->userId);
        }
        $list = $outputFolder->getDirectoryListing();
        foreach ($list as $node) {
            $userFolder->newFile($userFolder->getNonExistingName($node->getName()), $node->read());
        }
    }

    private function inSameFolder(array $files): bool {

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
            $message = 'OCA\PdfTool\ServiceFilactionService::checkPermissions: ' . $this->userId . 'tried to open file or folder.';
            $this->logger->log($message, $e);
            return false;
        }

    }
}