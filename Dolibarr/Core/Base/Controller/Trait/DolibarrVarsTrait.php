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

namespace DoliCore\Base\Controller\Trait;

use Alxarafe\Base\Config;
use DoliCore\Tools\Load;

trait DolibarrVarsTrait
{
    public $conf;
    public $config;
    public $db;
    public $menumanager;
    public $hookmanager;
    public $user;
    public $langs;
    public $mysoc;

    public function loadVars()
    {
        $this->conf = Load::getConfig();
        $this->config = Config::getConfig();
        $this->db = Load::getDB();
        $this->hookmanager = Load::getHookManager();
        $this->langs = Load::getLangs();
        $this->user = Load::getUser();
        $this->menumanager = Load::getMenuManager();
        if (isset($this->menumanager)) {
            $this->menumanager->loadMenu();
        }
        $this->mysoc = Load::getMySoc();
    }

}
