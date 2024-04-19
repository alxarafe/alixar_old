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

// use Illuminate\Database\Capsule\Manager as DB;

/**
 * Class Controller. Controller is the general purpose controller and requires the user to be authenticated.
 *
 * @package Alxarafe\Base
 */
abstract class Controller extends ViewController
{
    public function __construct()
    {
        // $db = DB::connection()->getPdo();

        parent::__construct();
    }

    private function isLogged()
    {
        return true;
    }

    public function doLogin($user, $password)
    {
        return true;
    }

    public function index(bool $executeActions = true): bool
    {
        $executeActions = $executeActions && $this->isLogged();
        if (!$executeActions) {
            $this->template = 'auth/login';
        }
        return parent::index($executeActions);
    }
}
