<?php

/* Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
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

namespace DoliCore\Lib;

abstract class Misc
{

    /**
     * It scans the $folderName folders that are within the folders that exist in
     * $searchPath, creating an associative array in which the index is the name
     * of the class, and the value is its full namespace.
     *
     * For example, with the default values it would return something like this...
     *
     * ^ array [▼
     *   "Accountancy" => "\DoliModules\Accounting\Model\Accountancy"
     *   "AccountancyCategory" => "\DoliModules\Accounting\Model\AccountancyCategory"
     *   ...
     * ]
     *
     * @param $searchPath
     * @param $folderName
     *
     * @return array
     */
    private static function createClassPaths($searchPath = '/../Deprecated/Modules', $folderName = 'Model')
    {
        $result = [];
        $path = realpath(BASE_PATH . $searchPath) . DIRECTORY_SEPARATOR;
        $folders = scandir($path);
        foreach ($folders as $folder) {
            $folderPath = realpath($path . $folder . DIRECTORY_SEPARATOR . $folderName);
            if (!is_dir($folderPath)) {
                continue;
            }
            $classes = scandir($folderPath);
            foreach ($classes as $class) {
                if (!str_ends_with($class, '.php') || str_ends_with($class, '.class.php')) {
                    continue;
                }
                $className = substr($class, 0, -4);
                $result[$className] = "\\DoliModules\\$folder\\$folderName\\$className";
            }
        }
        return $result;
    }

    /**
     * Same as createClassPaths, but trying to use a file cache.
     *
     * @param $searchPath
     * @param $folderName
     *
     * @return array|false|mixed
     */
    public static function getClassPaths($searchPath = '/../Deprecated/Modules', $folderName = 'Model')
    {
        $path = realpath(BASE_PATH . '/../tmp');
        if (!is_dir($path) && !mkdir($path)) {
            // Failed to create directory for cache
            return static::createClassPaths();
        }
        $file = $path . '/classpaths.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        $data = static::createClassPaths();
        file_put_contents($file, json_encode($data));
        return $data;
    }

    /**
     * Loads a model class given the name and an alternative parameter to be passed to
     * the model during creation (usually a db instance is passed to it).
     *
     * @param $name
     * @param $param
     *
     * @return mixed|null
     */
    public static function loadModel($name, $param = null)
    {
        $classes = static::getClassPaths();
        if (!isset($classes[$name])) {
            return null;
        }

        if ($param === null) {
            return new $classes[$name]();
        }

        return new $classes[$name]($param);
    }
}
