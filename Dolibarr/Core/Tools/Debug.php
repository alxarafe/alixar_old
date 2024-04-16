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

use DoliCore\Tools\DebugBarCollector\DolLogsCollector;
use DoliCore\Tools\DebugBarCollector\DolQueryCollector;

/**
 * Class Debug
 *
 * This class is only needed for compatibility with Dolibarr.
 *
 * @package DoliCore\Tools
 */
abstract class Debug extends \Alxarafe\Tools\Debug
{
    public static function load()
    {
        parent::load();

        parent::getDebugBar()->addCollector(new DolQueryCollector());
        parent::getDebugBar()->addCollector(new DolLogsCollector());
    }
}
