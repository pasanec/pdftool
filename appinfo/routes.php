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
