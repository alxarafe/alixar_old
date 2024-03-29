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

/**
 * Class GenericController. The generic controller contains what is necessary for any controller
 *
 * @package Alxarafe\Base
 */
abstract class GenericController
{
    /**
     * Contains the action to execute.
     *
     * @var string
     */
    protected $action;

    /**
     * GenericController constructor.
     */
    public function __construct()
    {
        $this->action = filter_input(INPUT_GET, GET_FILENAME_VAR);
        if (empty($this->action)) {
            $this->action = 'index';
        }
        if (method_exists($this, $this->action)) {
            return $this->{$this->action}();
        }
        return $this->index();
    }

    abstract public function index();
}