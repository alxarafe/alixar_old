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

namespace Alxarafe\Base;

use Exception;
use PDO;
use stdClass;

abstract class Config
{
    private const  AVAILABLE_GROUPS = ['main', 'db'];

    private static ?stdClass $config = null;

    public static function setDbConfig($values)
    {
        $data = (object)$values;
        if (!self::checkDatabaseConnection($data)) {
            return false;
        }
        self::saveConfig();
        return static::setConfig('db', $values);
    }

    private static function checkDatabaseConnection($data)
    {
        $dsn = "$data->type:host=$data->host;dbname=$data->name;charset=$data->charset";
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new PDO($dsn, $data->user, $data->pass, $options);

            // Ejecutar una simple consulta para verificar la conexión
            $pdo->query('SELECT 1');

            return true;
        } catch (Exception $e) {
            // Capturar errores y devolver false si la conexión falla
            return false;
        }
    }

    /**
     * Updates the configuration file with the information it has in memory.
     *
     * @return bool
     */
    public static function saveConfig(): bool
    {
        if (empty(self::$config)) {
            return true;
        }
        return file_put_contents(self::getConfigFilename(), json_encode(self::$config, JSON_PRETTY_PRINT)) !== false;
    }

    private static function setConfig(string $group, array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        $group = trim(strtolower($group));
        if (!in_array($group, self::AVAILABLE_GROUPS)) {
            return false;
        }

        if (!isset(self::$config)) {
            self::$config = self::loadConfig();
            if (self::$config === null) {
                self::$config = new stdClass();
                self::$config->main = self::getDefaultMainFileInfo();
            }
        }

        $branch = self::$config->{$group} ?? new stdClass();
        foreach ($values as $key => $value) {
            $branch->{$key} = $value;
        }

        if (!empty($branch)) {
            self::$config->{$group} = $branch;
        }

        return static::saveConfig();
    }

    /**
     * Returns a stdClass with the program configuration.
     * If the configuration file does not exist, is not accessible, or is not correct, returns null.
     * The configuration is loaded from the file only once and stored in a variable. You can set $reload
     * to true to force a reload of the configuration file.
     *
     * @param bool $reload
     * @return stdClass
     */
    public static function loadConfig(bool $reload = false): ?stdClass
    {
        if (!$reload && isset(self::$config)) {
            return self::$config;
        }

        $filename = self::getConfigFilename();
        if (!file_exists($filename)) {
            return null;
        }

        $config = file_get_contents($filename);
        if ($config === false) {
            return self::$config;
        }

        $result = json_decode($config);
        if (json_last_error() === JSON_ERROR_NONE) {
            self::$config = $result;
        }

        return $result;
    }

    /**
     * Returns the config.json complete path.
     *
     * @return string
     */
    private static function getConfigFilename(): string
    {
        return realpath(BASE_PATH . '/..') . DIRECTORY_SEPARATOR . 'config.json';
    }

    private static function getDefaultMainFileInfo()
    {
        $result = new stdClass();
        $result->path = constant('BASE_PATH');
        $result->url = constant('BASE_URL');
        return $result;
    }

    public static function setMainConfig($values)
    {
        return static::setConfig('main', $values);
    }
}
