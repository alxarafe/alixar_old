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

use Alxarafe\DB\DB;
use Alxarafe\LibClass\Lang;
use Jenssegers\Blade\Blade;
use stdClass;

/**
 * Class BasicController
 *
 * Loads the configuration, executes the defined action and after finishing,
 * displays the selected template.
 *
 * @package Alxarafe\Base
 */
abstract class BasicController extends Globals
{
    /**
     * Template to show
     *
     * @var string
     */
    protected $template;

    /**
     * Action to execute. If none are executed, noAction would be thrown.
     *
     * @var string|null
     */
    protected $action;

    /**
     * Database controller (DB descendant), or null.
     *
     * @var null|DB
     */
    public $db;

    /**
     * Translation class instance
     *
     * @var Lang
     */
    public $lang;

    /**
     * Config class instance
     *
     * @var Conf
     */
    public $config;

    /**
     * Application configuration parameters
     *
     * @var stdClass
     */
    public $conf;

    /**
     * Initilize global variables
     */
    public function __construct()
    {
        $this->db = Globals::getDb();
        $this->lang = Globals::getLang();
        $this->conf = Globals::getConf();
        $this->config = Globals::getConfig();
    }

    /**
     * Controller main code
     * By default, it only executes the selected action.
     *
     * @return bool
     */
    public function body()
    {
        return $this->checkAction();
    }

    /**
     * Code to execute if there is no action defined.
     *
     * @return bool
     */
    abstract public function noAction(): bool;

    /**
     * Executes the selected action.
     *
     * @return bool
     */
    public function checkAction(): bool
    {
        $this->action = filter_input(INPUT_POST, 'action');
        switch ($this->action) {
            case null:
            case '':
                return $this->noAction();
        }
        return false;
    }

    /**
     * Show the selected view.
     */
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
