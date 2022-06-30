<?php
namespace OCA\PdfTool\Service;

use Psr\Log\LoggerInterface;
use Exception;

class LogService {
    private LoggerInterface $logger;
    private string $appName;

    public function __construct(LoggerInterface $logger, string $appName){
        $this->logger = $logger;
        $this->appName = $appName;
    }

    public function log(string $message, Exception $e = NULL) : void {
        if ($e === NULL) {
            $this->logger->error($message);
            return
        }
        $extraContent = [
            'type' => gettype($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString();,
        ];
        $this->logger->error($message, $extraContent);
    }

    public function logInfo(string $message) : void {
        $this->logger->error($message);
    }
}