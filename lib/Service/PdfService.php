<?php

namespace OCA\PdfTool\Service;

use Exception;

use OCP\Files\IRootFolder;

class PdfService {

    /** @var string */
    private $appName;

    /** @var string */
    private $userId;

    /** @var IRootFolder */
    private $rootFolder;

    /** @var LogService */
    private $logger;

	public function __construct(IRootFolder $rootFolder, LogService $logger, string $appName, $userId) {
		$this->appName = $appName;
        $this->userId = $userId;
        $this->rootFolder = $rootFolder;
        $this->logger = $logger;
	}

    public function merge(array $files, string $outputfile): string {
        // TODO: logic for auto generate output file.
        // TODO: decide for input file format ($fileId or path)
        $args = '';
        foreach ($files as $file) {
            $args .= escapeshellarg($file) . ' ';
        }
        $result = shell_exec('gs -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=' . escapeshellarg($outputfile) . ' -dBATCH ' . $args);
        if ($result === NULL) throw new Exception('PdfTools merge(): An error has ocurred.');
        return $outputfile;
    }

    private function checkFileLocationIsSame(array $files) : bool {
        return false;
    }

    private function checkPermission(int $fileId): bool {
        try {
            $file = $this->rootFolder->getById($fileId);
            if ($file->gettype() !== 'file') return false;
            $folder = $file->getParent();
            if($file->getPermissions() > 0 && $folder->getPermissions() > 5) return true;
            return false;
        } catch (Exception $e) {
            $message = 'checkPermissions(): tried to open file or folder.';
            $this->logger->log($message, $e);
            return false;
        }

    }
}
