<?php

namespace OCA\PdfTools\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'pdftools';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

}