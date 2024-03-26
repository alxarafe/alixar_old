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

namespace DoliCore\Base;

use Alxarafe\Base\Controller;
use Alxarafe\Base\Globals;

/**
 * Class DolibarrController. Controller to carry out the migration from Dolibarr to Alixar.
 *
 * @package    Alxarafe\Base
 *
 * @deprecated This class is only needed for compatibility with Dolibarr.
 */
abstract class DolibarrController extends Controller
{
    public $conf;
    public $config;
    public $db;
    public $hookmanager;
    public $user;
    public $langs;

    public function __construct()
    {
        $this->conf = Config::loadConf();
        $this->config = Globals::getConfig($this->conf);
        $this->db = Globals::getDb($this->conf);
        $this->hookmanager = Globals::getHookManager();
        $this->user = Globals::getUser();
        $this->menumanager = Globals::getMenuManager($this->conf);
        $this->langs = Globals::getLangs($this->conf);

        $this->go();
    }

    abstract public function go();
}
