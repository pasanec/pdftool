<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\PdfTool\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	   ['name' => 'pdf#merge', 'url' => '/merge', 'verb' => 'POST'],
	   ['name' => 'pdf#split', 'url' => '/split', 'verb' => 'POST'],
	   ['name' => 'pdf#sort', 'url' => '/sort', 'verb' => 'POST'],
	   ['name' => 'pdf#preview', 'url' => '/preview', 'verb' => 'POST'],
	   ['name' => 'pdf#renderStatus', 'url' => '/status/{uuid}', 'verb' => 'GET'],
    ]
];
