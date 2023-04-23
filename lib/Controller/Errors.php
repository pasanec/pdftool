<?php

namespace OCA\PdfTool\Controller;

use Closure;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\NotPermittedException;

use OCA\PdfTool\Service\EmptyFilesArray;
use OCA\PdfTool\Service\FilesNotInSameFolder;
use OCA\PdfTool\Service\NoReadPermissionInFolder;

trait Errors {
	protected function handleExceptions(Closure $callback): DataResponse {
		try {
			return new DataResponse($callback());
		} catch (EmptyFilesArray $e) {
			$message = ['message' => 'EmptyFilesArray'];
			return new DataResponse($message, Http::STATUS_OK);
		} catch (FilesNotInSameFolder $e) {
            $message = ['message' => 'FilesNotInSameFolder'];
			return new DataResponse($message, Http::STATUS_OK);
        } catch (NoReadPermissionInFolder $e) {
            $message = ['message' => 'NoReadPermissionInFolder'];
			return new DataResponse($message, Http::STATUS_OK);
        } catch (NotPermittedException $e) {
            $message = ['message' => 'NotPermittedException'];
			return new DataResponse($message, Http::STATUS_OK);
        }
	}
}