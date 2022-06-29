<?php

namespace OCA\PdfTool\Service;

use Exception;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use \OCP\Files\IRootFolder;

class PdfService {

    /** @var string */
    private $appName;

    /** @var string */
    private $userId;

    /** @var IRootFolder */
    private $rootFolder;

	public function __construct(IRootFolder $rootFolder, string $appName, $userId) {
		$this->appName = $appName;
        $this->userId = $userId;
        $this->rootFolder = $rootFolder;

	}

    private function checkPermission(int $fileId) : bool {
        try {
            $file = $this->rootFolder->getById($fileId);
            if ($file->gettype() !== 'file') return false;
            $folder = $file->getParent();
            if($file->getPermissions() > 0 && $folder->getPermissions() > 5) return true;
            return false;
        } catch (Exception $e) {
            // TODO: throw meaningful exception.
            return false;
        }

    }
}
