<?php

declare(strict_types=1);

namespace OCA\PdfTool\Controller;

use OCA\PdfTool\Service\SettingsService;
use OCA\PdfTool\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SettingsController extends Controller
{
	private SettingsService $settings;

	public function __construct(string $appName, IRequest $request, SettingsService $settings)
	{
		parent::__construct($appName, $request);
		$this->settings = $settings;
	}

	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function updateAdmin(string $engine, int $maxPages, int $maxPdfs): DataResponse
	{
		if (!in_array($engine, ['gs', 'tcpdf'], true)) {
			return new DataResponse(['message' => 'Invalid PDF engine.'], Http::STATUS_BAD_REQUEST);
		}

		if ($maxPages < 1) {
			return new DataResponse(['message' => 'PDF max page count must be at least 1.'], Http::STATUS_BAD_REQUEST);
		}

		if ($maxPdfs < 1) {
			return new DataResponse(['message' => 'Max number of PDFs must be at least 1.'], Http::STATUS_BAD_REQUEST);
		}

		$this->settings->setEngine($engine);
		$this->settings->setMaxPages($maxPages);
		$this->settings->setMaxPdfs($maxPdfs);

		return new DataResponse([
			'engine' => $this->settings->getEngine(),
			'maxPages' => $this->settings->getMaxPages(),
			'maxPdfs' => $this->settings->getMaxPdfs(),
		]);
	}
}
