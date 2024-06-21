<?php

/* Copyright (C) 2024      Rafael San José      <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace DoliCore\Tools;

use Alxarafe\Tools\Debug;
use Alxarafe\Tools\Dispatcher as DispatcherBase;

class Dispatcher extends DispatcherBase
{
    /**
     * Process controller paths imported from Dolibarr. Try to locate it as a
     * modern controller first.
     *
     * This code is duplicated in Core/Dispatcher.
     * It can be refactored by creating an array with the possible "className" and
     * "filename", and looping through them all from Core/Dispatcher.
     * In that case, Dolibarr/Dispatcher would only have to add the new routes to
     * search for your drivers.
     *
     * @param string $module
     * @param string $controller
     * @return bool
     */
    protected static function processFolder(string $module, string $controller): bool
    {
        if (parent::processFolder($module, $controller)) {
            return true;
        }
        $className = 'DoliModules\\' . $module . '\\Controller\\' . $controller;
        $filename = realpath(constant('BASE_PATH') . '/../Dolibarr/Modules/' . $module . '/Controller/' . $controller . '.php');
        Debug::message('Filename: ' . $filename);
        Debug::message('Class: ' . $className);
        if (!file_exists($filename)) {
            return false;
        }
        $controller = new $className();
        if ($controller === null) {
            return false;
        }
        $controller->index();
        return true;
    }
}
