<?php

declare(strict_types=1);

namespace OCA\PdfTool\Settings;

use OCA\PdfTool\Service\SettingsService;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\TemplateResponse;

class Admin implements ISettings
{
	private IConfig $config;
	private SettingsService $settingsService;

	public function __construct(IConfig $config, SettingsService $settingsService)
	{
		$this->config = $config;
		$this->settingsService = $settingsService;
	}

	/**
	 * @return Template
	 */
	public function getForm(): TemplateResponse
	{
		$gsIsAvailable = $this->settingsService->isGsAvailable();
		$exiftoolIsAvailable = $this->settingsService->isExiftoolAvailable();

		$parameters = [
			'pdfToolEngine' => $this->settingsService->getEngine(),
			'pdfToolMaxPages' => $this->settingsService->getMaxPages(),
			'pdfToolMaxPdfs' => $this->settingsService->getMaxPdfs(),
			'gsIsAvailable' => $gsIsAvailable,
			'exiftoolIsAvailable' => $exiftoolIsAvailable,
		];

		// return new Template('pdftool', 'admin', 'blank', $parameters);
		return new TemplateResponse('pdftool', 'admin', $parameters);
	}

	public function getSection(): string
	{
		return 'pdftool';
	}

	public function getPriority(): int
	{
		return 10;
	}
}
