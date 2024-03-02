<?php

/* Copyright (C) 2024 Rafael San José     <rsanjose@alxarafe.com>
 * Copyright (C) 2024 Francesc Pineda     <fpineda@alxarafe.com>
 * Copyright (C) 2024 Cayetano Hernández  <chernandez@alxarafe.com>
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

use Jenssegers\Blade\Blade;

abstract class BasicController extends Globals
{
    protected $template;
    protected $action;

    public $db;
    public $lang;
    public $config;

    public function __construct()
    {
        $this->db = Globals::getDb();
        $this->lang = Globals::getLang();
        $this->config = Globals::getConfig();
    }

    public function body()
    {
        return $this->checkAction();
    }

    abstract public function noAction(): bool;

    public function checkAction(): bool
    {
        $this->action = filter_input(INPUT_POST, 'action');
        switch ($this->action) {
            case '':
                return $this->noAction();
        }
        return false;
    }

    public function view()
    {
        if (!$this->body()) {
            // Action failed!?
        }

        $route = realpath('Resources');
        $cache = realpath('../Cache');
        $blade = new Blade($route, $cache);
        echo $blade->render($this->template, array_merge(get_object_vars($this)));
    }
}
