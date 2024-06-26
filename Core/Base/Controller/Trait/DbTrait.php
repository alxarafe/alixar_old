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

namespace Alxarafe\Base\Controller\Trait;

use Alxarafe\Base\Config;
use DoliModules\Install\Controller\InstallController;

trait DbTrait
{
    public static function connectDb(\stdClass|null $db = null): bool
    {
        if ($db === null) {
            return false;
        }

        $checkDatabase = Config::checkDatabaseConnection($db);
        if (!$checkDatabase) {
            (new InstallController())->doIndex();
            die();
        }
        return true;
    }

}
