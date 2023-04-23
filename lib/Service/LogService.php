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

use Psr\Log\LoggerInterface;
use Exception;

class LogService {
    private string $userId;
    private LoggerInterface $logger;
    private string $appName;

    public function __construct(string $appName, LoggerInterface $logger, $userId){
        $this->userId = $userId;
        $this->appName = $appName;
        $this->logger = $logger;
    }

    public function log(string $message, Exception $e = NULL) : void {
        if ($e === NULL) {
            $this->logger->error($message);
            return;
        }
        $extraContent = [
            'type' => gettype($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user' => $this->userId,
            'app' => $this->appName,
        ];
        $this->logger->error($message, $extraContent);
    }

    public function logInfo(string $message) : void {
        $this->logger->error($message);
    }
}