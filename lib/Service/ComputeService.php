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

class ComputeService {

    /** @var string */
    private string $appName;

    /** @var string */
    private string $userId;

    /** @var LogService */
    private LogService $loggerl;

    public function __construct(LogService $logger, string $appName, $userId) {
		$this->appName = $appName;
        $this->userId = $userId;
        $this->logger = $logger;
	}

    public function split(array $snippets, int $fileId): array {
        try {
            // TODO: Copy file to app folder
            // TODO: Find file location
            // TODO: Split files
            // TODO: Return filepaths
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function merge(array $fileIds, string $fileName): void {
        try {
            // TODO: Find files location
            // TODO: Check filename, iterate if required to avoid overwrite (TODO: Function in FileactionService)
            // TODO: Merge files
            // TODO: Copy file to source location
        } catch (Exception $e) {
            throw $e
        }
    }

    public function sort(array $order, string $fileName, int $fileId): void {
        try {
            // TODO: create snippets array
            // TODO: Check filename, iterate if required to avoid overwrite (TODO: Function in FileactionService)
            // TODO: Merge files
            // TODO: Copy file to source location
        } catch (Exception $e) {
            throw $e
        }
    }

}