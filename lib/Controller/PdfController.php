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

namespace OCA\PdfTool\Controller;

use OCA\PdfTool\Service\PdfService;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class PdfController extends Controller
{

	/** @var string */
	protected string $AppName;

	/** @var IRequest */
	protected IRequest $req;

	/** @var PdfService */
	private PdfService $pdf;

	/** @var string */
	private string $UserId;

	use Errors;

	public function __construct($AppName, IRequest $req, PdfService $pdf, $UserId)
	{
		parent::__construct($AppName, $req);
		$this->AppName = $AppName;
		$this->UserId = $UserId;
		$this->req = $req;
		$this->pdf = $pdf;
	}

	/**
	 * @NoAdminRequired
	 */
	public function merge(array $fileList, string $outputFile = ''): DataResponse
	{

		return $this->handleExceptions(function () use ($fileList, $outputFile) {
			return $this->pdf->merge($fileList, $outputFile);
		});
	}

	/**
	 * @NoAdminRequired
	 */
	public function split(string $file): DataResponse
	{
		$filename = ['split(1).pdf', 'split(2).pdf'];
		return new DataResponse($filename);
	}

	/**
	 * @NoAdminRequired
	 */
	public function sort(string $file): DataResponse
	{
		$filename = ['sorted.pdf',];
		return new DataResponse($filename);
	}

	/**
	 * @NoAdminRequired
	 */
	public function pageCount(int $fileid): DataResponse
	{
		return $this->handleExceptions(function () use ($fileid) {
			return $this->pdf->countPages($fileid);
		});
	}

	/**
	 * @NoAdminRequired
	 */
	public function preview(array $request): DataResponse
	{
		$thumbnails = [
			'file1.pdf' => 'base64string',
			'file2.pdf' => 'base64string',
		]; // 2D array of base64 strings.
		return new DataResponse($thumbnails);
	}
}
