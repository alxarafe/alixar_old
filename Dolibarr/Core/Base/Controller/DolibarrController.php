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

namespace DoliCore\Base\Controller;

use DoliLib\DolibarrAuth;

class DolibarrController extends DolibarrPublicController
{
    public $object;
    public $username;

    public function __construct()
    {
        parent::__construct();
        if (!DolibarrAuth::isLogged()) {
            header('Location: ' . BASE_URL . '/index.php?module=Auth&controller=Login');
        }

        $this->username = DolibarrAuth::$user->login;

        return $this->index();
    }

    function filterPostInt($field)
    {
        return (int)$this->filterPost($field, 'int');
    }

    function filterPost($field, $filter)
    {
        if (!isset($_REQUEST[$field])) {
            return $this->object->$field ?? false;
        }
        return GETPOST($field, $filter);
    }

    public function index(bool $executeActions = true): bool
    {
        if (method_exists($this, 'loadRecord') && !$this->loadRecord()) {
            return false;
        }

        if (method_exists($this, 'loadPost') && !$this->loadPost()) {
            return false;
        }

        if (!parent::index($executeActions)) {
            return $this->action === null;
        }

        return true;
    }
}
