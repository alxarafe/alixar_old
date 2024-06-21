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

namespace Alxarafe\Tools;

class Dispatcher
{
    /**
     * Run the controller for the indicated module, if it exists.
     * Returns true if it can be executed.
     *
     * @param $module
     * @param $controller
     *
     * @return bool
     */
    public static function run($module, $controller): bool
    {
        $controller .= 'Controller';
        if (static::processFolder($module, $controller)) {
            Debug::message("Dispatcher::process(): Ok");
            return true;
        }
        Debug::message("Dispatcher::fail(): $module:$controller.");
        return false;
    }

    /**
     * Process modern application controller paths.
     *
     * @param string $module
     * @param string $controller
     * @return bool
     */
    protected static function processFolder(string $module, string $controller): bool
    {
        $className = 'Modules\\' . $module . '\\Controller\\' . $controller;
        $basepath = realpath(constant('BASE_PATH') . '/../Modules/' . $module);
        $filename = $basepath . '/Controller/' . $controller . '.php';
        Debug::message('Filename: ' . $filename);
        Debug::message('Class: ' . $className);
        if (!file_exists($filename)) {
            return false;
        }
        $controller = new $className();
        if ($controller === null) {
            return false;
        }
        if (method_exists($controller, 'setTemplatesPath')) {
            $controller->setTemplatesPath($basepath . '/Templates');
        }
        $controller->index();
        return true;
    }
}
