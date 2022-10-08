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

namespace OCA\PdfTool\AppInfo;

use OCA\PdfTool\Listeners\AddMenuItemsListener;
use OCA\PdfTool\Listeners\AddEmptyDivListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher;
use OCP\Util;

class Application extends App {
	public const APP_ID = 'pdftool';

	public function __construct() {
		parent::__construct(self::APP_ID);
        /* @var IEventDispatcher $eventDispatcher */
        $dispatcher = $this->getContainer()->query(IEventDispatcher::class);
        $dispatcher->addListener(BeforeTemplateRenderedEvent::class, function(BeforeTemplateRenderedEvent $event) {
            // Util::addHeader('div', ['id' => 'pdftools-content'], 'pdftools');
            Util::addHeader('div', ['id' => 'pdftools-content'], '');
        });
        $dispatcher->addListener(LoadAdditionalScriptsEvent::class, function(LoadAdditionalScriptsEvent $event) {
            // Util::addScript(Application::APP_ID, 'pdfmenu', 'viewer');
            Util::addScript(Application::APP_ID, 'pdftool-main');
        });

	}

    // public function register(IRegistrationContext $context): void {
        
	// 	$context->registerEventListener(LoadAdditionalScriptsEvent::class, AddMenuItemsListener::class);

    // }

}