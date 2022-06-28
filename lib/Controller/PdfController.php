<?php
namespace OCA\PdfTool\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class PdfController extends Controller {
	private $userId;

	public function __construct($AppName, IRequest $request, $UserId){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 */
	public function merge(array $fileList) : DataResponse {
		$filename = ['merged.pdf',];
		return new DataResponse($filename);
	}

	/**
	 * @NoAdminRequired
	 */
	public function split(string $file) : DataResponse {
		$filename = ['split(1).pdf', 'split(2).pdf'];
		return new DataResponse($filename);
	}

	/**
	 * @NoAdminRequired
	 */
	public function sort(string $file) : DataResponse {
		$filename = ['sorted.pdf', ];
		return new DataResponse($filename);
	}

	/**
	 * @NoAdminRequired
	 */
	public function preview(array $request) : DataResponse {
		$thumbnails = [ 
			'file1.pdf' => 'base64string',
			'file2.pdf' => 'base64string',
		 ]; // 2D array of base64 strings.
		return new DataResponse($thumbnails);
	}

}
