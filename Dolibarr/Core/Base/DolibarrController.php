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

namespace DoliCore\Base;

/**
 * Class DolibarrController. Controller to carry out the migration from Dolibarr to Alixar.
 *
 * @package    Alxarafe\Base
 *
 * @package    DoliCore\Base
 */
abstract class DolibarrController extends DolibarrViewController
{
    public $conf;
    public $config;
    public $db;
    public $menumanager;
    public $hookmanager;
    public $user;
    public $langs;

    public function __construct()
    {
        $this->conf = Config::getConf();
        $this->config = Config::getConfig($this->conf);
        $this->db = Config::getDb($this->conf);
        $this->hookmanager = Config::getHookManager();
        $this->user = Config::getUser();
        $this->menumanager = Config::getMenuManager($this->conf);
        $this->langs = Config::getLangs($this->conf);

        parent::__construct();
    }

    public function doLogin($user, $password)
    {
        return true;
    }

    private function isLogged()
    {
        return true;
    }
}
