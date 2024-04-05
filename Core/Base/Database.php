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

namespace Alxarafe\Base;

use Alxarafe\Tools\Debug;
use DebugBar\DataCollector\PDO\PDOCollector;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

/**
 * Create a database connection
 *
 * @package Alxarafe\Base
 */
class Database extends CapsuleManager
{
    /**
     * It can be received as a database type, the name used in Dolibarr. For compatibility,
     * if the name used in Dolibarr is in the index (e.g. mysqli), the value will be used
     * (e.g. mysql).
     */
    const DB_TYPES = [
        'mysqli' => 'mysql',
    ];

    /**
     * Construct the database access
     *
     * @param $db
     */
    function __construct($db)
    {
        parent::__construct();

        $type = self::DB_TYPES[$db->type] ?? $db->type;

        $this->addConnection([
            'driver' => $type,
            'host' => $db->host,
            'database' => $db->name,
            'username' => $db->user,
            'password' => $db->pass,
            'charset' => $db->charset,
            'collation' => $db->collation,
            'prefix' => $db->prefix,
        ]);
        $this->setAsGlobal();
        $this->bootEloquent();

        // Obtains Eloquent PDO
        $pdo = $this->getConnection()->getPdo();
        Debug::getDebugBar()->addCollector(new PDOCollector($pdo));
    }
}
