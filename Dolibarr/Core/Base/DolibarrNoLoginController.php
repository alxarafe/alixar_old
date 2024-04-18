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

use Alxarafe\Base\Globals;

/**
 * Class DolibarrController. Controller to carry out the migration from Dolibarr to Alixar.
 *
 * This class is only needed for compatibility with Dolibarr.
 *
 * @package DoliCore\Base
 */
abstract class DolibarrNoLoginController extends DolibarrGenericController
{
    public $conf;
    public $config;
    public $db;
    public $hookmanager;
    public $langs;

    public function __construct()
    {
        $this->conf = Config::getConf();
        if ($this->conf !== null) {
            $this->config = Config::getConfig($this->conf);
            /*
            $this->db = Config::getDb($this->conf);
            $this->hookmanager = Config::getHookManager();
            $this->menumanager = Config::getMenuManager($this->conf);
            */
            $this->langs = Config::getLangs($this->conf);
        }

        parent::__construct();
    }

    public function index(bool $executeActions = true): bool;

    abstract public function check();

    abstract public function fileconf();

    abstract public function step1();
    abstract public function step2();
    abstract public function step4();
    abstract public function step5();
}
